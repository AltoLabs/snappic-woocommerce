<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Snappic_Integration extends WC_Integration {

    public static $_instance;

    public static function instance() {
        if ( is_null( self::$_instance ) )
            self::$_instance = new self();
        return self::$_instance;
    }

    public function __construct()
    {
        $this->id = 'snappic';
        $this->method_title = __( 'Snappic Integration', 'snappic-for-woocommerce' );
        $this->method_description = __( 'Enable integration with Snappic retargeting service', 'snappic-for-woocommerce' );

        add_action( 'woocommerce_update_options_integration_' . $this->id, array( $this, 'process_admin_options' ) );

        $this->init_form_fields();
        $this->init_settings();

        self::$_instance = $this;
    }

    /**
     * Checks if API keys are set correctly. Otherwise regenerate new ones.
     *
     * @since  1.0.0
     */
    public function check_api_keys() {
        if ($this->get_option('cust_key') && $this->get_option('cust_secret')) {
            return;
        }

        $this->set_api_keys();
    }

    /**
     * Generate API keys and stored them in our own settings.
     *
     * @since  1.0.0
     */
    public function set_api_keys() {
        update_option( 'woocommerce_api_enabled', 'yes' );

        include_once( 'class-snappic-auth.php' );
        $snappicAuth = new Snappic_Auth();
        $helper = Snappic_Helper::get_instance();
        $domain = $helper->get_site_domain();
        $result = $snappicAuth->generate_keys( __( 'Snappic', 'snappic-for-woocommerce' ), $domain, 'read' );

        if( ! is_wp_error( $result ) && !empty($result['key_id']) && !empty($result['consumer_key']) && !empty($result['consumer_secret']) ) {

            $updated_options = array(
                'key_id' => $result['key_id'],
                'cust_key' => $result['consumer_key'],
                'cust_secret'   => $result['consumer_secret'],
                'cleanup'   => 'yes'
            );

            $helper->update_options( $updated_options );
            $this->init_settings();
        }
    }

    /**
     * Initialize settings form fields.
     *
     * Add an array of fields to be displayed
     * on the gateway's settings screen.
     *
     * @since  1.0.0
     * @return string
     */
    public function init_form_fields() {
        
        $this->form_fields = array(
            'cust_key' => array(
                'title'         => __( 'Customer Key', 'snappic-for-woocommerce' ),
                'type'          => 'text',
                'default'       => '',
                'custom_attributes' => array( 'disabled' => 'DISABLED '),
            ),
            'cust_secret' => array(
                'title'         => __( 'Customer Secret', 'snappic-for-woocommerce' ),
                'type'          => 'text',
                'default'       => '',
                'custom_attributes' => array( 'disabled' => 'DISABLED '),
            ),
            'pixel_id' => array(
                'title'         => __( 'Facebook Pixel ID', 'snappic-for-woocommerce' ),
                'type'          => 'text',
                'default'       => '',
                'custom_attributes' => array( 'disabled' => 'DISABLED '),
            ),
            'mode' => array(
                'title'         => __( 'Mode', 'snappic-for-woocommerce' ),
                'type'          => 'select',
                'desc_tip'          => true,
                'description'       => __( 'Run Snappic in sandbox or in live mode', 'snappic-for-woocommerce' ),
                'default'       => 'live',
                'options'       => array(
                                    'sandbox' => __( 'Sandbox', 'snappic-for-woocommerce' ),
                                    'live'     => __( 'Live', 'snappic-for-woocommerce' )
                                )
            ),
            'cleanup' => array(
                'title'         => __( 'Cleanup on Uninstall', 'snappic-for-woocommerce' ),
                'label'         => __( 'Completely remove settings on plugin removal', 'snappic-for-woocommerce' ),
                'type'          => 'checkbox',
                'default'       => 'yes'
            )

        );

    }


    /**
     * Validate the custom fields, helps persist the disabled text inputs
     *
     * @param string $key
     * @param string $value
     *
     * @since  1.0.0
     * @return string
     */
    public function validate_cust_key_field( $key, $value ) {
        return $this->get_option( $key );
    }

    public function validate_cust_secret_field( $key, $value ) {
        return $this->get_option( $key );
    }

    public function validate_pixel_id_field( $key, $value ) {
        return $this->get_option( $key );
    }

}