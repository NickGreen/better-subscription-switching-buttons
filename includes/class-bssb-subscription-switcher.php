<?php
/**
 * Handles subscription switching functionality
 *
 * @package BetterSubscriptionSwitchingButtons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Subscription Switcher class
 */
class BSSB_Subscription_Switcher {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Handle the switch action.
		add_action( 'init', array( $this, 'handle_switch_action' ) );

		// Add template override.
		add_filter( 'wc_get_template', array( $this, 'override_grouped_template' ), 10, 5 );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Override the grouped product template
	 *
	 * @param string $template Template path.
	 * @param string $template_name Template name.
	 * @param array  $args Template arguments.
	 * @param string $template_path Template path.
	 * @param string $default_path Default path.
	 * @return string
	 */
	public function override_grouped_template( $template, $template_name, $args, $template_path, $default_path ) {
		if ( 'single-product/add-to-cart/grouped.php' === $template_name ) {
			$template = plugin_dir_path( dirname( __FILE__ ) ) . 'templates/grouped.php';
		}
		return $template;
	}

	/**
	 * Get the current subscription for the product group
	 *
	 * @param array $subscriptions User's subscriptions.
	 * @param int   $group_id Parent grouped product ID.
	 * @return WC_Subscription|false
	 */
	private function get_current_subscription( $subscriptions, $group_id ) {

		foreach ( $subscriptions as $subscription ) {
			// Only look at active subscriptions
			if ( ! $subscription->has_status( 'active' ) ) {
				continue;
			}

			foreach ( $subscription->get_items() as $item ) {
				$product = $item->get_product();
				if ( ! $product ) {
					continue;
				}

				$product_id = $product->get_id();
				$parent_id  = $product->get_parent_id();

				// Check if this product is part of the group, either as a child or the group itself
				if ( $group_id === $parent_id || $group_id === $product_id ) {
					return $subscription;
				}
			}
		}

		return false;
	}

	/**
	 * Get current subscription information
	 *
	 * @param int $group_id Parent grouped product ID.
	 * @return array|false
	 */
	public function get_current_subscription_info( $group_id ) {
		if ( ! is_user_logged_in() || ! class_exists( 'WC_Subscriptions' ) ) {
			return false;
		}

		$subscriptions = wcs_get_users_subscriptions();
		// Use 0 as the group ID to match the logic in get_switch_button
		$current_subscription = $this->get_current_subscription( $subscriptions, 0 );

		if ( ! $current_subscription ) {
			return false;
		}

		// Get the current product using the same logic as get_switch_button
		$current_product = false;
		foreach ( $current_subscription->get_items() as $item ) {
			$product = $item->get_product();
			if ( ! $product ) {
				continue;
			}

			// If this product belongs to our group, it's the current one
			if ( 0 === $product->get_parent_id() ) {
				$current_product = $product;
				break;
			}
		}

		if ( ! $current_product ) {
			return false;
		}

		return array(
			'name'  => $current_product->get_name(),
			'price' => $current_product->get_price_html(),
		);
	}

	/**
	 * Handle the switch action
	 */
	public function handle_switch_action() {
		if ( ! isset( $_GET['bssb-switch'] ) || ! isset( $_GET['subscription'] ) || ! isset( $_GET['item'] ) || ! isset( $_GET['switch-to'] ) ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'bssb_switch' ) ) {
			wc_add_notice( __( 'Invalid switch request.', 'better-subscription-switching-buttons' ), 'error' );
			wp_safe_redirect( wc_get_cart_url() );
			exit;
		}

		$subscription_id = absint( $_GET['subscription'] );
		$item_id         = absint( $_GET['item'] );
		$switch_to_id    = absint( $_GET['switch-to'] );

		// Get subscription.
		$subscription = wcs_get_subscription( $subscription_id );
		if ( ! $subscription ) {
			return;
		}

		// Verify user can switch.
		if ( ! current_user_can( 'switch_shop_subscription', $subscription_id ) ) {
			wc_add_notice( __( 'You do not have permission to switch this subscription.', 'better-subscription-switching-buttons' ), 'error' );
			wp_safe_redirect( wc_get_cart_url() );
			exit;
		}

		// Clear cart before switch.
		WC()->cart->empty_cart();

