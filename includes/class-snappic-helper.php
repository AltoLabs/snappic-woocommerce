<?php

if (!defined('ABSPATH')) exit;

class Snappic_Helper {
    const TESTPIXEL = '123123123';
    const API_HOST_DEFAULT = 'https://api.snappic.io';
    const API_SANDBOX_HOST_DEFAULT = 'http://api.magento-sandbox.snappic.io';
    const SNAPPIC_ADMIN_URL_DEFAULT = 'https://app.snappic.io';
    const OPTION = 'woocommerce_snappic_settings';

    public static $_instance;

    private $stored_pixel_id;
    private $pixel_id;

    /**
    * Get an instance of this class.
    * @since  1.0.0
    */
    public static function get_instance() {
        if (is_null(self::$_instance))
            self::$_instance = new self();
        return self::$_instance;
    }

    /**
    * Fetch the pixel from API
    * @todo : add timer (don't do this every page load)
    * @since  1.0.0
    */
    public function fetch_pixel () {

        $result = wp_cache_get('snappic_pixel_request', 'api_calls');

        if (false === $result) {
            $result = wp_safe_remote_get($this->get_api_url());
            wp_cache_set('snappic_pixel_request', $result, 'api_calls', 900);
        }

        // Do something with $result
        if (is_wp_error($result)) {
            return false;
        }

        $body = wp_remote_retrieve_body($result);
        $data = json_decode($body);
        // No pixel...
        if (!isset($data->facebook_pixel_id) || $data->facebook_pixel_id == '') {
            return false;
        }
        // If pixel is valid and safe, clear the cache pixel result.
        if ($this->save_pixel_id($data->facebook_pixel_id)) {
            wp_cache_delete('snappic_pixel_request', 'api_calls');
        }
    }

    /**
    * Get the pixel ID that is stored in the admin settings
    * @return string
    * @since  1.0.0
    */
    public function get_stored_pixel_id() {
        if (!$this->stored_pixel_id) {
            $settings = Snappic_Integration::instance();
            $this->stored_pixel_id = $settings->get_option('pixel_id');
        }
        return $this->stored_pixel_id;
    }

    /**
    * Get the pixel ID that is stored in the admin settings OR test pixel
    * @return stringfetch
    * @since  1.0.0
    */
    public function get_pixel_id() {
        if (!$this->pixel_id) {
            $this->pixel_id = $this->is_sandboxed() ? self::TESTPIXEL : $this->get_stored_pixel_id();
        }
        return $this->pixel_id;
    }

    /**
    * Save the pixel ID that is stored in the admin settings
    * @return bool
    * @since  1.0.0
    */
    public function save_pixel_id($pixel_id) {
        return $this->update_options(array('pixel_id' => $pixel_id));
    }

    /**
    * Save an array of options
    *
    * @param array $new_options
    * @return array
    * @since  1.0.0
    */
    public function update_options($options = array()) {
        $settings = Snappic_Integration::instance();
        $old_options = (array) get_option($settings->get_option_key());
        $new_options = apply_filters('woocommerce_settings_api_sanitized_fields_' . $settings->id, $options);
        $updated_options = array_merge($old_options, $new_options);
        return update_option($settings->get_option_key(), $updated_options);
    }

    /**
    * Determine if we need to fetch the pixel from API
    * @since  1.0.0
    */
    public function needs_pixel() {
        $pixel_id = $this->get_stored_pixel_id();
        if (($this->is_sandboxed() && '' == $pixel_id) || ($this->is_live() && in_array($pixel_id, array('', self::TESTPIXEL)))) {
            return true;
        } else {
            return false;
        }
    }

    /**
    * Determine if we have a pixel
    * @since  1.0.0
    */
    public function has_pixel() {
        return (bool)!$this->needs_pixel();
    }

    /**
     * Get whether or not live mode is enabled
     *
     * @return bool
     */
    public function is_live() {
        return (bool)!$this->is_sandboxed();
    }

    /**
     * Get whether or not sandbox mode is enabled
     *
     * @return bool
     */
    public function is_sandboxed() {
        return (bool) ('sandbox' == $this->get_mode());
    }

    /**
    * Get the mode
    * @return  string sandbox|live
    * @since  1.0.0
    */
    public function get_mode() {
        $settings = Snappic_Integration::instance();
        return $settings->get_option('mode');
    }

    /**
     * Return the endpoint for the Snappic API
     *
     * @param  bool $bypassSandbox
     * @return string
     */
    public function get_api_host($bypassSandbox = false) {
        if (!$bypassSandbox && $this->is_sandboxed()) {
            return self::API_SANDBOX_HOST_DEFAULT;
        }
        return $this->getEnvOrDefault('SNAPPIC_API_HOST', self::API_HOST_DEFAULT);
    }

    /**
     * Return the url for the Snappic API
     *
     * @param  bool $bypassSandbox
     * @return string
     */
    public function get_api_url($bypassSandbox = false) {
        return add_query_arg('domain', $this->get_site_domain(), $this->get_api_host($bypassSandbox) . '/admin_frontend/stores/current');
    }

    /**
     * Return the checkout tracker url for the Snappic API
     *
     * @param  int $order_id
     * @param  bool $bypassSandbox
     * @return string
     */
    public function get_checkout_tracker_url($order_id, $bypassSandbox = false) {
        return add_query_arg(
            array(
                'store_domain' => $this->get_site_domain(),
                'order_id' => $order_id
            ),
            $this->get_api_host($bypassSandbox).'/checkout_trackers/record'
        );
    }

    /**
     * @return string
     */
    public function get_snappic_admin_url() {
        return $this->getEnvOrDefault('SNAPPIC_ADMIN_URL', self::SNAPPIC_ADMIN_URL_DEFAULT);
    }

    /**
     * Return the checkout tracker url for the Snappic API
     *
     * @param  str $plan starter|growth
     * @return string
     */
    public function get_signup_url($plan = '') {
        $settings = Snappic_Integration::instance();

        // Check if API keys are correctly set.
        $settings->check_api_keys();

        $consumerKey = $settings->get_option('cust_key');
        $consumerSecret = $settings->get_option('cust_secret');
        $query_args = array(
            'login' => true,
            'provider'  => 'woocommerce',
            'domain' => urlencode($this->get_site_domain()),
            'access_token' => urlencode($consumerKey.':'.$consumerSecret)
       );

        $plan = in_array($plan, array('starter', 'growth')) ? $plan : '';
        if ($plan) {
            $query_args['sra_plan'] = $plan;
        }

        return add_query_arg($query_args, $this->get_snappic_admin_url());
    }

    /**
     * Return the checkout tracker url for the Snappic API
     *
     * @return string
     */
    public function get_login_url() {
        return add_query_arg('login', '', $this->get_snappic_admin_url());
    }

    /**
     * Parse the site's domain
     *
     * @return string
     */
    public function get_site_domain () {
        $domain = get_site_url();
        $components = parse_url($domain);
        return strtolower($components['host']);
    }

    /**
     * Return from environment variables or a default value
     *
     * @param  string $key
     * @param  string $key
     * @return string
     */
    public function getEnvOrDefault($key, $default = null) {
        $val = getenv($key);
        return empty($val) ? $default : $val;
    }

}
