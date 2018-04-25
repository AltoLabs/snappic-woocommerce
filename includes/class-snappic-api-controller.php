<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Snappic_API_Controller extends WP_REST_Controller {

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wc/v1/snappic';

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'store';

 
  /**
   * Register the routes for the objects of the controller.
   */
  public function register_routes() {

    register_rest_route( $this->namespace, '/' . $this->rest_base, array(
      array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => array( $this, 'get_store_data' ),
        'permission_callback' => array( $this, 'get_items_permissions_check' ),
        'args'                => $this->get_collection_params(),
      ),
      'schema' => array( $this, 'get_public_item_schema' ),
    ) );

  }
 
  /**
   * Get a collection of items
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|WP_REST_Response
   */
  public function get_store_data( $request ) {

    $data = array(
            'version' => Snappic_Base::VERSION,
            'name' => get_bloginfo( 'name' ),
            'domain' => Snappic_Helper::get_instance()->get_site_domain(),
            'currency' => get_woocommerce_currency(),
            'money_with_currency_format' => self::get_price_format(),
            'ssl'   => is_ssl(),
            'iana_timezone' => wc_timezone_string(),
            'email' => get_option( 'admin_email' )
        );
 
    return rest_ensure_response( $data );
  }

  /**
   * Check whether a given request has permission to read data.
   *
   * @param  WP_REST_Request $request Full details about the request.
   * @return WP_Error|boolean
   */
  public function get_items_permissions_check( $request ) {
    if ( ! wc_rest_check_user_permissions( 'read' ) ) {
      return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot view store data.', 'snappic-for-woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
    }

    return true;
  }

  /**
   * Get the price format for Snappic
   */
  private function get_price_format() {
    $currency = get_woocommerce_currency();
    $format = get_woocommerce_price_format(); // "%1$s%2$s" where 1 is the symbol and 2 is the amount
    $symbol = get_woocommerce_currency_symbol( $currency );
    return sprintf( $format, $symbol, '{{amount}}' );
  }
  

  /**
   * Makes sure the current user has access to WRITE the settings APIs.
   *
   * @since  1.0.0
   * @deprecated 1.1.0
   * 
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|boolean
   */
  public function update_permalinks_permissions_check( $request ) { 
    _deprecated_function( 'WC_Free_Gift_Coupons::update_permalinks', '1.1.0', 'Snappic_Base::update_permalinks()' );
    if ( ! wc_rest_check_manager_permissions( 'settings', 'edit' ) ) {
      return new WP_Error( 'woocommerce_rest_cannot_edit', __( 'Sorry, you cannot edit the permalinks.', 'snappic-for-woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
    }

    return true;
  }

  /**
   * Check whether a given request has permission to read data.
   * 
   * @since 1.0.0 
   * @deprecated 1.1.0 - process now occurs via AJAX callback, see Snappic_Base::update_permalinks()
   *
   * @param  WP_REST_Request $request Full details about the request.
   * @return WP_Error|boolean
   */
  public function update_permalinks( $request ) {
    _deprecated_function( 'WC_Free_Gift_Coupons::update_permalinks', '1.1.0', 'Snappic_Base::update_permalinks()' );
    $data = array( 'status' => update_option( 'permalink_structure', "/%year%/%monthnum%/%postname%/" ) );
    flush_rewrite_rules();
    return rest_ensure_response( $data );
  }


}


