<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PB_Wishlist_Report
 *
 * A simple admin screen showing which products are most-wishlisted
 * across all customers — useful signal for restocking or marketing,
 * even for items that aren't necessarily selling yet.
 */
class PB_Wishlist_Report {

	private static $instance = null;
	const TRANSIENT_KEY = 'auto_commerce_wishlist_report_data';

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_report_page' ) );
	}

	public function add_report_page() {
		add_submenu_page(
			'woocommerce',
			__( 'Most Wishlisted', 'auto-commerce-wishlist' ),
			__( 'Most Wishlisted', 'auto-commerce-wishlist' ),
			'manage_woocommerce',
			'auto-commerce-wishlist-report',
			array( $this, 'render_report_page' )
		);
	}

	/**
	 * Scans all users' wishlist meta and tallies product counts.
	 * Cached for 6 hours since this can be a heavier query on large stores.
	 */
	private function get_report_data() {

		$cached = get_transient( self::TRANSIENT_KEY );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;

		$meta_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s",
				PB_Wishlist::META_KEY
			)
		);

		$counts = array();

		foreach ( $meta_rows as $row ) {
			$items = maybe_unserialize( $row->meta_value );
			if ( ! is_array( $items ) ) {
				continue;
			}
			foreach ( array_keys( $items ) as $product_id ) {
				$product_id = absint( $product_id );
				if ( ! $product_id ) {
					continue;
				}
				$counts[ $product_id ] = isset( $counts[ $product_id ] ) ? $counts[ $product_id ] + 1 : 1;
			}
		}

		arsort( $counts );
		$counts = array_slice( $counts, 0, 25, true );

		set_transient( self::TRANSIENT_KEY, $counts, 6 * HOUR_IN_SECONDS );

		return $counts;
	}

	public function render_report_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$counts = $this->get_report_data();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Most Wishlisted Products', 'auto-commerce-wishlist' ); ?></h1>
			<p><?php esc_html_e( 'Products customers have saved to their wishlist most often, across all accounts. Updated every 6 hours.', 'auto-commerce-wishlist' ); ?></p>

			<?php if ( empty( $counts ) ) : ?>
				<p><?php esc_html_e( 'No wishlist activity yet.', 'auto-commerce-wishlist' ); ?></p>
			<?php else : ?>
				<table class="widefat striped" style="max-width:800px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Product', 'auto-commerce-wishlist' ); ?></th>
							<th><?php esc_html_e( 'Times Wishlisted', 'auto-commerce-wishlist' ); ?></th>
							<th><?php esc_html_e( 'Stock Status', 'auto-commerce-wishlist' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $counts as $product_id => $count ) :
						$product = wc_get_product( $product_id );
						if ( ! $product ) {
							continue;
						}
						?>
						<tr>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link( $product_id ) ); ?>">
									<?php echo esc_html( $product->get_name() ); ?>
								</a>
							</td>
							<td><?php echo absint( $count ); ?></td>
							<td><?php echo $product->is_in_stock() ? esc_html__( 'In Stock', 'auto-commerce-wishlist' ) : '<strong style="color:#b32d2e;">' . esc_html__( 'Out of Stock', 'auto-commerce-wishlist' ) . '</strong>'; ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}
