<?php
/**
 * Setup Wizard Class
 *
 * Takes new users through some basic steps to setup their store.
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     2.6.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Snappic_Admin_Setup_Wizard class.
 */
class Snappic_Admin_Setup_Wizard {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		if ( apply_filters( 'snappic_enable_setup_wizard', true ) && current_user_can( 'manage_woocommerce' ) ) {
			add_action( 'admin_menu', array( $this, 'admin_menus' ) );
			add_action( 'admin_init', array( $this, 'setup_wizard' ) );			
		}
	}

	/**
	 * Add admin menus/screens.
	 */
	public function admin_menus() {
		add_dashboard_page( '', '', 'manage_options', 'snappic-welcome', '' );
	}

	/**
	 * Show the setup wizard.
	 */
	public function setup_wizard() {
		if ( empty( $_GET['page'] ) || 'snappic-welcome' !== $_GET['page'] ) {
			return;
		}

		wp_enqueue_style( 'snappic-welcome', Snappic_Base::get_instance()->plugin_url() . '/assets/css/snappic-welcome.css', array(), time() );
		wp_enqueue_script( 'snappic-welcome', Snappic_Base::get_instance()->plugin_url() . '/assets/js/snappic-welcome.js', array( 'jquery' ), Snappic_Base::VERSION, true );

		$l10n = array( 
			'nonce' => wp_create_nonce( 'snappic_update' ),
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			 );
		wp_localize_script( 'snappic-welcome', 'snappic_for_woocommerce ', $l10n );

		ob_start();
		$this->setup_wizard_header();
		$this->setup_wizard_content();
		$this->setup_wizard_footer();
		exit;
	}



	/**
	 * Setup Wizard Header.
	 */
	public function setup_wizard_header() {
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta name="viewport" content="width=device-width" />
			<meta name="description" content="<?php _e( 'Snappic dynamic ads (DPA) for Catch Surf saw a 19X ROI, a 2.16% click through rate, and a 95.24% decrease in acquisition cost.', 'snappic-for-woocommerce' );?>">
			<meta name="format-detection" content="telephone=no">
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<title><?php esc_html_e( 'Welcome to Snappic for WooCommerce', 'snappic-for-woocommerce' ); ?></title>
			<?php wp_print_scripts( 'snappic-welcome' ); ?>
			<?php do_action( 'admin_print_styles' ); ?>
			<?php do_action( 'admin_head' ); ?>
		</head>
		<body class="snappic-welcome">
		<?php
	}


	/**
	 * Setup Wizard Footer.
	 */
	public function setup_wizard_footer() { ?>
			</body>
		</html>
		<?php
	}

	/**
	 * Output the content.
	 */
	public function setup_wizard_content() {
		include( Snappic_Base::get_instance()->plugin_path() . '/includes/admin/views/html-snappic-welcome.php' );	
	}  

}

new Snappic_Admin_Setup_Wizard();
