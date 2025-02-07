<?php
defined( 'ABSPATH' ) || exit;

global $product, $bssb_subscription_switcher;

if ( is_user_logged_in() && class_exists( 'WC_Subscriptions' ) ) {
	$current_subscription_info = $bssb_subscription_switcher->get_current_subscription_info( $product->get_id() );

	if ( $current_subscription_info ) : ?>
		<div class="bssb-current-plan-notice">
			<?php
			printf(
				/* translators: 1: Product name, 2: Subscription price */
				esc_html__( 'You are currently subscribed to %1$s paying %2$s', 'better-subscription-switching-buttons' ),
				'<strong>' . esc_html( $current_subscription_info['name'] ) . '</strong>',
				wp_kses_post( $current_subscription_info['price'] )
			);
			?>
		</div>
		<?php
	endif;
}
?>

<h3 class="bssb-table-header">
	<?php esc_html_e( 'Remain on your current subscription or switch to a different one', 'better-subscription-switching-buttons' ); ?>
</h3>

<table cellspacing="0" class="woocommerce-grouped-product-list group_table">
	<tbody>
		<?php
		foreach ( $product->get_children() as $child_id ) {
			$child_product = wc_get_product( $child_id );
			if ( ! $child_product || ! $child_product->is_purchasable() ) {
				continue;
			}
			?>
			<tr id="product-<?php echo esc_attr( $child_product->get_id() ); ?>" class="woocommerce-grouped-product-list-item">
				<td class="woocommerce-grouped-product-list-item__label">
					<?php echo wp_kses_post( $child_product->get_name() ); ?>
				</td>
				<td class="woocommerce-grouped-product-list-item__button">
					<?php echo wp_kses_post( $bssb_subscription_switcher->get_switch_button( $child_product ) ); ?>
				</td>
			</tr>
			<?php
		}
		?>
	</tbody>
</table>
