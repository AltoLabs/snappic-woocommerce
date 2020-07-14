<?php
/**
Plugin Name: Snappic Integration for WooCommerce
Plugin URI: https://wordpress.org/plugins/snappic-for-woocommerce/
Description: Link your WooCommerce store to Snappic
Version: 1.3.0
Author: Snappic
Author URI: https://www.snappic.io
License: GPL2 http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: snappic-for-woocommerce
Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Snappic_Base {
    const VERSION = '1.3.0';
    const REQUIRED_WOO = '3.1.0';

    public static $_instance;

    /**
    * Get an instance of this class.
    * @since  1.0.0
    */
    public static function get_instance() {
        if ( is_null( self::$_instance ) )
            self::$_instance = new self();
        return self::$_instance;
    }

    /**
     * Initialize the plugin.
     */
    public function __construct() {

        if ( ! class_exists( 'WooCommerce' ) || version_compare( wc()->version, self::REQUIRED_WOO, '<' ) ) {
            add_action( 'admin_notices', array($this, 'plugin_error') );
            return;
        }

        // Redirect to Install Wizard.
        add_action( 'admin_init', array( $this, 'admin_redirect' ) );

        // Include required files.
        $this->includes();

        // Set up the helper class.
        $this->helper = Snappic_Helper::get_instance();

        // Show notice if in sandbox mode.
        add_action( 'admin_notices', array( $this, 'admin_notice' ), 99 );

        // Add settings link to plugins page
        add_filter( 'plugin_action_links', array( $this, 'add_action_links' ), 10, 2 );

        // Fetch pixel.
        add_action( 'shutdown', array( $this, 'maybe_fetch_pixel' ), 20 );

        // Set up localisation.
        add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

        // Register integration section
        add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );

        // Register API endpoint
        add_filter( 'rest_api_init', array( $this, 'add_api_resource' ) );

        // Ajax callback for updating permalinks
        add_action( 'wp_ajax_snappic_update_permalinks', array( $this, 'update_permalinks' ) );

        /*
         * Save routine workaround
         * WooCommerce saves the integrations *after* admin_notice hook
         * So our notices are not updated until refresh
         * See: https://github.com/woocommerce/woocommerce/issues/16221
         */
        add_action( 'load-woocommerce_page_wc-settings', array( $this, 'save_workaround' ), 20 );

    }

    /*-----------------------------------------------------------------------------------*/
    /*  Required files.                                                                */
    /*-----------------------------------------------------------------------------------*/

    /**
     * Add Includes.
     */
    public function includes() {
        include_once( 'includes/class-snappic-helper.php' );
        include_once( 'includes/class-snappic-integration.php' );
        include_once( 'includes/class-snappic-api-controller.php' );

        // Setup/welcome.
        if ( ! empty( $_GET['page'] ) ) {
            switch ( $_GET['page'] ) {
                case 'snappic-welcome' :
                    include_once( 'includes/class-snappic-admin-setup-wizard.php' );
                break;
            }
        }

        // Front End Pixel.
        if( ! is_admin() ) {
            include_once( 'includes/class-snappic-pixel.php' );
            include_once( 'includes/class-snappic-pixel-display.php' );
        }
    }

    /*-----------------------------------------------------------------------------------*/
    /*  Activation / Deactivation                                                                   */
    /*-----------------------------------------------------------------------------------*/

    /**
     * Add installation later.
     */
    public static function activation() {

        // Queue upgrades/setup wizard
        $settings    = get_option( 'woocommerce_snappic_settings', null );

        // No versions? This is a new install :)
        if ( is_null( $settings ) && apply_filters( 'snappic_enable_setup_wizard', true ) ) {
            set_transient( '_snappic_activation_redirect', 1, 30 );
        }

        $snappic = Snappic_Base::get_instance();
        add_action( 'shutdown', array( $snappic, 'delayed_install' ) );
    }

    /**
     * Handle redirects to setup/welcome page after install and updates.
     *
     * For setup wizard, transient must be present, the user must have access rights, and we must ignore the network/bulk plugin updaters.
     */
    public function admin_redirect() {

        // Setup wizard redirect
        if ( get_transient( '_snappic_activation_redirect' ) ) {
            delete_transient( '_snappic_activation_redirect' );

            if ( ( ! empty( $_GET['page'] ) && in_array( $_GET['page'], array( 'snappic-welcome' ) ) ) || is_network_admin() || isset( $_GET['activate-multi'] ) || ! current_user_can( 'manage_woocommerce' ) || apply_filters( 'woocommerce_prevent_automatic_wizard_redirect', false ) ) {
                return;
            }

            // If the user needs to install, send them to the setup wizard
            wp_safe_redirect( admin_url( 'index.php?page=snappic-welcome' ) );
            exit;

        }
    }

    /**
     * Delay the keygen until shutdown.
     */
    public function delayed_install() {
        if( ! $this->helper->get_stored_pixel_id() ) {
            $settings = Snappic_Integration::instance();
            $settings->set_api_keys();
        }
    }



    /**
     * Delete options table entries ONLY when plugin deactivated AND deleted
     * @since 1.0
     */
    public static function delete_plugin_options() {
        $options = get_option( 'woocommerce_snappic_settings', true );
        if( isset( $options['cleanup'] ) && 'yes' == $options['cleanup'] ) {

            // Delete the API key
            if( isset( $options['key_id'] ) && 0 < $options['key_id'] ) {
                include_once( 'includes/class-snappic-auth.php' );
                Snappic_Auth::delete_key( $options['key_id'] );
            }

            delete_option( 'woocommerce_snappic_settings' );
        }
    }

    /**
    * Display notice if WooCommerce is not installed
    * @since  1.0.0
    */
    public function plugin_error() {

        $class = 'notice notice-error';
        $message = sprintf( __( 'Could not find a WooCommerce Install. Snappic requires WooCommerce v%s or higher.', 'snappic-for-woocommerce'), self::REQUIRED_WOO );

        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );

    }


    /**
    * Display notice if airplane mode causes pixel fetching to fail
    * @since  1.0.0
    */
    public function admin_notice() {

        $class = 'notice notice-warning';

        // Permalinks are disabled
        if ( '' == get_option( 'permalink_structure' ) || ! get_option( 'permalink_structure' ) ) {
            $message = sprintf( __( 'For the Snappic extension to operate properly, the URL rewriting <strong>must</strong> be turned on and set to any value (other than "Plain") in the %sPermalinks Settings%s', 'snappic-for-woocommerce' ), '<a href="' . admin_url( 'options-permalink.php' ) . '">', '</a>' );

            printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
        }

        // Notice in sandbox mode
        if( $this->helper->is_sandboxed() ) {

            $message = sprintf( __( 'Snappic is running in sandbox mode. Go to %sSnappic Settings%s to switch to live mode.', 'snappic-for-woocommerce' ), '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=integration&section=snappic' ) . '">', '</a>' );

            printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );

        // Notice if live and needs a pixel
        } else if ( $this->helper->is_live() && $this->helper->needs_pixel() ) {

            $signup_url = $this->helper->get_signup_url();

            $message = sprintf( __( 'Sign up for Snappic to get your tracking pixel. Go to %sSnappic%s to sign up.', 'snappic-for-woocommerce' ),
                '<a href="' . $signup_url . '">',
                '</a>' );

            printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );

        }


    }

    /**
    * Display a Settings link on the main Plugins page
    * @param  array $links
    * @param  string $file
    * @return array
    * @since  1.0.0
    */
    public function add_action_links( $links, $file ) {

        if ( $file == plugin_basename( __FILE__ ) ) {
            $plugin_link = '<a href="'. add_query_arg( array( 'page' => 'wc-settings', 'tab' => 'integration', 'section' => 'snappic' ), admin_url( 'admin.php' ) ) . '">' . __( 'Settings', 'snappic-for-woocommerce' ) . '</a>';

            // make the 'Settings' link appear first
            array_unshift( $links, $plugin_link );
        }

        return $links;

    }

    /**
     * Load Localisation files.
     *
     * Note: the first-loaded translation file overrides any following ones if the same translation is present.
     *
     * Locales found in:
     *      - WP_LANG_DIR/snappic/snappic-LOCALE.mo
     *      - WP_LANG_DIR/plugins/snappic-LOCALE.mo
     */
    public function load_plugin_textdomain() {
        $locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
        $locale = apply_filters( 'plugin_locale', $locale, 'snappic' );

        unload_textdomain( 'snappic' );
        load_plugin_textdomain( 'snappic-for-woocommerce', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
    }


    /**
    * Determine if we need to fetch the pixel from API
    * @since  1.0.0
    */
    public function maybe_fetch_pixel() {
        if( $this->helper->needs_pixel() ) {
            $this->helper->fetch_pixel();
        }
    }

     /**
     * Add integration settings.
     *
     * @param $integrations array
     * @return  array
     */
    public function add_integration( $integrations ) {
        $integrations[] = 'Snappic_Integration';
        return $integrations;
    }

     /**
     * Add custom endpoint.
     *
     * @param $endpoints array
     * @return  array
     */
    public function add_api_resource( $endpoints ) {
        $controller = new Snappic_API_Controller;
        $controller->register_routes();
    }


     /**
     * Workaround for Integrations .
     *
     * @param $endpoints array
     * @return  array
     */
    public function save_workaround() {

        if ( ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-settings' ) ) {
            $current_section = empty( $_REQUEST['section'] ) ? '' : sanitize_title( $_REQUEST['section'] );

            if ( 'snappic' == $current_section ) {
                do_action( 'woocommerce_update_options_integration_snappic' );
            }
        }

    }


    /**
     * Update the Permalinks via AJAX
     * Using an AJAX callback is preferred to using a REST route because it ensures that the functions that write the HTACCESS are available.
     *
     * @return string json-encoded
     */
    public function update_permalinks() {

        global $wp_rewrite;

        $permalink_structure = "/%postname%/";

		if ( ! isset( $_POST[ 'nonce' ] ) || ! wp_verify_nonce( $_POST[ 'nonce' ], 'snappic_update' ) ) {
			wp_die();
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die();
		}

        $wp_rewrite->set_permalink_structure( $permalink_structure );
        flush_rewrite_rules();
        wp_die('1');

    }


    /*-----------------------------------------------------------------------------------*/
    /*  Helpers                                                                   */
    /*-----------------------------------------------------------------------------------*/

    /**
     * Get the plugin url.
     *
     * @return string
     */
    public function plugin_url() {
        return untrailingslashit( plugins_url( '/', __FILE__ ) );
    }


    /**
     * Get the plugin path.
     *
     * @return string
     */
    public function plugin_path() {
        return untrailingslashit( plugin_dir_path( __FILE__ ) );
    }


}

// Initialize the plugin
add_action( 'plugins_loaded', array( 'Snappic_Base', 'get_instance' ) );

// Activation.
register_activation_hook( __FILE__, array( 'Snappic_Base', 'activation' ) );

// Uninstall.
register_uninstall_hook( __FILE__, array( 'Snappic_Base', 'delete_plugin_options' ) );
