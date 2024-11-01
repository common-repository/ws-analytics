<?php
/**
 * Plugin Name: WS Analytics
 * Plugin URI: https://wordpress.org/plugins/ws-analytics
 * Description: Output your Google Analytics tracking code to your visitor everywhere on your website.
 * Version: 1.0
 * Author: WebShouters
 * Author URI: https://www.webshouters.com/
 * Requires at least: 4.4.1
 * Tested up to: 4.7
 *
 * Text Domain: ws-analytics
 * Domain Path: /languages/
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WS_Analytics' ) ) {
	/**
	 * Main WS_Analytics Class
	 *
	 * Contains the main functions for WS_Analytics
	 *
	 * @class WS_Analytics
	 * @version 1.0.0
	 * @since 1.0.0
	 */
	class WS_Analytics {

		/**
		 * @var string
		 */
		public $version = '1.0';

		/**
		 * @var WS Analytics The single instance of the class
		 */
		protected static $_instance = null;

		/**
		 * Main WS Analytics Instance
		 *
		 * Ensures only one instance of WS Analytics is loaded or can be loaded.
		 *
		 * @static
		 * @see WPM()
		 * @return WS Analytics - Main instance
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * WS Analytics Constructor.
		 */
		public function __construct() {

			$this->init_hooks();
		}

		/**
		 * Hook into actions and filters
		 */
		private function init_hooks() {
			add_action( 'init', array( $this, 'init' ), 0 );

			// head hooks
			add_action( 'wp_head', array( $this, 'analytics_tracking_code' ) );

			// admin hooks
			add_action( 'admin_menu', array( $this, 'add_menu' ) );
			add_action( 'admin_init', array( $this, 'options' ) );

			// Plugin row meta
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'settings_action_links' ) );
		}

		/**
		 * Init WS Analytics when WordPress Initialises.
		 */
		public function init() {
			// Set up localisation
			$this->load_plugin_textdomain();
		}

		/**
		 * Add admin menu
		 */
		public function add_menu() {

			add_management_page( esc_html( 'Analytics', 'ws-analytics' ), esc_html( 'Analytics', 'ws-analytics' ), 'activate_plugins', 'ws-analytics', array( $this, 'analytics_settings' ) , 'dashicons-chart-line' );
		}

		/**
		 * Register options fields
		 */
		public function options() {

			register_setting( 'ws-analytics-options', 'WS_Analytics_settings', array( $this, 'settings_validate' ) );
			add_settings_section( 'ws-analytics-options', '', array( $this, 'section_intro' ), 'ws-analytics-options' );
			add_settings_field( 'is_active', esc_html__( 'Is Active', 'ws-analytics' ), array( $this, 'section_analytics_active' ), 'ws-analytics-options', 'ws-analytics-options' );
			add_settings_field( 'code', esc_html__( 'Your Google Analytics ID', 'ws-analytics' ), array( $this, 'section_analytics_code' ), 'ws-analytics-options', 'ws-analytics-options' );
		}

		/**
		 * Validate options
		 *
		 * @return string
		 */
		public function settings_validate( $input ) {

			$input['google_analytics_id'] = sanitize_text_field( $input['google_analytics_id'] );
			return $input;

		}

		/**
		 * Intro used for debug and JS
		 */
		public function section_intro() {
			/*global $options;
			echo "<pre>";
			print_r( get_option( 'WS_Analytics_settings' ) );
			echo "</pre>";*/
		}

		/**
		 * Skin
		 */
		public function section_analytics_code() {
			?>
			<input type="text" placeholder="UA-#######-#" value="<?php echo esc_attr( $this->get_option( 'google_analytics_id' ) ); ?>" name="WS_Analytics_settings[google_analytics_id]">
			<?php
		}

		public function section_analytics_active() {
			$is_active = esc_attr( $this->get_option( 'google_analytics_active' ) );
			?>
			<input type="checkbox" value="1" name="WS_Analytics_settings[google_analytics_active]" <?php if($is_active == TRUE): ?> checked <?php endif; ?>>
			<?php
		}

		/**
		 * Get player options
		 *
		 * @param string $value
		 * @return string
		 */
		public function get_option( $value ) {
			global $options;
			$settings = get_option( 'WS_Analytics_settings' );

			if ( isset( $settings[ $value ] ) ) {
				return $settings[ $value ];
			}
		}

		/**
		 * Print options form
		 *
		 * @return string
		 */
		public function analytics_settings() {
			?>
			<div class="wrap">
				<div id="icon-options-general" class="icon32"></div>
				<h2><?php esc_html_e( 'WS Google Analytics', 'ws-analytics' ); ?></h2>
				<?php if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) { ?>
				<div id="setting-error-settings_updated" class="updated settings-error">
					<p><strong><?php esc_html_e( 'Settings saved.', 'ws-analytics' ); ?></strong></p>
				</div>
				<?php } ?>
				<form action="options.php" method="post">
					<?php settings_fields( 'ws-analytics-options' ); ?>
					<?php do_settings_sections( 'ws-analytics-options' ); ?>
					<p class="submit">
						<input name="save" type="submit" class="button-primary" value="<?php esc_html_e( 'Save Changes', 'ws-analytics' ); ?>">
					</p>
				</form>
			</div>
			<?php
		}

		/**
		 * Output analytics code in the page footer
		 *
		 * @return int
		 */
		public function analytics_tracking_code() {

			$is_active = esc_js( $this->get_option( 'google_analytics_active' ) );
			$google_analytics_id = esc_js( $this->get_option( 'google_analytics_id' ) );

			if ( !empty($google_analytics_id) && ($is_active == TRUE) ) {
				$tracking_code = "<script>
				  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
				  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
				  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
				  })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

				  ga('create', '$google_analytics_id', 'auto');
				  ga('send', 'pageview');

				</script>";

				if ( 
					$tracking_code
					&& ! is_user_logged_in() 
				) {
					echo $tracking_code;
				}
			}
		}

		/**
		 * Loads the plugin text domain for translation
		 */
		public function load_plugin_textdomain() {

			$domain = 'ws-analytics';
			$locale = apply_filters( 'ws-analytics', get_locale(), $domain );
			load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
			load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Add settings link in plugin page
		 */
		public function settings_action_links( $links ) {
			$setting_link = array(
				'<a href="' . admin_url( 'tools.php?page=ws-analytics' ) . '">' . esc_html__( 'Settings', 'ws-analytics' ) . '</a>',
			);
			return array_merge( $links, $setting_link );
		}
	}
} // endif class exists

/**
 * Returns the main instance of WSNLTCS to prevent the need to use globals.
 *
 * @return WS_Analytics
 */
function WSNLTCS() {
	return WS_Analytics::instance();
}

WSNLTCS(); // Go