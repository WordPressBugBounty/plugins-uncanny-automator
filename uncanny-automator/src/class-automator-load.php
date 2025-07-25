<?php
/**
 * Automator_Load
 *
 * Boot loads Automator plugin.
 *
 * @class   Automator_Load
 * @since   3.0
 * @version 3.0
 * @author  Saad S.
 * @package Uncanny_Automator
 */

namespace Uncanny_Automator;

use Uncanny_Automator\Services\CLI\Logs\Prune_Command;

/**
 * Class Automator_Load
 *
 * @package Uncanny_Automator
 */
class Automator_Load {

	/**
	 * The instance of the class
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      Object
	 */
	public static $instance = null;

	/**
	 * @var array
	 */
	public static $core_class_inits = array();

	/**
	 * @var array
	 */
	public static $integrations = array();

	/**
	 * @var array
	 */
	public static $active_integrations = array();

	/**
	 * @var bool
	 */
	public static $any_recipes_active = false;
	/**
	 * @var bool
	 */
	public static $is_admin_sect = false;

	/**
	 * class constructor
	 */
	public function __construct() {

		add_action( 'upgrader_process_complete', array( $this, 'flag_last_updated' ), 20, 2 );

		// Load text domain
		add_action(
			'init',
			function () {
				Automator()->automator_load_textdomain();
			}
		);

		if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 'favicon' ) ) {
			// bail out if it's favicon.ico
			return;
		}

		self::$is_admin_sect = is_admin();

		// Check if any recipes are active;
		self::$any_recipes_active = $this->any_active_recipe();

		// Load both admin & non-admin files.
		add_filter( 'automator_core_files', array( $this, 'global_classes' ) );

		// Load Admin only files.
		add_filter( 'automator_core_files', array( $this, 'admin_only_classes' ) );

		// Load Custom Post Types only files.
		add_filter( 'automator_core_files', array( $this, 'custom_post_types_classes' ) );

		// Load Gutenberg Block files.
		add_filter( 'automator_core_files', array( $this, 'gutenberg_block_classes' ) );

		// Load non-admin files.
		add_filter( 'automator_core_files', array( $this, 'front_only_classes' ) );

		// Add the pro links utm_r attributes.
		add_action( 'admin_footer', array( $this, 'global_utm_r_links' ) );

		add_action( 'activated_plugin', array( $this, 'automator_activated' ) );

		// Show 'Upgrade to Pro' on plugins page.
		add_filter(
			'plugin_action_links_' . plugin_basename( AUTOMATOR_BASE_FILE ),
			array(
				$this,
				'uo_automator_upgrade_to_pro_link',
			),
			99
		);

		add_action( 'admin_init', array( $this, 'automator_schedule_healthchecks' ) );

		add_action( 'admin_notices', array( $this, 'check_runtime_environment' ) );

		$this->load_automator();

		// Show set-up wizard.
		$this->initiate_setup_wizard();

		// Show upgrade notice from readme.txt.
		if ( self::$is_admin_sect ) {
			add_action(
				'in_plugin_update_message-' . plugin_basename( AUTOMATOR_BASE_FILE ),
				array( $this, 'in_plugin_update_message' ),
				10,
				2
			);
			$this->load_deactivation_survey();
		}

		// Load logs auto prune.
		$this->load_logs_autoremoval();

		// Load restore trigger status.
		$this->load_logs_multiple_trigger_status_restore();

		// Loads all cli commands.
		$this->load_cli_commands();

		// Load AI settings.
		$this->load_ai_settings_admin_post_hooks();

		// Auto-delete user logs.
		add_action( 'deleted_user', array( $this, 'auto_prune_user_logs_handler' ), 10, 3 );
	}

	/**
	 * Loads the AI settings admin post hooks.
	 *
	 * @return void
	 */
	public function load_ai_settings_admin_post_hooks() {
		Core\Lib\AI\Adapters\Integration\AI_Settings::register_wp_hooks();
	}

	/**
	 * Loads all WP-CLI Commands.
	 *
	 * @return void
	 */
	public function load_cli_commands() {

		// Only loads in WP_CLI.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$dependecy      = new Prune_Logs( false );
			$prune_commands = new Prune_Command( $dependecy );
			$prune_commands->register_command();
		}
	}

	/**
	 * Flags the last automator pro update time.
	 *
	 * @return void
	 * @var string[] $options
	 *
	 * @var object $upgrader_object
	 */
	public function flag_last_updated( $upgrader_object, $options ) {
		// Bail if the options are not set.
		if ( ! isset( $options['action'] ) || ! isset( $options['type'] ) ) {
			return;
		}

		// Check if it's a plugin update.
		if ( 'update' !== $options['action'] || 'plugin' !== $options['type'] ) {
			return;
		}

		// The plugins being updated are stored in the 'plugins' key of the options array.
		if ( ! isset( $options['plugins'] ) || ! is_array( $options['plugins'] ) ) {
			return;
		}

		// The path to the specific plugin to check for, relative to the wp-content/plugins directory.
		$specific_plugin_path = plugin_basename( AUTOMATOR_BASE_FILE );

		foreach ( $options['plugins'] as $plugin_path ) {
			if ( $plugin_path !== $specific_plugin_path ) {
				continue;
			}
			// Update an option with the current time for the specific plugin
			automator_update_option( 'automator_last_updated', current_time( 'mysql' ) );
			break; // No need to continue the loop
		}
	}

	/**
	 * Checks runtime environment.
	 *
	 * - Displays some message on web assembly.
	 *
	 * @return void
	 */
	public function check_runtime_environment() {

		if ( is_array( $_SERVER ) && isset( $_SERVER['SERVER_SOFTWARE'] ) && 'php.wasm' === strtolower( trim( $_SERVER['SERVER_SOFTWARE'] ) ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash

			echo '<div class="notice notice-error"><p>'
				. esc_html_x( 'Uncanny Automator cannot currently run in WP Playground environments because Playground cannot support custom tables, cURL or SSL PHP functions. Please consider trying the free Uncanny Automator plugin in your own environment instead.', 'Uncanny Automator', 'uncanny-automator' ) // phpcs:ignore WordPress.WhiteSpace.PrecisionAlignment.Found
				. '</p></div>'; // phpcs:ignore WordPress.WhiteSpace.PrecisionAlignment.Found
		}
	}

	/**
	 * @return void
	 */
	public function load_deactivation_survey() {

		require_once UA_ABSPATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'deactivation-survey' . DIRECTORY_SEPARATOR . 'class-automator-deactivation-survey.php';

		add_action(
			'admin_menu',
			function () {
				// See Usage instructions below for more information.
				define(
					'AUTOMATOR_DEACTIVATION_SURVEY_URL',
					'https://survey.automatorplugin.com/wp-json/am-deactivate-survey/v1/deactivation-data/'
				);
				new \Automator_Deactivation_Survey(
					'Uncanny Automator',
					'uncanny-automator'
				);
			},
			100
		);
	}

	/**
	 * Loads logs auto removal class to register the hooks.
	 *
	 * @return void
	 */
	public function load_logs_autoremoval() {
		( new \Uncanny_Automator\Services\Logger_Auto_Removal() )->register_hooks();
	}

	/**
	 * Lods log multiple trigger status retoration.
	 *
	 * @return void
	 */
	public function load_logs_multiple_trigger_status_restore() {
		( new \Uncanny_Automator\Services\Multiple_Triggers_Restore_Failed_Logs() )->restore_once();
	}

	/**
	 * @param $plugin
	 */
	public function automator_activated( $plugin ) {

		// If it's not Automator, bail
		if ( plugin_basename( AUTOMATOR_BASE_FILE ) !== $plugin ) {
			return;
		}

		// If disbaled by filter
		if ( false === apply_filters( 'automator_on_activate_redirect_to_dashboard', true ) ) {
			return;
		}

		// Check if the current user can activate plugin
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// If activated via AJAX or REST
		if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		// If activated via CRON
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return;
		}

		// If not defined cli
		if ( function_exists( 'php_sapi_name' ) && php_sapi_name() === 'cli' ) {
			return;
		}

		// Bail if from Codeception WPTestCase.
		if ( class_exists( '\Codeception\TestCase\WPTestCase' ) ) {
			return;
		}

		// Bail if in WP CLI mode.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return;
		}

		// If HTTP_USER_AGENT is missing for an automated script
		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return;
		}

		$checked = filter_input( INPUT_POST, 'checked', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		// Bail if bulked activated and there are more than 1 plugin.
		if ( is_array( $checked ) && count( $checked ) >= 2 ) {
			return;
		}

		// Bail if not from `wp-admin/plugins.php` (e.g coming from an ajax, or unit test)
		if ( ! check_admin_referer( 'activate-plugin_' . $plugin ) ) {
			return;
		}

		// If the site is not previously connected, let's redirect to Setup Wizard
		if ( class_exists( '\Uncanny_Automator\Api_Server' ) && empty( Api_Server::get_license_key() ) ) {
			wp_redirect( esc_url_raw( admin_url( 'admin.php?page=uncanny-automator-setup-wizard' ) ) ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
			exit();
		}

		// Else, redirect back to Dashboard
		wp_redirect( esc_url_raw( admin_url( 'admin.php?page=uncanny-automator-dashboard' ) ) ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit();
	}

	/**
	 * Callback function to `plugin_action_links_{$path}` to add our 'Upgrade to Pro' link.
	 *
	 * @param array $links The accepted argument.
	 *
	 * @return array The links.
	 */
	public function uo_automator_upgrade_to_pro_link( $links ) {

		// Check if Automator Pro is not active.
		if ( ! defined( 'AUTOMATOR_PRO_FILE' ) ) {

			$link = 'https://automatorplugin.com/pricing/?utm_source=uncanny_automator&utm_medium=plugins_page&utm_content=update_to_pro';

			$settings_link = sprintf( '<a href="%s" target="_blank" style="color: rgb(0, 163, 42); font-weight: 700;">%s</a>', $link, esc_html__( 'Upgrade to Pro', 'uncanny-automator' ) );

			array_unshift( $links, $settings_link );

		}

		return $links;
	}

	/**
	 *
	 */
	public function load_automator() {
		// If it's not required to load automator, bail
		if ( false === LOAD_AUTOMATOR ) {
			return;
		}

		do_action( 'automator_before_configure' );

		// Load Utilities
		$this->initialize_utilities();

		// Load Configuration
		$this->initialize_automator_db();

		// Load the core files
		$this->initialize_core_automator();

		// Load the services.
		$this->load_services();

		// Load system overwrites.
		$this->load_overwrites();

		do_action( 'automator_configuration_complete' );

		add_action( 'wpforms_loaded', array( $this, 'wpforms_integration' ) );
	}

	/**
	 * Load constant overwrites or early filters.
	 *
	 * Adds filters for enabling debug mode and determining whether Automator should load.
	 *
	 * @return void
	 */
	public function load_overwrites() {
		add_filter( 'automator_should_enable_debug_mode', array( $this, 'should_enable_debug_mode_handler' ) );
		add_filter( 'automator_should_load_automator', array( $this, 'should_load_automator' ) );
	}

	/**
	 * Check if debug mode should be enabled.
	 *
	 * @return bool True if debug mode is enabled, false otherwise.
	 */
	public function should_enable_debug_mode_handler() {
		return ! empty( automator_get_option( 'automator_settings_debug_notices_enabled', false ) );
	}

	/**
	 * Check if Automator should load.
	 *
	 * @return bool True if Automator should load, false otherwise.
	 */
	public function should_load_automator() {
		return filter_var( automator_get_option( 'load_automator', true ), FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Loads the RESTful API services.
	 *
	 * @since 4.15
	 */
	public function load_services() {

		// Rest services.
		add_action(
			'rest_api_init',
			function ( \WP_REST_Server $wp_rest_server ) {
				// Register our routes when 'rest_api_init' is called.
				// Only require the route when needed.
				require_once UA_ABSPATH . 'src/core/services/rest-routes.php';
				Rest\Log_Endpoint\rest_api_init( $wp_rest_server );
			},
			10,
			1
		);

		$this->register_fields_logger();

		require_once UA_ABSPATH . 'src/core/services/logger/async-logger.php';
	}

	/**
	 * Registers the fields logger.
	 *
	 * @return void
	 */
	public function register_fields_logger() {

		require_once UA_ABSPATH . 'src/core/services/logger.php';

		\Uncanny_Automator\Logger\fields_logger_register_listeners();
	}

	/**
	 *
	 */
	public function wpforms_integration() {
		if ( ! class_exists( 'WPForms' ) ) {
			return;
		}

		if ( version_compare( WPFORMS_VERSION, '1.7.0', '<' ) ) {
			return;
		}

		add_filter(
			'wpforms_load_providers',
			function ( $providers ) {
				$providers[] = 'uncanny-automator';

				return $providers;
			},
			99,
			1
		);

		add_action(
			'wpforms_load_uncanny-automator_provider',
			function () {
				require_once UA_ABSPATH . 'src/core/admin/class-wpforms-provider.php';
			},
			99
		);
	}

	/**
	 * Initialize static singleton class that has shared functions and variables
	 *
	 * @since 1.0.0
	 */
	public function initialize_utilities() {

		require_once UA_ABSPATH . 'src/core/class-utilities.php';
		Utilities::get_instance();
	}

	/**
	 * Initialize static singleton class that configures all constants, utilities variables and handles
	 * activation/deactivation
	 *
	 * @since 1.0.0
	 */
	public function initialize_automator_db() {

		include_once dirname( AUTOMATOR_BASE_FILE ) . '/src/core/class-automator-db.php';

		$config_instance = Automator_DB::get_instance();

		register_activation_hook(
			AUTOMATOR_BASE_FILE,
			array(
				Automator_DB::class,
				'activation',
			)
		);

		$db_version = automator_get_option( 'uap_database_version', null );

		if ( null === $db_version || (string) AUTOMATOR_DATABASE_VERSION !== (string) $db_version ) {
			Automator_DB::activation();
			$config_instance->mysql_8_auto_increment_fix();
		}

		if ( (string) AUTOMATOR_DATABASE_VIEWS_VERSION !== (string) automator_get_option( 'uap_database_views_version', 0 ) ) {
			$config_instance->automator_generate_views();
		}
	}

	/**
	 *
	 */
	public function initialize_core_automator() {
		do_action( 'automator_before_init' );

		$classes = apply_filters( 'automator_core_files', array() );

		if ( empty( $classes ) ) {
			return;
		}

		// only load if it's admin
		$this->load_traits();

		foreach ( $classes as $class_name => $file ) {
			if ( ! is_file( $file ) ) {
				continue;
			}
			require_once $file;
			$class                                 = __NAMESPACE__ . '\\' . $class_name;
			self::$core_class_inits[ $class_name ] = new $class();
		}

		do_action( 'automator_after_init' );
	}

	/**
	 * Creates singleton instance of class
	 *
	 * @return Automator_Load $instance The Automator_Load Class
	 * @since 1.0.0
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @param $args
	 * @param $response
	 */
	public function in_plugin_update_message( $args, $response ) {
		$upgrade_notice = '';
		if ( isset( $response->upgrade_notice ) && ! empty( $response->upgrade_notice ) ) {
			$upgrade_notice .= '<div class="ua_plugin_upgrade_notice">';
			$upgrade_notice .= sprintf( '<strong>%s</strong>', esc_html__( 'Heads up!', 'uncanny-automator' ) );
			$upgrade_notice .= preg_replace( '~\[([^\]]*)\]\(([^\)]*)\)~', '<a href="${2}">${1}</a>', $response->upgrade_notice );
			$upgrade_notice .= '</div>';
		}

		echo wp_kses_post(
			apply_filters(
				'uap_in_plugin_update_message',
				$upgrade_notice ? '</p>' . wp_kses_post( $upgrade_notice ) . '<p class="dummy">' : '',
				$args
			)
		);
	}

	/**
	 * @param array $classes
	 *
	 * @return array|mixed
	 */
	public function admin_only_classes( $classes = array() ) {
		/**
		 * Admin.
		 */
		if ( ! self::$is_admin_sect && ! Automator()->helpers->recipe->is_automator_ajax() ) {
			return $classes;
		}

		do_action( 'automator_before_admin_init' );

		$classes['Admin_Menu']                     = UA_ABSPATH . 'src/core/admin/class-admin-menu.php';
		$classes['Prune_Logs']                     = UA_ABSPATH . 'src/core/admin/class-prune-logs.php';
		$classes['Admin_Logs']                     = UA_ABSPATH . 'src/core/admin/admin-logs/admin-logs.php';
		$classes['Admin_Tools']                    = UA_ABSPATH . 'src/core/admin/admin-tools/admin-tools.php';
		$classes['Admin_Settings']                 = UA_ABSPATH . 'src/core/admin/admin-settings/admin-settings.php';
		$classes['Pro_Upsell']                     = UA_ABSPATH . 'src/core/admin/pro-upgrade/class-pro-upsell.php';
		$classes['Addons']                         = UA_ABSPATH . 'src/core/admin/addons/class-addons.php';
		$classes['Automator_Review']               = UA_ABSPATH . 'src/core/admin/class-automator-review.php';
		$classes['Automator_Notifications']        = UA_ABSPATH . 'src/core/admin/notifications/notifications.php';
		$classes['Automator_Tooltip_Notification'] = UA_ABSPATH . 'src/core/admin/tooltip-notification/class-tooltip-notification.php';
		$classes['Automator_Tooltip_48hr']         = UA_ABSPATH . 'src/core/admin/tooltip-notification/tooltips/class-create-recipe-reminder.php';
		$classes['Admin_Template_Library']         = UA_ABSPATH . 'src/core/admin/class-admin-template-library.php';

		$classes['Api_Log'] = UA_ABSPATH . 'src/core/admin/api-log/class-api-log.php';

		$classes['Add_User_Recipe_Type'] = UA_ABSPATH . 'src/core/classes/class-add-user-recipe-type.php';
		if ( ! defined( 'AUTOMATOR_PRO_FILE' ) ) {
			$classes['Add_Anon_Recipe_Type'] = UA_ABSPATH . 'src/core/anon/class-add-anon-recipe-type.php';
		}
		do_action( 'automator_after_admin_init' );

		/**
		 * Automator Custom Post Types.
		 */
		//$classes = $this->custom_post_types_classes( $classes );

		/**
		 * Activity Stream / Logs.
		 */
		$classes = $this->activity_stream_classes( $classes );

		/**
		 * Classes.
		 */
		do_action( 'automator_before_classes_init' );

		$classes['Populate_From_Query'] = UA_ABSPATH . 'src/core/classes/class-populate-from-query.php';

		do_action( 'automator_after_classes_init' );

		return $classes;
	}

	/**
	 * @param array $classes
	 *
	 * @return array|mixed
	 */
	public function custom_post_types_classes( $classes = array() ) {

		if ( ! self::$any_recipes_active && ! self::$is_admin_sect && ! Automator()->helpers->recipe->is_automator_ajax() ) {
			return $classes;
		}

		do_action( 'automator_before_automator_post_types_init' );

		$classes['Recipe_Post_Type']      = UA_ABSPATH . 'src/core/automator-post-types/uo-recipe/class-recipe-post-type.php';
		$classes['Recipe_Post_Metabox']   = UA_ABSPATH . 'src/core/automator-post-types/uo-recipe/class-recipe-post-metabox.php';
		$classes['Recipe_Post_Utilities'] = UA_ABSPATH . 'src/core/automator-post-types/uo-recipe/class-recipe-post-utilities.php';
		$classes['Triggers_Post_Type']    = UA_ABSPATH . 'src/core/automator-post-types/uo-trigger/class-triggers-post-type.php';
		$classes['Actions_Post_Type']     = UA_ABSPATH . 'src/core/automator-post-types/uo-action/class-actions-post-type.php';
		$classes['Closures_Post_Type']    = UA_ABSPATH . 'src/core/automator-post-types/uo-closure/class-closures-post-type.php';
		$classes['Automator_Taxonomies']  = UA_ABSPATH . 'src/core/automator-post-types/uo-taxonomies/class-automator-taxonomies.php';

		do_action( 'automator_after_automator_post_types_init' );

		return $classes;
	}

	/**
	 * @param array $classes
	 *
	 * @return array|mixed
	 */
	public function gutenberg_block_classes( $classes = array() ) {

		do_action( 'automator_before_blocks_init' );

		$classes['Blocks'] = UA_ABSPATH . 'src/core/blocks/class-blocks.php';

		do_action( 'automator_after_blocks_init' );

		return $classes;
	}

	/**
	 * @param array $classes
	 *
	 * @return array|mixed
	 */
	public function activity_stream_classes( $classes = array() ) {

		do_action( 'automator_before_activity_stream_init' );

		$classes['Activity_Log'] = UA_ABSPATH . 'src/core/admin/class-activity-log.php';

		do_action( 'automator_after_activity_stream_init' );

		return $classes;
	}

	/**
	 * @param array $classes
	 *
	 * @return array|mixed
	 */
	public function front_only_classes( $classes = array() ) {

		$classes['Actionify_Triggers'] = UA_ABSPATH . 'src/core/classes/class-actionify-triggers.php';

		return $classes;
	}

	/**
	 * @param array $classes
	 *
	 * @return array
	 */
	public function global_classes( $classes = array() ) {
		/**
		 * Class autoloader.
		 */
		do_action( 'automator_before_autoloader' );

		// Webhooks
		$classes['Automator_Send_Webhook_Ajax_Handler'] = UA_ABSPATH . 'src/core/lib/webhooks/class-automator-send-webhook-ajax-handler.php';
		$classes['Recipe_Post_Rest_Api']                = UA_ABSPATH . 'src/core/automator-post-types/uo-recipe/class-recipe-post-rest-api.php';
		$classes['Background_Actions']                  = UA_ABSPATH . 'src/core/classes/class-background-actions.php';
		$classes['Calculation_Token']                   = UA_ABSPATH . 'src/core/classes/class-calculation-token.php';
		$classes['Copy_Recipe_Parts']                   = UA_ABSPATH . 'src/core/admin/class-copy-recipe-parts.php';
		$classes['Export_Recipe']                       = UA_ABSPATH . 'src/core/admin/class-export-recipe.php';
		$classes['Import_Recipe']                       = UA_ABSPATH . 'src/core/admin/class-import-recipe.php';
		$classes['Pricing_Plan_Resolver']               = UA_ABSPATH . 'src/core/admin/class-pricing-plan-resolver.php';

		require_once UA_ABSPATH . 'src/core/classes/class-api-server.php';

		// Load migrations
		$this->load_migrations();

		// Only initialize classes if there're any active recipes OR if user is editing recipe
		$classes = $this->maybe_initialize_automator( $classes );

		do_action( 'automator_after_autoloader' );

		return $classes;
	}

	/**
	 * @param $classes
	 *
	 * @return mixed
	 */
	public function maybe_initialize_automator( $classes ) {
		// Check if running unit-tests
		$unit_tests = is_automator_running_unit_tests();

		// check if it's REST endpoint call or running unit tests
		if ( ! Automator()->helpers->recipe->is_automator_ajax() && ! $unit_tests ) {

			$classes['Usage_Reports'] = UA_ABSPATH . 'src/core/classes/class-usage-reports.php';

			// If there are no active recipes && is not an admin page -- bail
			if ( ! self::$is_admin_sect && ! self::$any_recipes_active ) {
				return $classes;
			}

			global $pagenow;

			$load_on_pages = array(
				'post.php',
				'edit.php',
				'options.php',
			);

			if ( 'edit.php' === $pagenow && ( ! automator_filter_has_var( 'post_type' ) || 'uo-recipe' !== automator_filter_input( 'post_type' ) ) && ! self::$any_recipes_active ) {
				return $classes;
			}

			// if current page is not an edit screen, and none of the recipes are published, return
			if ( ! self::$any_recipes_active && ! in_array( $pagenow, $load_on_pages, true ) ) {
				return $classes;
			}
		}

		$classes['Set_Up_Automator']     = UA_ABSPATH . 'src/core/classes/class-set-up-automator.php';
		$classes['Initialize_Automator'] = UA_ABSPATH . 'src/core/classes/class-initialize-automator.php';

		// Load Anon part if Pro is not active
		if ( ! defined( 'AUTOMATOR_PRO_FILE' ) ) {
			$classes['Automator_Handle_Anon'] = UA_ABSPATH . 'src/core/anon/class-automator-handle-anon.php';
		}

		return $classes;
	}

	/**
	 * @return bool
	 */
	public function any_active_recipe() {
		// Check if cache exists
		$results = Automator()->cache->get( 'automator_any_recipes_active', 'automator' );

		if ( ! empty( $results ) && 'yes' === $results ) {
			return true;
		}

		if ( ! empty( $results ) && 'no' === $results ) {
			return false;
		}

		if ( empty( $results ) ) {
			global $wpdb;

			$results = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->posts WHERE post_type = %s AND post_status = %s", 'uo-recipe', 'publish' ) );
			$results = 0 !== absint( $results );
			$val     = 'yes';
			if ( false === $results ) {
				$val = 'no';
			}

			// Instead of transient, lets use cache
			Automator()->cache->set( 'automator_any_recipes_active', $val, 'automator', 2 * MINUTE_IN_SECONDS );
		}

		return $results;
	}

	/**
	 * load_migrations
	 *
	 * @return void
	 */
	public function load_migrations() {
		require_once UA_ABSPATH . 'src/core/migrations/abstract-migration.php';
		require_once UA_ABSPATH . 'src/core/migrations/class-migrate-schedules.php';
		require_once UA_ABSPATH . 'src/core/migrations/class-migrate-triggers.php';
		require_once UA_ABSPATH . 'src/core/migrations/class-migrate-nested-tokens.php';
		require_once UA_ABSPATH . 'src/core/migrations/class-migrate-nested-tokens-pro.php';
		require_once UA_ABSPATH . 'src/core/migrations/class-migrate-option-types.php';
	}

	/**
	 *
	 */
	public function load_traits() {
		do_action( 'automator_before_traits' );

		// Settings
		$classes['Trait_Settings_Premium_Integrations'] = UA_ABSPATH . 'src/core/lib/settings/trait-premium-integrations.php';

		$classes['Premium_Integration_Settings'] = UA_ABSPATH . 'src/core/lib/settings/premium-integration-settings.php';

		// Integrations
		$classes['Integrations'] = UA_ABSPATH . 'src/core/lib/recipe-parts/trait-integrations.php';

		// Closures
		$classes['Trait_Closure_Setup'] = UA_ABSPATH . 'src/core/lib/recipe-parts/closures/trait-closure-setup.php';
		$classes['Closures']            = UA_ABSPATH . 'src/core/lib/recipe-parts/trait-closures.php';

		// Tokens
		$classes['Trait_Trigger_Tokens'] = UA_ABSPATH . 'src/core/lib/recipe-parts/trait-trigger-tokens.php';

		// Triggers
		$classes['Trait_Trigger_Setup']          = UA_ABSPATH . 'src/core/lib/recipe-parts/triggers/trait-trigger-setup.php';
		$classes['Trait_Trigger_Filters']        = UA_ABSPATH . 'src/core/lib/recipe-parts/triggers/trait-trigger-filters.php';
		$classes['Trait_Trigger_Recipe_Filters'] = UA_ABSPATH . 'src/core/lib/recipe-parts/triggers/trait-trigger-recipe-filters.php';
		$classes['Trait_Trigger_Conditions']     = UA_ABSPATH . 'src/core/lib/recipe-parts/triggers/trait-trigger-conditions.php';
		$classes['Trait_Trigger_Process']        = UA_ABSPATH . 'src/core/lib/recipe-parts/triggers/trait-trigger-process.php';
		$classes['Triggers']                     = UA_ABSPATH . 'src/core/lib/recipe-parts/triggers/trait-triggers.php';

		// Action Tokens
		$classes['Trait_Action_Tokens'] = UA_ABSPATH . 'src/core/lib/recipe-parts/actions/trait-action-tokens.php';

		// Actions
		$classes['Trait_Action_Setup']         = UA_ABSPATH . 'src/core/lib/recipe-parts/actions/trait-action-setup.php';
		$classes['Trait_Action_Conditions']    = UA_ABSPATH . 'src/core/lib/recipe-parts/actions/trait-action-conditions.php';
		$classes['Trait_Action_Parser']        = UA_ABSPATH . 'src/core/lib/recipe-parts/actions/trait-action-parser.php';
		$classes['Trait_Action_Process']       = UA_ABSPATH . 'src/core/lib/recipe-parts/actions/trait-action-process.php';
		$classes['Trait_Action_Helpers_Email'] = UA_ABSPATH . 'src/core/lib/recipe-parts/actions/trait-action-helpers-email.php';
		$classes['Trait_Action_Helpers']       = UA_ABSPATH . 'src/core/lib/recipe-parts/actions/trait-action-helpers.php';
		$classes['Actions']                    = UA_ABSPATH . 'src/core/lib/recipe-parts/trait-actions.php';

		// Webhooks
		$classes['Webhooks'] = UA_ABSPATH . 'src/core/lib/recipe-parts/trait-webhooks.php';

		if ( empty( $classes ) ) {
			return;
		}
		// TODO: Generate Class names by filenames
		foreach ( $classes as $file ) {
			require_once $file;
		}

		require_once UA_ABSPATH . 'src/core/lib/recipe-parts/abstract-integration.php';
		require_once UA_ABSPATH . 'src/core/lib/recipe-parts/actions/abstract-action.php';
		require_once UA_ABSPATH . 'src/core/lib/recipe-parts/triggers/abstract-trigger.php';

		do_action( 'automator_after_traits' );
	}

	/**
	 * Adds `utm_r` parameters to all Automator Pro Links.
	 *
	 * @return void.
	 */
	public function global_utm_r_links() {

		$uncanny_automator_enabled_global_utm = apply_filters( 'uncanny_automator_enabled_global_utm', true );

		$uncannyautomator_source = automator_get_option( 'uncannyautomator_source' );

		if ( false === $uncannyautomator_source || empty( $uncannyautomator_source ) ) {
			return;
		}

		if ( ! $uncanny_automator_enabled_global_utm ) {
			return;
		}
		?>
		<script>

			jQuery(document).ready(function ($) {

				"use strict";

				var automator_pro_links = 'a[href^="https://automatorplugin.com"]';

				var _update_url_parameter = function (uri, key, value) {

					// remove the hash part before operating on the uri
					var i = uri.indexOf('#');
					var hash = i === -1 ? '' : uri.substr(i);
					uri = i === -1 ? uri : uri.substr(0, i);

					var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
					var separator = uri.indexOf('?') !== -1 ? "&" : "?";
					if (uri.match(re)) {
						uri = uri.replace(re, '$1' + key + "=" + value + '$2');
					} else {
						uri = uri + separator + key + "=" + value;
					}
					return uri + hash;  // finally append the hash as well
				}

				var source = "<?php echo esc_js( $uncannyautomator_source ); ?>";

				// Add utmr to all automator upgrade links.
				$.each($(automator_pro_links), function () {
					var link_with_utmr = _update_url_parameter($(this).attr('href'), 'utm_r', '<?php echo esc_js( $uncannyautomator_source ); ?>');
					$(this).attr('href', link_with_utmr);
				});

				// Add utmr to all automator upgrade links which are not accessible on document ready.
				$(document).on('mouseover', automator_pro_links, function (e) {
					var link_with_utmr = _update_url_parameter($(this).attr('href'), 'utm_r', '<?php echo esc_js( $uncannyautomator_source ); ?>');
					$(this).attr('href', link_with_utmr);
				});

			});
		</script>
		<?php
	}

	/**
	 * Initiate the set-up wizard.
	 */
	public function initiate_setup_wizard() {
		if ( self::$is_admin_sect && ! defined( 'DOING_AJAX' ) ) {
			include_once UA_ABSPATH . 'src/core/admin/setup-wizard/setup-wizard.php';
			$setup_wizard = new Setup_Wizard();
		}

		if ( defined( 'DOING_AJAX' ) ) {
			// Add the ajax listener.
			include_once UA_ABSPATH . 'src/core/admin/setup-wizard/setup-wizard.php';
			add_action(
				'wp_ajax_uo_setup_wizard_set_tried_connecting',
				array(
					'\Uncanny_Automator\Setup_Wizard',
					'set_tried_connecting',
				)
			);
		}
	}

	/**
	 * automator_schedule_healthchecks
	 *
	 * @return void
	 */
	public function automator_schedule_healthchecks() {

		if ( ! wp_next_scheduled( 'automator_weekly_healthcheck' ) ) {
			wp_schedule_event( time(), 'weekly', 'automator_weekly_healthcheck' );
		}

		if ( ! wp_next_scheduled( 'automator_daily_healthcheck' ) ) {
			wp_schedule_event( time(), 'daily', 'automator_daily_healthcheck' );
		}
	}

	/**
	 * Callback method to `deleted_user` action hook.
	 *
	 * @param int $id
	 * @param int|null $reassign
	 * @param \WP_User $user
	 *
	 * @return bool True if the purge_logs method has been invoked. Otherwise, false.
	 */
	public function auto_prune_user_logs_handler( $id, $reassign, $user ) {

		$should_delete_user_records = automator_get_option( 'automator_delete_user_records_on_user_delete', false );

		// Bail on settings disabled. '0' is considered empty.
		if ( empty( $should_delete_user_records ) ) {
			return false;
		}

		$prune_logs = new Prune_Logs( false );
		$user_logs  = $prune_logs->get_user_logs( $id );

		foreach ( $user_logs as $user_log ) {
			$prune_logs->purge_logs( $user_log['automator_recipe_id'], $user_log['ID'], $user_log['run_number'] );
		}

		return true;
	}

	/**
	 * Get the instance of a core class.
	 *
	 * @param string $class_name
	 *
	 * @return mixed - The instance of the class if it exists, otherwise null.
	 */
	public static function get_core_class_instance( $class_name ) {

		if ( isset( self::$core_class_inits[ $class_name ] ) ) {
			return self::$core_class_inits[ $class_name ];
		}

		return null;
	}
}
