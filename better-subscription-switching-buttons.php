<?php
/**
 * Plugin Name: Better Subscription Switching Buttons
 * Description: Improves the subscription switching experience in WooCommerce Subscriptions.
 * Version: 1.0.0
 * Text Domain: better-subscription-switching-buttons
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.2
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 *
 * @package BetterSubscriptionSwitchingButtons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include the main plugin class.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-better-subscription-switching-buttons.php';

/**
 * Returns the main instance of Better_Subscription_Switching_Buttons
 *
 * @return Better_Subscription_Switching_Buttons
 */
function better_subscription_switching_buttons() {
	return Better_Subscription_Switching_Buttons::instance();
}

// Initialize the plugin.
better_subscription_switching_buttons();
