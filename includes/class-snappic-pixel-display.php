<?php
/**
 * Facebook Pixel Display
 * borrrowed heavily from Facebook for WooCommerce plugin
 * @since    1.0.0
 */



if ( ! defined( 'ABSPATH' ) ) exit;

class Snappic_Pixel_Display
{

    const FB_RETAILER_ID_PREFIX = 'snappic_';

    private static $pixel_id;
    private static $pixel;

    public static function init() {

        $helper = Snappic_Helper::get_instance();
        self::$pixel_id = $helper->get_pixel_id();
        
        if( self::$pixel_id ) {

            self::$pixel = new Snappic_Pixel( self::$pixel_id );

            // Pixel Tracking Hooks
            add_action( 'wp_head', array( __CLASS__, 'inject_base_pixel' ) );
            add_action( 'woocommerce_after_single_product', array( __CLASS__, 'inject_view_content_event' ) );
            add_action( 'woocommerce_add_to_cart', array( __CLASS__, 'inject_add_to_cart_event' ), 40 );
            add_action( 'woocommerce_thankyou', array( __CLASS__, 'inject_purchase_event' ) );     
            add_action( 'wc_ajax_inject_add_to_cart_event', array( __CLASS__, 'inject_ajax_add_to_cart_event' ) );

        }


    }

  /**
  * Base pixel code to be injected on page head. Because of this, it's better to
  * echo the return value than using wc_enqueue_js()
  * in this case
  */
  public static function inject_base_pixel() {
    echo self::$pixel->pixel_base_code();
  }


  /**
  * Helper function to iterate through a cart and gather all content ids
  */
  private static function get_content_ids_from_cart( $cart ) {
    $product_ids = array();
    foreach ( $cart as $item ) {
        // Remember that $item['data'] is the $product object if needed
        $product_ids[] = self::prefix_product_id( $item['product_id'] );
    }
    return $product_ids;
  }

  /**
  * Helper function to prefix product ID
  */
  private static function prefix_product_id( $product_id ) {
      return self::FB_RETAILER_ID_PREFIX . $product_id;
  }

  /**
  * Triggers ViewContent product pages
  */
  public static function inject_view_content_event() {
    $product = wc_get_product(get_the_ID());
    $content_type = 'product';

    // if product is a variant, fire the pixel with content_type: product_group
    if ($product->get_type() === 'variable') {
      $content_type = 'product_group';
    }

    $content_ids = (array) self::prefix_product_id( $product->get_id() );

    self::$pixel->inject_event(
      'ViewContent',
      array(
        'content_name' => $product->get_title(),
        'content_ids' => json_encode($content_ids),
        'content_type' => $content_type,
        'value' => $product->get_price(),
        'currency' => get_woocommerce_currency()
      ));
  }

  /**
  * Triggers AddToCart for cart page and add_to_cart button clicks
  */
  public static function inject_add_to_cart_event() {
    $product_ids = self::get_content_ids_from_cart(WC()->cart->get_cart());

    self::$pixel->inject_event(
      'AddToCart',
      array(
        'content_ids' => json_encode($product_ids),
        'content_type' => 'product',
        'value' => WC()->cart->cart_contents_total, // $cart->total isn't calculated by WC until cart/checkout
        'currency' => get_woocommerce_currency()
      ));
  }


  /**
  * Triggered by add_to_cart jquery trigger
  */
  public static function inject_ajax_add_to_cart_event() {
    ob_start();
    
    echo '<script>';
    
    $product_ids = self::get_content_ids_from_cart(WC()->cart->get_cart());

    echo self::$pixel->build_event(
      'AddToCart',
      array(
        'content_ids' => json_encode($product_ids),
        'content_type' => 'product',
        'value' => WC()->cart->total,
        'currency' => get_woocommerce_currency()
      ));
    echo '</script>';

    $pixel = ob_get_clean();

    wp_send_json( $pixel );
  }


  /**
  * Triggers Purchase for thank you page
  */
  public static function inject_purchase_event( $order_id ) { 

    $product_ids = array();

    $order = wc_get_order( $order_id );

    $url = Snappic_Helper::get_instance()->get_checkout_tracker_url( $order_id );

    // @Todo: do we need to SSL verify? Skipping for now
    $result = wp_safe_remote_get( $url, array( 'sslverify'   => false ) );

    if( ! is_wp_error( $result ) ) {
        $body = wp_remote_retrieve_body( $result );
        $data = json_decode( $body );

        if( isset( $data->result ) && 1 == $data->result ) {

            foreach ( $order->get_items() as $item ) {
               $product_ids[] = self::prefix_product_id( $item->get_product_id() );
            }

            self::$pixel->inject_event(
              'Purchase',
              array(
                'content_ids' => json_encode($product_ids),
                'content_type' => 'product',
                'value' => $order->get_total(),
                'currency' => get_woocommerce_currency()
              ));
        }
    }

  }

 

}

Snappic_Pixel_Display::init();