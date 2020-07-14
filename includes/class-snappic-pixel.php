<?php
/**
* @package FacebookCommerce
*/

if (!class_exists('Snappic_Pixel')) :


class Snappic_Pixel {

  public function __construct($pixel_id, $user_info = array()) {
    $this->pixel_id = $pixel_id;
    $this->user_info = $user_info;
  }

  /**
  * Returns FB pixel code
  */
  public function pixel_base_code() {
    if (Snappic_Integration::instance()->get_option('skip_pixel')) {
      return;
    }

    $params = self::add_version_info();

    return sprintf("
<!-- Snappic Integration Begin -->
<!-- Facebook Pixel Code -->
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,'script','https://connect.facebook.net/en_US/fbevents.js');
%s
fbq('trackSingle', '%s', 'PageView', %s);

<!-- Support AJAX add to cart -->
if(typeof jQuery != 'undefined') {
  jQuery(document).ready(function($){
    jQuery( 'body' ).on( 'added_to_cart', function( event ) {

      // Ajax action.
      $.get( '?wc-ajax=inject_add_to_cart_event', function( data ) {
        $('head').append( data );
      });
      
    });
  });
}
<!-- End Support AJAX add to cart -->
</script>
<noscript><img height=\"1\" width=\"1\" style=\"display:none\"
src=\"https://www.facebook.com/tr?id=%s&ev=PageView&noscript=1\"
/></noscript>
<!-- DO NOT MODIFY -->
<!-- End Facebook Pixel Code -->
<!-- Snappic Integration end -->
      ",
      $this->pixel_init_code(),
      $this->pixel_id,
      json_encode($params, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT),
      esc_js($this->pixel_id));
  }

  /**
  * Preferred method to inject events in a page, normally you should use this
  * instead of Snappic_Pixel::build_event()
  */
  public function inject_event($event_name, $params, $method='trackSingle') {
    if (Snappic_Integration::instance()->get_option('skip_pixel')) {
      return;
    }

    $code = self::build_event($event_name, $params, $method);
    wc_enqueue_js($code);
  }

  /**
  * You probably should use Snappic_Pixel::inject_event() but
  * this method is available if you need to modify the JS code somehow
  */
  public static function build_event($event_name, $params, $method='trackSingle') {
    if (Snappic_Integration::instance()->get_option('skip_pixel')) {
      return;
    }

    $params = self::add_version_info($params);
    return sprintf(
      "// WooCommerce Snappic Integration Event Tracking\n".
      "fbq('%s', '%s', '%s', %s);",
      $method,
      Snappic_Integration::instance()->get_option('pixel_id'),
      $event_name,
      json_encode($params, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT));
  }

  private static function get_version_info() {
    return array(
      'source' => 'woocommerce',
      'version' => WC()->version,
      'pluginVersion' => Snappic_Base::VERSION );
  }

  /**
  * Returns an array with version_info for pixel fires. Parameters provided by
  * users should not be overwritten by this function
  */
  private static function add_version_info($params=array()) {
    // if any parameter is passed in the pixel, do not overwrite it
    return array_replace(self::get_version_info(), $params);
  }

  /**
  * Init code might contain additional information to help matching website
  * users with facebook users. Information is hashed in JS side using SHA256
  * before sending to Facebook.
  */
  private function pixel_init_code() {
    $version_info = self::get_version_info();
    $agent_string = sprintf(
      '%s-%s-%s',
      $version_info['source'],
      $version_info['version'],
      $version_info['pluginVersion']);

    $params = array(
      'agent' => $agent_string);

    return sprintf(
      "fbq('dataProcessingOptions', ['LDU'], 0, 0);\nfbq('init', '%s', %s, %s);\n",
      esc_js($this->pixel_id),
      json_encode($this->user_info, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT),
      json_encode($params, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT));
  }

}

endif;
