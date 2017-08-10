<?php
/**
 * WooCommerce Snappic Auth
 *
 * Workaround to call parent protected method
 *
 * @since    1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Snappic_Auth extends WC_Auth {

	/**
	 * Create keys. This is protected in WC
	 * so we need to extend the class
	 *
	 * @since  1.0.0
	 *
	 * @param  string $app_name
	 * @param  string $app_user_id
	 * @param  string $scope
	 *
	 * @return array
	 */
	public static function generate_keys( $app_name, $app_user_id, $scope ) {
		$auth = new parent;
	    return $auth->create_keys( $app_name, $app_user_id, $scope );
	}

	/**
	 * Delete key.
	 *
	 * WC_Auth has this as a private method so we need to copy most of the logic here
	 *
	 * @since 1.0.0
	 *
	 * @param array $key
	 */
	public static function delete_key( $key_id ) {
		global $wpdb;

		$wpdb->delete( $wpdb->prefix . 'woocommerce_api_keys', array( 'key_id' => $key_id ), array( '%d' ) );
		
	}
}