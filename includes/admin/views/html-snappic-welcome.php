<?php
/**
 * Admin View: Snappic Welcome Page
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}


?>
  <!-- begin header  -->
  <div class="header">
    <div class="header__logo">
      <img src="<?php echo Snappic_Base::get_instance()->plugin_url();?>/assets/img/logo.svg" alt="">
    </div>
    <div class="header__right">
      <a href='<?php echo esc_url( Snappic_Helper::get_instance()->get_login_url() ); ?>' class="header__user"><?php _e( 'Already have an account?', 'snappic-for-woocommerce' );?></a>
      <a href='<?php echo esc_url( Snappic_Helper::get_instance()->get_login_url() ); ?>' class="header__signin button"><span><?php _e( 'Sign in', 'snappic-for-woocommerce' );?></span></a>
    </div>
  </div>
  <!-- end header -->

  <!-- begin slides  -->
  <div class="slides">

    <div class="slides__wrap">
    
      <?php  // Permalinks are disabled
        if ( '' == get_option( 'permalink_structure' ) || ! get_option( 'permalink_structure' ) ) { ?>

        <!-- begin content  -->
        <div class="content" id="change_url_structure">
        <h2 class="h2"><?php _e( 'Looks like we have to switch your URL structure from “Plain” to “Post Name”', 'snappic-for-woocommerce' );?></h2>
        <div class="subtitle"><?php _e( 'THIS ALLOWS US TO SYNC UP WITH YOUR INVENTORY AND STOP SHOWING ADS FOR PRODUCTS ONCE THEY ARE OUT OF STOCK', 'snappic-for-woocommerce' );?></div>
        <a href='#pick_plan' class="button-blue js-goto update-permalink" data-goto="pick_plan"><?php _e( 'Change and Continue', 'snappic-for-woocommerce' );?></a>
        <div class="note"><?php _e( 'This won’t change how your website looks or any of your products', 'snappic-for-woocommerce' );?></div>
      </div>
      <!-- end content -->

      <?php } ?>
      

      <!-- begin content  -->
      <div class="content" id="pick_plan">
        <h2 class="h2"><?php _e( 'Awesome! Now, pick your plan', 'snappic-for-woocommerce' );?></h2>
        <div class="subtle"><?php _e( '14-DAY TRIAL. FLEXIBLE PRICING. NO CONTRACTS', 'snappic-for-woocommerce' );?></div>

        <!-- begin plans  -->
        <div class="plans">
          <!-- begin plan  -->
          <div class="plan">
            <h2 class="plan__title"><?php _e( 'Starter', 'snappic-for-woocommerce' );?></h2>
            <div class="plan__price"><?php _e( '$99/MO', 'snappic-for-woocommerce' );?></div>
            <div class="plan__descr"><?php _e( 'A 4% campaign management fee on the ad spend is applied to all plans', 'snappic-for-woocommerce' );?></div>
            <a href="<?php echo esc_url( Snappic_Helper::get_instance()->get_signup_url( 'starter' ) );?>" class="plan__buy button js-goto" data-plan="starter"><span><?php _e( 'Get Starter', 'snappic-for-woocommerce' );?></span></a>
            <div class="plan__image">
              <img src="<?php echo Snappic_Base::get_instance()->plugin_url();?>/assets/img/plan1.jpg" alt="">
            </div>
            <ul class="plan__points">
              <li><?php _e( 'Instagram Retargeting Ads', 'snappic-for-woocommerce' );?></li>
              <li><?php _e( 'Facebook Retargeting Ads', 'snappic-for-woocommerce' );?></li>
              <li><?php _e( 'Analytics', 'snappic-for-woocommerce' );?></li>
              <li><?php _e( 'Campaign Setup', 'snappic-for-woocommerce' );?></li>
              <li><?php _e( 'Campaign Optimization', 'snappic-for-woocommerce' );?></li>
              <li><?php _e( 'Customer Support', 'snappic-for-woocommerce' );?></li>
              <li class="is-disabled"><?php _e( 'Use Social Creative', 'snappic-for-woocommerce' );?></li>
              <li class="is-disabled"><?php _e( 'Real Time Product Updates', 'snappic-for-woocommerce' );?></li>
            </ul>
          </div>
          <!-- end plan -->

          <!-- begin plan  -->
          <div class="plan plan_main">
            <div class="plan__icon"></div>
            <h2 class="plan__title"><?php _e( 'Growth', 'snappic-for-woocommerce' );?></h2>
            <div class="plan__price"><?php _e( '$199/MO', 'snappic-for-woocommerce' );?></div>
            <div class="plan__descr"><?php _e( 'A 4% campaign management fee on the ad spend is applied to all plans', 'snappic-for-woocommerce' );?></div>
            <a href="<?php echo esc_url( Snappic_Helper::get_instance()->get_signup_url( 'growth' ) );?>" class="plan__buy button js-goto" data-plan="growth"><span><?php _e( 'Get Growth', 'snappic-for-woocommerce' );?></span></a>
            <div class="plan__image">
              <img src="<?php echo Snappic_Base::get_instance()->plugin_url();?>/assets/img/plan2.jpg" alt="">
            </div>
            <ul class="plan__points">
              <li><?php _e( 'Instagram Retargeting Ads', 'snappic-for-woocommerce' );?></li>
              <li><?php _e( 'Facebook Retargeting Ads', 'snappic-for-woocommerce' );?></li>
              <li><?php _e( 'Analytics', 'snappic-for-woocommerce' );?></li>
              <li><?php _e( 'Campaign Setup', 'snappic-for-woocommerce' );?></li>
              <li><?php _e( 'Campaign Optimization', 'snappic-for-woocommerce' );?></li>
              <li><?php _e( 'Customer Support', 'snappic-for-woocommerce' );?></li>
              <li class="is-new"><?php _e( 'Use Social Creative', 'snappic-for-woocommerce' );?></li>
              <li><?php _e( 'Real Time Product Updates', 'snappic-for-woocommerce' );?></li>
            </ul>
          </div>
          <!-- end plan -->
        </div>
        <!-- end plans -->
      </div>
      <!-- end content -->
    </div>
    <!-- end slides wrap -->
  </div>
  <!-- end slides -->