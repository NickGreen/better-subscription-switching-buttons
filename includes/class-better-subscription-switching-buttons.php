<?php
/**
 * Main plugin class
 *
 * @package BetterSubscriptionSwitchingButtons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class
 */
class Better_Subscription_Switching_Buttons {

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * The single instance of the class
	 *
	 * @var Better_Subscription_Switching_Buttons
	 */
	protected static $instance = null;

	/**
	 * Subscription Switcher instance
	 *
	 * @var BSSB_Subscription_Switcher
	 */
	public $subscription_switcher;

	/**
	 * Main plugin instance
	 *
	 * @return Better_Subscription_Switching_Buttons
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->define_constants();
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Define plugin constants
	 */
	private function define_constants() {
		$this->define( 'BSSB_VERSION', $this->version );
		$this->define( 'BSSB_PLUGIN_DIR', plugin_dir_path( dirname( __FILE__ ) ) );
		$this->define( 'BSSB_PLUGIN_URL', plugin_dir_url( dirname( __FILE__ ) ) );
	}

	/**
	 * Define constant if not already defined
	 *
	 * @param string $name Constant name.
	 * @param string $value Constant value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Initialize plugin
	 */
	public function init() {
		// Check dependencies.
		if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'WC_Subscriptions' ) ) {
			add_action( 'admin_notices', array( $this, 'dependency_notice' ) );
			return;
		}

		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required files
	 */
	private function includes() {
		require_once BSSB_PLUGIN_DIR . 'includes/class-bssb-subscription-switcher.php';
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		$this->subscription_switcher = new BSSB_Subscription_Switcher();

		global $bssb_subscription_switcher;
		$bssb_subscription_switcher = $this->subscription_switcher;
	}

	/**
	 * Display dependency notice
	 */
	public function dependency_notice() {
		?>
		<div class="error">
			<p>
				<?php
				esc_html_e(
					'Better Subscription Switching Buttons requires WooCommerce and WooCommerce Subscriptions to be installed and active.',
					'better-subscription-switching-buttons'
				);
				?>
			</p>
		</div>
		<?php
	}
}