		// Add switch to cart.
		$cart_item_data = array(
			'subscription_switch' => array(
				'subscription_id'        => $subscription_id,
				'item_id'                => $item_id,
				'next_payment'           => $subscription->get_time( 'next_payment' ),
				'upgraded_or_downgraded' => 'switched',
			),
		);

		WC()->cart->add_to_cart( $switch_to_id, 1, 0, array(), $cart_item_data );

		// Redirect to checkout.
		wp_safe_redirect( wc_get_checkout_url() );
		exit;
	}

	/**
	 * Generate switch button HTML
	 *
	 * @param WC_Product $product Product object.
	 * @return string
	 */
	public function get_switch_button( $product ) {
		if ( ! is_user_logged_in() || ! class_exists( 'WC_Subscriptions' ) ) {
			return '';
		}

		$subscriptions        = wcs_get_users_subscriptions();
		$current_subscription = $this->get_current_subscription( $subscriptions, $product->get_parent_id() );

		if ( ! $current_subscription ) {
			return '';
		}

		$switch_url   = $this->get_switch_url( $current_subscription, $product );
		$button_text  = $this->get_button_text( $current_subscription, $product );
		$button_class = $this->get_button_class( $current_subscription, $product );

		return sprintf(
			'<a href="%s" class="button %s">%s</a>',
			esc_url( $switch_url ),
			esc_attr( $button_class ),
			esc_html( $button_text )
		);
	}

	/**
	 * Get the switch URL for the subscription
	 *
	 * @param WC_Subscription $subscription Current subscription.
	 * @param WC_Product     $new_product Product to switch to.
	 * @return string
	 */
	private function get_switch_url( $subscription, $new_product ) {
		// Get the item we're switching from.
		$items     = $subscription->get_items();
		$item_id   = null;
		$parent_id = $new_product->get_parent_id();

		// Find the item in the subscription that matches our product group.
		foreach ( $items as $item ) {
			$product = $item->get_product();
			if ( $product && $product->get_parent_id() === $parent_id ) {
				$item_id = $item->get_id();
				break;
			}
		}

		if ( ! $item_id ) {
			return '#';
		}

		return add_query_arg(
			array(
				'bssb-switch'  => '1',
				'subscription' => $subscription->get_id(),
				'item'         => $item_id,
				'switch-to'    => $new_product->get_id(),
				'_wpnonce'     => wp_create_nonce( 'bssb_switch' ),
			),
			home_url( '/' )
		);
	}

	/**
	 * Get button text based on subscription status
	 *
	 * @param WC_Subscription $subscription Current subscription.
	 * @param WC_Product     $new_product Product to switch to.
	 * @return string
	 */
	private function get_button_text( $subscription, $new_product ) {
		$current_product_id = 0;
		foreach ( $subscription->get_items() as $item ) {
			$current_product_id = $item->get_product_id();
			break;
		}

		if ( $current_product_id === $new_product->get_id() ) {
			return esc_html__( 'Your current subscription', 'better-subscription-switching-buttons' );
		}

		// Get the price string without HTML
		$price = wp_strip_all_tags( $new_product->get_price_html() );

		return sprintf(
			/* translators: %s: Subscription price */
			esc_html_x( '%s', 'Switch button text with price', 'better-subscription-switching-buttons' ),
			$price
		);
	}

	/**
	 * Get button class based on subscription status
	 *
	 * @param WC_Subscription $subscription Current subscription.
	 * @param WC_Product     $new_product Product to switch to.
	 * @return string
	 */
	private function get_button_class( $subscription, $new_product ) {
		$current_product_id = 0;
		foreach ( $subscription->get_items() as $item ) {
			$current_product_id = $item->get_product_id();
			break;
		}

		if ( $current_product_id === $new_product->get_id() ) {
			return 'bssb-button-current';
		}

		return 'bssb-button-switch';
	}

	/**
	 * Enqueue styles
	 */
	public function enqueue_styles() {
		if ( ! is_product() ) {
			return;
		}

		wp_enqueue_style(
			'bssb-styles',
			plugins_url( 'assets/css/bssb-styles.css', dirname( __FILE__ ) ),
			array(),
			'1.0.0'
		);
	}
}
