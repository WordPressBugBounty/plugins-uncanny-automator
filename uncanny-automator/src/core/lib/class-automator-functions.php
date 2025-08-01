<?php // phpcs:ignore Internal.Exception

namespace Uncanny_Automator;

use Exception;
use Uncanny_Automator\Deprecated\Legacy_Token_Parser;
use Uncanny_Automator\Resolver\Fields_Shared_Callable;
use WP_Error;

/**
 * Class Automator_Functions
 *
 * Development ready functions.
 *
 * @package Uncanny_Automator
 */
class Automator_Functions {

	/**
	 * @var
	 */
	public static $instance;
	/**
	 * Composite Class of integration, trigger, action, and closure registration functions
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var Automator_Registration
	 */
	public $register;
	/**
	 * Collection of all recipe types
	 *
	 * @since    2.0.0
	 * @access   public
	 */
	public $recipe_types = array( 'user', 'anonymous' );
	/**
	 * Collection of valid recipe part post types.
	 *
	 * @since    4.2.0
	 * @access   public
	 */
	public $recipe_part_post_types = array( 'uo-trigger', 'uo-action', 'uo-closure', 'uo-loop', 'uo-loop-filter' );
	/**
	 * Collection of all recipe items
	 *
	 * @since    4.2.0
	 * @access   public
	 */
	public $recipe_items = array();
	/**
	 * Collection of all integrations
	 *
	 * @since    4.6
	 * @access   public
	 */
	public $all_integrations = array();
	/**
	 * Collection of active integrations
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public $integrations = array();
	/**
	 * Collection of all triggers
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public $triggers = array();
	/**
	 * Collection of all actions
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public $actions = array();
	/**
	 * Collection of all closures
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public $closures = array();
	/**
	 * Collection of all loop filters
	 *
	 * @since    4.2.0
	 * @access   public
	 */
	public $loop_filters = array();
	/**
	 * Triggers and actions for each recipe with data
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public $recipes_data = array();
	/**
	 * Collection of all localized strings
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public $i18n = array();
	/**
	 * @since    2.1
	 * @access   public
	 * @var Automator_Recipe_Process
	 */
	public $process;
	/**
	 *
	 * @since    2.1
	 * @access   public
	 * @var Automator_Recipe_Process_Complete
	 */
	public $complete;
	/**
	 * Composite Class of pre-defined Automator helper functions
	 *
	 * @since    2.1.0
	 * @access   public
	 * @var Automator_Helpers
	 */
	public $helpers;
	/**
	 * Composite Class of pre-defined Automator utilities
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var Automator_Utilities
	 */
	public $utilities;
	/**
	 * Composite Class of data collection functions
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var Automator_Get_Data
	 */
	public $get;
	/**
	 * Composite Class of pre-defined Automator tokens
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public $tokens;
	/**
	 * Composite Class that checks plugin status
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public $plugin_status;
	/**
	 * Composite Class that returns common error messages
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public $error_message;
	/**
	 * Composite Class that returns an input that needs to have tokens replaced
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public $parse;
	/**
	 * Collection of all Automator Email Variables
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public $defined_variables;

	/**
	 * System report
	 *
	 * @since    3.0.0
	 * @access   public
	 * @var Automator_System_Report
	 */
	public $system_report;

	/**
	 * @var Automator_WP_Error
	 */
	public $wp_error;

	/**
	 * @var \Uncanny_Automator\Automator_Error
	 */
	public $error;

	/**
	 * @var Automator_WP_Error
	 */
	public $exception;

	/**
	 * @var Automator_DB_Handler
	 */
	public $db;

	/**
	 * @var Automator_Cache_Handler
	 */
	public $cache;

	/**
	 * @var Automator_Send_Webhook
	 */
	public $send_webhook;

	/**
	 * @var Parsed_Token_Records_Singleton
	 */
	protected $parsed_token_records = null;

	/**
	 * Initializes all development helper classes and variables via class composition
	 */
	public function __construct() {
		// Automator Cache Handler
		require_once __DIR__ . '/helpers/class-automator-cache-handler.php';
		$this->cache = Automator_Cache_Handler::get_instance();

		// Automator DB Handler
		require_once __DIR__ . '/utilities/db/class-automator-db-handler-tokens.php';
		require_once __DIR__ . '/utilities/db/class-automator-db-handler-closures.php';
		require_once __DIR__ . '/utilities/db/class-automator-db-handler-actions.php';
		require_once __DIR__ . '/utilities/db/class-automator-db-handler-triggers.php';
		require_once __DIR__ . '/utilities/db/class-automator-db-handler-recipes.php';
		require_once __DIR__ . '/utilities/db/class-automator-db-handler-api.php';
		require_once __DIR__ . '/utilities/db/class-automator-db-handler.php';
		$this->db = Automator_DB_Handler::get_instance();

		// Automator WP_Error Handler
		require_once __DIR__ . '/utilities/error/class-automator-wp-error.php';
		$this->wp_error = Automator_WP_Error::get_instance();

		// Automator_Exception Handler
		require_once __DIR__ . '/utilities/error/class-automator-exception.php';
		$this->exception = Automator_Exception::get_instance();

		// Automator_Exception Handler
		require_once __DIR__ . '/utilities/error/class-automator-error.php';
		$this->error = Automator_Error::get_instance();

		// Automator integration, trigger, action and closure registration
		require_once __DIR__ . '/utilities/class-automator-registration.php';
		$this->register = Automator_Registration::get_instance();

		// Automator integration, trigger, action and closure process
		require_once __DIR__ . '/process/class-automator-recipe-process.php';
		require_once __DIR__ . '/process/class-automator-recipe-process-user.php';
		//require_once __DIR__ . '/process/class-automator-recipe-process-anon.php';
		$this->process = Automator_Recipe_Process::get_instance();

		// Automator integration, trigger, action and closure process
		require_once __DIR__ . '/process/class-automator-action-status.php';
		require_once __DIR__ . '/process/class-automator-recipe-process-complete.php';
		$this->complete = Automator_Recipe_Process_Complete::get_instance();

		// Load pre-defined options for triggers, actions, and closures
		require_once __DIR__ . '/helpers/class-automator-helpers.php';
		require_once __DIR__ . '/helpers/class-automator-email-helpers.php';
		require_once __DIR__ . '/helpers/class-automator-recipe-helpers.php';
		require_once __DIR__ . '/helpers/class-automator-recipe-helpers-field.php';
		require_once __DIR__ . '/helpers/class-automator-trigger-condition-helpers.php';
		$this->helpers = Automator_Helpers::get_instance();

		// Load plugin status checks
		require_once __DIR__ . '/utilities/class-automator-integrations-status.php';
		$this->plugin_status = Automator_Integrations_Status::get_instance();

		// Load plugin status checks
		require_once __DIR__ . '/utilities/error/class-automator-error-messages.php';
		$this->error_message = Automator_Error_Messages::get_instance();

		// Load plugin status checks
		require_once __DIR__ . '/recipe-parts/tokens/class-automator-tokens.php';
		$this->tokens = Automator_Tokens::get_instance();

		// Load plugin status checks
		require_once __DIR__ . '/utilities/class-automator-input-parser.php';
		$this->parse = Automator_Input_Parser::get_instance();

		$use_legacy_parser = apply_filters( 'automator_use_legacy_token_parser', false );
		if ( true === $use_legacy_parser ) {
			$this->parse = Legacy_Token_Parser::get_instance();
		}

		// Load plugin translated strings
		require_once __DIR__ . '/utilities/class-automator-translations.php';
		$this->i18n = Automator_Translations::get_instance();

		// Load plugin translated strings
		require_once __DIR__ . '/utilities/class-automator-utilities.php';
		$this->utilities = Automator_Utilities::get_instance();

		// Load plugin translated strings
		require_once __DIR__ . '/utilities/class-automator-get-data.php';
		$this->get = Automator_Get_Data::get_instance();

		// Load System report
		require_once __DIR__ . '/utilities/class-automator-system-report.php';
		$this->system_report = Automator_System_Report::get_instance();

		// Load Webhook files
		require_once __DIR__ . '/webhooks/class-automator-send-webhook.php';
		$this->send_webhook = Automator_Send_Webhook::get_instance();

		add_filter( 'plugins_loaded', array( $this, 'filter_recipe_parts' ), AUTOMATOR_LOAD_INTEGRATIONS_PRIORITY );
	}

	/**
	 * @return void
	 */
	public function filter_recipe_parts() {

		$this->integrations = apply_filters_deprecated( 'uap_integrations', array( $this->integrations ), '3.0', 'automator_integrations' );
		$this->integrations = apply_filters( 'automator_integrations', $this->integrations );

		$this->actions = apply_filters_deprecated( 'uap_actions', array( $this->actions ), '3.0', 'automator_actions' );
		$this->actions = apply_filters( 'automator_actions', $this->actions );

		$this->triggers = apply_filters_deprecated( 'uap_triggers', array( $this->triggers ), '3.0', 'automator_triggers' );
		$this->triggers = apply_filters( 'automator_triggers', $this->triggers );

		$this->closures = apply_filters_deprecated( 'uap_closures', array( $this->closures ), '3.0', 'automator_closures' );
		$this->closures = apply_filters( 'automator_closures', $this->closures );

		$this->recipe_items = apply_filters( 'automator_recipe_items', $this->recipe_items );

		$this->all_integrations = apply_filters( 'automator_all_integrations', $this->all_integrations );

		$this->recipe_types = apply_filters_deprecated( 'uap_recipe_types', array( $this->recipe_types ), '3.0', 'automator_recipe_types' );
		$this->recipe_types = apply_filters( 'automator_recipe_types', $this->recipe_types );
	}

	/**
	 * @return \Uncanny_Automator\Automator_Functions
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Creates and return a new Action token factory object.
	 *
	 * @return Factory
	 */
	public function action_tokens() {
		// The action tokens factory class.
		return new Services\Recipe\Action\Token\Factory();
	}

	/**
	 * @return Singleton\Parsed_Token_Records_Singleton
	 */
	public function parsed_token_records() {
		return Singleton\Parsed_Token_Records_Singleton::get_instance();
	}

	/**
	 * @return Logger\Singleton\Async_Actions_Logger_Singleton
	 */
	public function async_action_logger() {
		return Logger\Singleton\Async_Actions_Logger_Singleton::get_instance();
	}

	/**
	 * @returnLogger\Singleton\Main_Aggregate_Logger_Singleton
	 */
	public function main_aggregate_logger() {
		return Logger\Singleton\Main_Aggregate_Logger_Singleton::get_instance();
	}

	/**
	 * @return Services\Structure\Actions\Item\Loop\Filters_Db
	 */
	public function loop_filters_db() {
		return new Services\Structure\Actions\Item\Loop\Filters_Db();

	}

	/**
	 * @param $integration_code
	 * @param $integration
	 */
	public function set_integrations( $integration_code, $integration ) {
		$this->integrations[ $integration_code ] = $integration;
	}

	/**
	 * @param $integration_code
	 * @param $integration
	 */
	public function set_all_integrations( $integration_code, $integration ) {

		// Only register the integration if it has icon_svg.
		if ( ! isset( $integration['icon_svg'] ) && array_key_exists( $integration_code, $this->all_integrations ) ) {
			return;
		}

		$this->all_integrations[ $integration_code ] = $integration;
	}

	/**
	 * @param string $integration_code
	 *
	 * @return string
	 */
	public function get_integration_name_by_code( $integration_code ) {
		if ( isset( $this->all_integrations[ $integration_code ] ) ) {
			return $this->all_integrations[ $integration_code ]['name'];
		}

		return '';
	}

	/**
	 * @param $trigger
	 */
	public function set_triggers( $trigger ) {
		if ( $this->add_unique_recipe_item( $trigger, 'triggers' ) ) {
			// Code is a required field.
			$this->triggers[ $trigger['code'] ] = $trigger;
		};
	}

	/**
	 * @param string $code
	 */
	public function has_trigger( $code = '' ) {
		return isset( $this->triggers[ $code ] );
	}

	/**
	 * Retrieves the trigger from the Triggers entries.
	 *
	 * @param string $code
	 *
	 * @return bool|mixed[] The Trigger if there is an entry. Otherwise, boolean false.
	 */
	public function get_trigger( $code = '' ) {
		if ( $this->has_trigger( $code ) ) {
			return $this->triggers[ $code ];
		}

		return false;
	}

	/**
	 * Does linear search to find the integration triggers by integration code.
	 *
	 * @param string $integration_code
	 *
	 * @return mixed[]
	 */
	public function get_integration_triggers( $integration_code ) {

		$triggers = array();

		foreach ( Automator()->get_triggers() as $code => $trigger ) {

			if ( $integration_code === $trigger['integration'] ) {
				$triggers[ $code ] = $trigger;
			}
		}

		return $triggers;
	}

	/**
	 * Does linear search to find the integration actions by integration code.
	 *
	 * @param string $integration_code
	 *
	 * @return mixed[]
	 */
	public function get_integration_actions( $integration_code ) {

		$actions = array();

		foreach ( Automator()->get_actions() as $code => $action ) {

			if ( $integration_code === $action['integration'] ) {
				$actions[ $code ] = $action;
			}
		}

		return $actions;

	}

	/**
	 * @param $action
	 */
	public function set_actions( $action ) {
		if ( $this->add_unique_recipe_item( $action, 'actions' ) ) {
			// Code is a required field.
			$this->actions[ $action['code'] ] = $action;
		}
	}

	/**
	 * @param $code
	 *
	 * @return bool
	 */
	public function has_action( $code = '' ) {
		return isset( $this->actions[ $code ] );
	}

	/**
	 * @param $code
	 *
	 * @return false|mixed
	 */
	public function get_action( $code = '' ) {
		if ( $this->has_action( $code ) ) {
			return $this->actions[ $code ];
		}

		return false;
	}

	/**
	 * @param string $code
	 * @param string $integration
	 *
	 * @return mixed false|array
	 */
	public function get_closure( $code, $integration ) {
		$closures = $this->get_closures();
		if ( ! empty( $closures ) ) {
			foreach ( $closures as $closure ) {
				if ( $closure['code'] === $code && $closure['integration'] === $integration ) {
					return $closure;
				}
			}
		}

		return false;
	}

	/**
	 * @param $closure
	 */
	public function set_closures( $closure ) {
		if ( $this->add_unique_recipe_item( $closure, 'closures' ) ) {
			$this->closures[] = $closure;
		}
	}

	/**
	 * Get all loop filters
	 *
	 * @since 5.9.1
	 *
	 * @return array
	 */
	public function get_loop_filters() {
		if ( empty( $this->loop_filters ) ) {
			$this->set_loop_filters();
		}

		return $this->loop_filters;
	}

	/**
	 * Get loop filter
	 *
	 * @since 5.9.1
	 *
	 * @param string $filter
	 * @param string $integration
	 *
	 * @return bool
	 */
	public function get_loop_filter( $filter, $integration ) {
		$filters = $this->get_loop_filters();
		if ( isset( $filters[ $integration ] ) && isset( $filters[ $integration ][ $filter ] ) ) {
			return $filters[ $integration ][ $filter ];
		}

		return false;
	}

	/**
	 * Set loop filters
	 *
	 * @since 5.9.1
	 *
	 * @return void
	 */
	public function set_loop_filters() {
		if ( defined( 'AUTOMATOR_PRO_ITEM_NAME' ) ) {
			$this->loop_filters = automator_pro_loop_filters()->get_filters();
		}
	}

	/**
	 * @param array $item
	 * @param string $type "actions" || "triggers" || "closures"
	 */
	private function add_unique_recipe_item( $item, $type ) {

		$integration = $item['integration'];
		$code        = $item['code'];

		if ( isset( $this->recipe_items[ $integration ][ $type ][ $code ] ) ) {
			return false;
		}

		$this->recipe_items[ $integration ][ $type ][ $code ] = 1;

		return true;
	}

	/**
	 * @param $recipe_type
	 * @param $details
	 */
	public function set_recipe_type( $recipe_type, $details ) {
		$this->recipe_types[ $recipe_type ] = $details;
	}

	/**
	 * Returns a filtered set of automator recipe items
	 *
	 * @return array
	 */
	public function get_recipe_items() {
		return $this->recipe_items;
	}

	/**
	 * Returns a filtered set on automator integrations
	 *
	 * @return array
	 */
	public function get_integrations() {
		return $this->integrations;
	}

	/**
	 * @param $code
	 *
	 * @return bool
	 */
	public function has_integration( $code ) {
		return isset( $this->integrations[ $code ] );
	}

	/**
	 * @param string $code
	 *
	 * @return null|array
	 */
	public function get_integration( $code ) {
		if ( $this->has_integration( $code ) ) {
			return $this->integrations[ $code ];
		}

		return null;
	}

	/**
	 * @return mixed|null
	 */
	public function get_all_integrations() {
		return $this->all_integrations;
	}

	/**
	 * Returns a filtered set on automator triggers
	 *
	 * @return array
	 */
	public function get_triggers() {
		return $this->triggers;
	}

	/**
	 * Returns a filtered set on automator actions
	 *
	 * @return array
	 */
	public function get_actions() {
		return $this->actions;
	}

	/**
	 * Returns a filtered set on automator closures
	 *
	 * @return array
	 */
	public function get_closures() {
		return $this->closures;
	}

	/**
	 * Returns a recipe types for automator
	 *
	 * @return array
	 */
	public function get_recipe_types() {
		return $this->recipe_types;
	}

	/**
	 * @param $code
	 *
	 * @return mixed
	 */
	public function get_author_name( $code = '' ) {
		if ( ! empty( $code ) ) {
			$code   = strtolower( $code );
			$filter = "automator_{$code}_author_name";
		} else {
			$filter = 'automator_author_name';
		}

		return apply_filters( $filter, 'Uncanny Owl' );
	}

	/**
	 * @param $code
	 * @param $link
	 *
	 * @return mixed
	 */
	public function get_author_support_link( $code = '', $link = '' ) {
		$url = 'https://automatorplugin.com/';
		if ( ! empty( $code ) ) {
			$code   = strtolower( $code );
			$filter = "automator_{$code}_author_support_link";
		} else {
			$filter = 'automator_author_support_link';
		}
		if ( ! empty( $link ) ) {
			$url .= $link;
		}

		return apply_filters( $filter, $url );
	}

	/**
	 * Get data for all recipe objects
	 *
	 * @param $force_new_data_load
	 * @param int $recipe_id . Defaults to null.
	 *
	 * @return array
	 */
	public function get_recipes_data( $force_new_data_load = false, $recipe_id = null ) {

		if ( ( false === $force_new_data_load ) && ! empty( $this->recipes_data ) && null === $recipe_id ) {
			return $this->recipes_data;
		}

		$recipes_data = Automator()->cache->get( $this->cache->recipes_data );

		if ( ! empty( $recipes_data ) && false === $force_new_data_load && null === $recipe_id ) {
			return $recipes_data;
		}

		// Accidentally sent recipe post instead of id?
		if ( $recipe_id instanceof \WP_Post && 'uo-recipe' === (string) $recipe_id->post_type ) {
			$recipe_id = $recipe_id->ID;
		}

		if ( null !== $recipe_id && is_numeric( $recipe_id ) ) {
			return $this->get_recipe_data_by_recipe_id( $recipe_id, $force_new_data_load );
		}

		global $wpdb;

		$recipes = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_title, post_type, post_status, post_parent
					FROM $wpdb->posts
					WHERE post_type = %s
					AND post_status NOT LIKE %s
					ORDER BY ID DESC
					LIMIT 0, 99999",
				'uo-recipe',
				'trash'
			)
		);

		if ( empty( $recipes ) ) {
			return array();
		}

		$cached = Automator()->cache->get( 'get_recipe_type' );

		//Extract Recipe IDs
		$recipe_ids = array_column( (array) $recipes, 'ID' );

		//Collective array of recipes triggers, actions, closures
		$recipe_data = $this->pre_fetch_recipe_metas( $recipes );

		//Collective array of users recipes completed status
		$recipes_completed = $this->are_recipes_completed( null, $recipe_ids );

		$recipes_completed = empty( $recipes_completed ) ? array() : $recipes_completed;

		$recipes_loops = Automator()->loop_db()->fetch_all_recipes_loops();

		foreach ( $recipes as $recipe ) {

			$recipe_id = absint( $recipe->ID );

			if ( array_key_exists( $recipe_id, $recipe_data ) && is_array( $recipe_data[ $recipe_id ] ) && array_key_exists( 'triggers', $recipe_data[ $recipe_id ] ) ) {

				if ( $recipe_data[ $recipe_id ]['triggers'] ) {
					//Grab tokens for each of trigger
					foreach ( $recipe_data[ $recipe_id ]['triggers'] as $t_id => $tr ) {
						$t_id                                                     = absint( $t_id );
						$tokens                                                   = $this->tokens->trigger_tokens( $tr['meta'], $recipe_id );
						$recipe_data[ $recipe_id ]['triggers'][ $t_id ]['tokens'] = $tokens;
					}
				}

				// Add action tokens to recipe_objects.
				if ( ! empty( $recipe_data[ $recipe_id ] ['actions'] ) ) {
					foreach ( $recipe_data[ $recipe_id ] ['actions'] as $recipe_action_id => $recipe_action ) {
						$recipe_action_id                                                    = absint( $recipe_action_id );
						$recipe_data[ $recipe_id ]['actions'][ $recipe_action_id ]['tokens'] = $this->tokens->get_action_tokens_renderable( $recipe_action['meta'], $recipe_action_id, $recipe_id );
					}
				}

				$triggers = $recipe_data[ $recipe_id ]['triggers'];
			} else {
				$triggers = array();
			}

			$this->recipes_data[ $recipe_id ]['ID']          = $recipe_id;
			$this->recipes_data[ $recipe_id ]['post_status'] = $recipe->post_status;
			$this->recipes_data[ $recipe_id ]['recipe_type'] = isset( $cached[ $recipe_id ] ) ? $cached[ $recipe_id ] : Automator()->utilities->get_recipe_type( $recipe_id );

			$this->recipes_data[ $recipe_id ]['triggers'] = $triggers;

			if ( array_key_exists( $recipe_id, $recipe_data ) && is_array( $recipe_data[ $recipe_id ] ) && array_key_exists( 'actions', $recipe_data[ $recipe_id ] ) ) {
				$actions = $recipe_data[ $recipe_id ]['actions'];
			} else {
				$actions = array();
			}

			/**
			 * Add loops inside the actions. This is a temporary solution and must be removed in the future.
			 *
			 * Adding loop actions as top level actions.
			 *
			 * @since 5.0
			 */
			$loop_actions = Automator()->loop_db()->find_recipe_loops_actions( $recipe_id, true, false, $recipes_loops );

			if ( ! empty( $loop_actions ) ) {
				$actions = array_merge( $actions, $loop_actions );
			}

			$this->recipes_data[ $recipe_id ]['actions'] = $actions;

			if ( array_key_exists( $recipe_id, $recipe_data ) && is_array( $recipe_data[ $recipe_id ] ) && array_key_exists( 'closures', $recipe_data[ $recipe_id ] ) ) {
				$closures = $recipe_data[ $recipe_id ]['closures'];
			} else {
				$closures = array();
			}
			$this->recipes_data[ $recipe_id ]['closures'] = $closures;

			$this->recipes_data[ $recipe_id ]['completed_by_current_user'] = array_key_exists( $recipe_id, $recipes_completed ) ? $recipes_completed[ $recipe_id ] : false;
		}

		$this->recipes_data = apply_filters( 'automator_get_recipes_data', $this->recipes_data, $recipe_id );

		Automator()->cache->set( $this->cache->recipes_data, $this->recipes_data );

		return $this->recipes_data;
	}

	/**
	 * @param null $recipe_id
	 *
	 * @return array
	 */
	public function get_recipe_data_by_recipe_id( $recipe_id = null, $force_new = false ) {

		$recipes_loops = Automator()->loop_db()->fetch_all_recipes_loops();

		if ( null === $recipe_id ) {
			return array();
		}

		$key    = 'automator_recipe_data_of_' . $recipe_id;
		$recipe = Automator()->cache->get( $key );

		if ( ! empty( $recipe ) && false === $force_new ) {
			return $recipe;
		}

		$recipe  = array();
		$recipes = get_post( $recipe_id );
		if ( ! $recipes ) {
			return array();
		}

		$cached = Automator()->cache->get( 'get_recipe_type' );

		$is_recipe_completed           = $this->is_recipe_completed( $recipe_id );
		$key                           = $recipe_id;
		$recipe[ $key ]['ID']          = $recipe_id;
		$recipe[ $key ]['post_status'] = $recipes->post_status;
		$recipe[ $key ]['recipe_type'] = isset( $cached[ $recipe_id ] ) ? $cached[ $recipe_id ] : $this->utilities->get_recipe_type( $recipe_id );

		$triggers_array             = array();
		$triggers                   = $this->get_recipe_data( 'uo-trigger', $recipe_id, $triggers_array );
		$recipe[ $key ]['triggers'] = $triggers;

		$action_array = array();
		$actions      = $this->get_recipe_data( 'uo-action', $recipe_id, $action_array );

		/**
		 * Add loops inside the actions. This is a temporary solution and must be removed in the future.
		 *
		 * Adding loop actions as top level actions.
		 *
		 * @since 5.0
		 */
		$loop_actions = Automator()->loop_db()->find_recipe_loops_actions( $recipe_id, true, true, $recipes_loops );

		if ( ! empty( $loop_actions ) ) {
			$actions = array_merge( $actions, $loop_actions );
		}

		$recipe[ $key ]['actions'] = $actions;

		$closure_array              = array();
		$closures                   = $this->get_recipe_data( 'uo-closure', $recipe_id, $closure_array );
		$recipe[ $key ]['closures'] = $closures;

		$recipe[ $key ]['completed_by_current_user'] = $is_recipe_completed;

		$recipe[ $key ]['extra_options'] = $this->load_extra_options( $recipe[ $key ] );

		$recipe = apply_filters( 'automator_get_recipe_data_by_recipe_id', $recipe, $key );

		Automator()->cache->set( $key, $recipe );

		return $recipe;
	}

	/**
	 * load_extra_options
	 *
	 * @param mixed $type
	 * @param mixed $item_code
	 *
	 * @return void
	 */
	public function load_extra_options( $recipe ) {

		// Get the extra options meta. This one should only exists during REST calls. In all other cases, this meta should nor exist
		$extra_options_meta = get_post_meta( $recipe['ID'], 'extra_options', true );

		// If the meta doesn't exist (initial recipe page load), replace it with an empty array
		$extra_options = empty( $extra_options_meta ) ? array() : $extra_options_meta;

		// We will loop through triggers and actions to see if any of them have extra optiosn to load
		$types_to_process = array( 'actions', 'triggers' );

		foreach ( $types_to_process as $type ) {
			foreach ( $recipe[ $type ] as $item ) {

				$item_code   = $item['meta']['code'] ?? '';
				$integration = $item['meta']['integration'] ?? '';

				// If extra options were already loaded for this item, bail
				if ( isset( $extra_options[ $integration ][ $item_code ] ) ) {
					continue;
				}

				// Otherwise, get the options callback from the integration definition
				if ( 'actions' === $type ) {
					$callback = Automator()->get->value_from_action_meta( $item_code, 'options_callback' );
				} elseif ( 'triggers' === $type ) {
					$callback = Automator()->get->value_from_trigger_meta( $item_code, 'options_callback' );
				}

				// If there is no callback found, bail
				if ( ! $callback ) {
					continue;
				}

				// If the callback is found, execute it
				$callback_response = $this->get_options_from_callable( $type, $item_code, $callback );

				// If the callback is found, execute it
				$extra_options[ $integration ][ $item_code ] = apply_filters( 'automator_options_callback_response', $callback_response, $callback, $item, $recipe, $type );
			}
		}

		// Store all the extra options in the post meta so that subsequent REST API calls won't need to load the options again
		update_post_meta( $recipe['ID'], 'extra_options', $extra_options );

		return $extra_options;
	}

	/**
	 * Retrieve the callable return data from the shared callable fields class.
	 *
	 * This function initializes an instance of Fields_Shared_Callable, sets it up using the provided
	 * type and item code, and then executes the provided callback to retrieve the fields options.
	 *
	 * @param string   $type      The type of the fields to retrieve. Required.
	 * @param string   $item_code The item code associated with the fields. Required.
	 * @param callable $callback  A callable function to process and retrieve the fields options. Required.
	 * @param Fields_Shared_Callable|null $fields Instance of Fields_Shared_Callable for easier mocking. Optional.
	 *
	 * @since 5.9.0
	 *
	 * @return mixed[] The fields options array, as returned by the callback.
	 *                 If the callback is not callable, it triggers a doing_it_wrong notice and returns an empty array.
	 */
	public function get_options_from_callable( $type, $item_code, $callback, $fields = null ) {

		// Validate that the provided callback is indeed callable.
		if ( ! is_callable( $callback ) ) {
			doing_it_wrong( __FUNCTION__, 'The $callback parameter must be a valid callable.', '5.9.0' );
			return array();
		}

		// Create a new Fields_Shared_Callable object.
		if ( $fields === null ) {
			$fields = Fields_Shared_Callable::get_instance();
		}

		// Set up the instance with the provided type and item code, then execute the callback to get the options.
		try {
			$options = $fields->with_parameters( $type, $item_code )->get_callable( $callback );
		} catch ( Exception $e ) {
			// Trigger a notice and return an empty array.
			doing_it_wrong( __FUNCTION__, 'Exception: ' . $e->getMessage(), '5.9.0' );
			return array();
		}

		return $options;
	}

	/**
	 * @param array $recipes
	 *
	 * @return array
	 */
	public function pre_fetch_recipe_metas( $recipes = array() ) {
		$metas    = array();
		$triggers = array();
		$actions  = array();
		$closures = array();
		if ( ! empty( $recipes ) ) {

			global $wpdb;
			// Fetch uo-trigger, uo-action, uo-closure.
			$recipe_children = $wpdb->get_results( "SELECT ID, post_status, post_type, menu_order, post_parent FROM $wpdb->posts WHERE post_parent IN (SELECT ID FROM $wpdb->posts WHERE post_type = 'uo-recipe')" );

			if ( $recipe_children ) {
				foreach ( $recipe_children as $p ) {
					$child_id    = absint( $p->ID );
					$p_t         = $p->post_type;
					$p_s         = $p->post_status;
					$m_o         = $p->menu_order;
					$post_parent = $p->post_parent;
					switch ( $p_t ) {
						case 'uo-trigger':
							$triggers[ $child_id ] = array(
								'ID'          => $child_id,
								'post_status' => $p_s,
								'menu_order'  => $m_o,
								'post_parent' => $post_parent,
							);
							break;
						case 'uo-action':
							$actions[ $child_id ] = array(
								'ID'          => $child_id,
								'post_status' => $p_s,
								'menu_order'  => $m_o,
								'post_parent' => $post_parent,
							);
							break;
						case 'uo-closure':
							$closures[ $child_id ] = array(
								'ID'          => $child_id,
								'post_status' => $p_s,
								'menu_order'  => $m_o,
								'post_parent' => $post_parent,
							);
							break;
					}
				}
			}

			// Fetch metas for uo-trigger, uo-action, uo-closure
			$related_metas = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT pm.post_id, pm.meta_key, pm.meta_value, p.post_parent, p.post_type, p.menu_order
FROM $wpdb->postmeta pm
    LEFT JOIN $wpdb->posts p
        ON p.ID = pm.post_id
WHERE pm.post_id
          IN (SELECT ID FROM $wpdb->posts WHERE post_parent IN (SELECT ID FROM $wpdb->posts WHERE post_type = %s))",
					'uo-recipe'
				)
			);

			if ( $related_metas ) {
				foreach ( $related_metas as $p ) {
					$child_id = absint( $p->post_id );
					$m_k      = $p->meta_key;
					$m_v      = $p->meta_value;
					if ( array_key_exists( $child_id, $triggers ) ) {
						$triggers[ $child_id ]['meta'][ $m_k ] = $m_v;
					} elseif ( array_key_exists( $child_id, $actions ) ) {
						$actions[ $child_id ]['meta'][ $m_k ] = $m_v;
					} elseif ( array_key_exists( $child_id, $closures ) ) {
						$closures[ $child_id ]['meta'][ $m_k ] = $m_v;
					}
				}
			}
			//Fix missing metas!
			if ( $triggers ) {
				foreach ( $triggers as $trigger_id => $array ) {
					if ( ! array_key_exists( 'meta', $array ) ) {
						$triggers[ $trigger_id ]['meta'] = array( 'code' => '' );
					} else {
						//Attempt to return Trigger ID for magic button
						foreach ( $array['meta'] as $mk => $mv ) {
							if ( null === $mv ) {
								continue;
							}
							if ( 'code' === (string) trim( $mk ) && 'WPMAGICBUTTON' === (string) trim( $mv ) ) {
								$triggers[ $trigger_id ]['meta']['WPMAGICBUTTON'] = $trigger_id;
							}
						}
					}
				}
			}

			//Build old recipe array style
			foreach ( $related_metas as $r ) {
				$recipe_id     = absint( $r->post_parent );
				$non_recipe_id = absint( $r->post_id );
				switch ( $r->post_type ) {
					case 'uo-trigger':
						if ( array_key_exists( $non_recipe_id, $triggers ) ) {
							$metas[ $recipe_id ]['triggers'][] = $triggers[ $non_recipe_id ];
							unset( $triggers[ $non_recipe_id ] );
						}
						break;
					case 'uo-action':
						if ( array_key_exists( $non_recipe_id, $actions ) ) {
							$metas[ $recipe_id ]['actions'][] = $actions[ $non_recipe_id ];
							unset( $actions[ $non_recipe_id ] );
						}
						break;
					case 'uo-closure':
						if ( array_key_exists( $non_recipe_id, $closures ) ) {
							$metas[ $recipe_id ]['closures'][] = $closures[ $non_recipe_id ];
							unset( $closures[ $non_recipe_id ] );
						}
						break;
				}
			}
		}

		return $metas;
	}

	/**
	 * Check if the recipe was completed
	 *
	 * @param null $user_id
	 * @param $recipe_ids
	 *
	 * @return array
	 */
	public function are_recipes_completed( $user_id = null, $recipe_ids = array() ) {

		if ( empty( $recipe_ids ) ) {
			Automator()->wp_error->trigger( 'You are trying to check if a recipe is completed without providing a recipe_ids.' );

			return null;
		}

		// Set user ID
		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		// No user id is available.
		if ( 0 === $user_id ) {
			Automator()->wp_error->trigger( 'You are trying to check if a recipe is completed when a there is no logged in user.' );

			return null;
		}

		$completed = array();
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare( "SELECT COUNT(completed) AS completed, automator_recipe_id FROM {$wpdb->prefix}uap_recipe_log WHERE user_id = %d AND automator_recipe_id IN (" . join( ',', $recipe_ids ) . ') AND completed = 1 GROUP BY automator_recipe_id', $user_id ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);

		if ( $results ) {
			foreach ( $recipe_ids as $recipe_id ) {
				$complete = 0;
				$found    = false;
				foreach ( $results as $r ) {
					if ( $recipe_id === (int) $r->automator_recipe_id ) {
						$found    = true;
						$complete = $r->completed;
						break;
					} else {
						$found = false;
					}
				}

				if ( $found ) {
					$completed[ $recipe_id ] = $complete;
				} else {
					$completed[ $recipe_id ] = 0;
				}
			}
		} else {
			//Fallback to mark every recipe incomplete
			foreach ( $recipe_ids as $recipe_id ) {
				$completed[ $recipe_id ] = 0;
			}
		}

		return $this->utilities->recipes_number_times_completed( $recipe_ids, $completed );
	}

	/**
	 * Check if the recipe was completed
	 *
	 * @param null $recipe_id
	 * @param null $user_id
	 *
	 * @return null|bool
	 */
	public function is_recipe_completed( $recipe_id = null, $user_id = null ) {

		if ( null === $recipe_id || ! is_numeric( $recipe_id ) ) {
			Automator()->wp_error->trigger( 'You are trying to check if a recipe is completed without providing a recipe_id.' );

			return null;
		}

		/**
		 * If recipe is completed maximum number of times, bail.
		 *
		 * @since 3.0
		 */
		if ( $this->is_recipe_completed_max_times( $recipe_id ) ) {
			return true;
		}

		if ( ! is_user_logged_in() ) {
			return null;
		}

		// Set user ID
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$results = $this->user_completed_recipe_number_times( $recipe_id, $user_id );

		return $this->utilities->recipe_number_times_completed( $recipe_id, $results );
	}

	/**
	 * @param $recipe_id
	 * @param $user_id
	 *
	 * @return false|int|string
	 */
	public function user_completed_recipe_number_times( $recipe_id, $user_id ) {
		global $wpdb;
		$results = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(completed) AS num_times_completed
						FROM {$wpdb->prefix}uap_recipe_log
						WHERE 1=1
						AND user_id = %d
						AND automator_recipe_id = %d
						AND completed = 1",
				$user_id,
				$recipe_id
			)
		);

		if ( 0 === $results ) {
			return false;
		}

		return empty( $results ) ? 0 : $results;
	}

	/**
	 * @param null $recipe_id
	 *
	 * @return bool|null
	 */
	public function is_recipe_completed_max_times( $recipe_id = null ) {

		if ( null === $recipe_id || ! is_numeric( $recipe_id ) ) {
			Automator()->wp_error->add_error( 'is_recipe_completed', 'ERROR: You are trying to check if a recipe is completed without providing a recipe_id.', $this );

			return null;
		}

		global $wpdb;
		$results = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(completed) AS num_times_completed
						FROM {$wpdb->prefix}uap_recipe_log
						WHERE 1=1
						AND automator_recipe_id = %d
						AND completed = 1",
				$recipe_id
			)
		);

		if ( 0 === $results ) {
			return false;
		}
		$results = empty( $results ) ? 0 : $results;

		return $this->utilities->recipe_max_times_completed( $recipe_id, $results );
	}

	/**
	 * @param $recipe_id
	 * @param $type
	 *
	 * @return array|object|null
	 */
	public function get_recipe_children_query( $recipe_id, $type ) {
		global $wpdb;
		$q = $wpdb->prepare( "SELECT ID, post_status, menu_order, post_parent FROM $wpdb->posts WHERE post_parent = %d AND post_type = %s", $recipe_id, $type );
		if ( 'uo-action' === $type ) {
			$q = "$q ORDER BY menu_order ASC";
		}
		$q = apply_filters_deprecated(
			'q_get_recipe_data',
			array(
				$q,
				$recipe_id,
				$type,
			),
			'3.0',
			'automator_get_recipe_data_query'
		);
		$q = apply_filters( 'automator_get_recipe_data_query', $q, $recipe_id, $type );

		return $wpdb->get_results( $q, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Get saved data for recipe actions or triggers
	 *
	 * @param null $type
	 * @param null $recipe_id
	 * @param array $recipe_children
	 *
	 * @return mixed[]
	 */
	public function get_recipe_data( $type = null, $recipe_id = null, $recipe_children = array() ) {

		if ( null === $type ) {
			return null;
		}

		if ( ! in_array( $type, array( 'uo-trigger', 'uo-action', 'uo-closure' ), true ) ) {
			return null;
		}

		if ( ! is_numeric( $recipe_id ) ) {
			Automator()->wp_error->trigger( 'You are trying to get recipe data without providing a recipe_id' );

			return null;
		}

		if ( empty( $recipe_children ) ) {
			// All the triggers associated with the recipe
			$recipe_children = $this->get_recipe_children_query( $recipe_id, $type );
		}
		// All data for recipe triggers
		$recipe_children_data = array();
		if ( empty( $recipe_children ) ) {
			return $recipe_children_data;
		}

		// Check each trigger for set values
		foreach ( $recipe_children as $key => $child ) {

			// Collect all meta data for this trigger
			if ( ! array_key_exists( 'meta', $child ) ) {
				$child_meta = get_post_custom( $child['ID'] );
			} else {
				$child_meta = $child['meta'];
			}

			if ( ! $child_meta ) {
				continue;
			}

			// Get post custom return an array for each meta_key as there maybe more than one value per key.. we only store and need one value
			$child_meta_single = array();
			foreach ( $child_meta as $meta_key => $meta_value ) {
				$child_meta_single[ $meta_key ] = reset( $meta_value );
			}
			$code = array_key_exists( 'code', $child_meta_single ) ? $child_meta_single['code'] : '';

			/** Fix to show MAGIC BUTTON ID
			 *
			 * @since 3.0
			 * @package Uncanny_Automator
			 */
			if ( 'WPMAGICBUTTON' === (string) $code && ! array_key_exists( 'WPMAGICBUTTON', $child_meta_single ) ) {
				$child_meta_single['WPMAGICBUTTON'] = $child['ID'];
			}

			$item_not_found = $this->child_item_not_found_handle( $type, $code );
			//if ( $item_not_found ) {
			//$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET post_status = 'draft' WHERE ID = %d", absint( $child['ID'] ) ) );
			//$child['post_status'] = 'draft';
			//}

			// The trigger is create/stored automatically but may not have been saved. Delete if not saved!
			if ( empty( $child_meta ) && isset( $child['ID'] ) ) {
				continue;
			}

			$post_parent = isset( $child['post_parent'] ) ? absint( $child['post_parent'] ) : null;

			$recipe_children_data[ $key ]['ID']          = absint( $child['ID'] );
			$recipe_children_data[ $key ]['post_status'] = $child['post_status'];
			$recipe_children_data[ $key ]['meta']        = $child_meta_single;
			$recipe_children_data[ $key ]['post_parent'] = $post_parent;

			if ( ! empty( $child['menu_order'] ) ) {
				$recipe_children_data[ $key ]['menu_order'] = $child['menu_order'];
			}

			if ( 'uo-trigger' === $type ) {
				$recipe_children_data[ $key ]['tokens'] = $this->tokens->trigger_tokens( $child_meta_single, $recipe_id );
			}

			if ( 'uo-action' === $type ) {
				$recipe_children_data[ $key ]['tokens'] = $this->tokens->get_action_tokens_renderable( $child_meta_single, absint( $child['ID'] ), $recipe_id );
			}
		}

		return apply_filters(
			'automator_recipe_children_data',
			$recipe_children_data,
			array(
				'type'            => $type,
				'recipe_id'       => $recipe_id,
				'recipe_children' => $recipe_children,
			)
		);
	}

	/**
	 * Retrieve recipe actions ordered by menu number.
	 *
	 * @param int $recipe_id The recipe ID.
	 * @param bool $show_draft Whether to show the draft actions or not.
	 *
	 * @return array|object|null
	 */
	public function get_recipe_actions( $recipe_id = 0, $show_draft = false ) {

		// Do not add cache to this function. Memcached on SG created issue here.

		global $wpdb;

		if ( false === $show_draft ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID, post_title, post_status
					FROM {$wpdb->posts}
					WHERE post_parent = %d
					AND post_type = %s
					AND post_status = 'publish'
					ORDER BY menu_order ASC
					LIMIT 100
					",
					$recipe_id,
					'uo-action'
				),
				ARRAY_A
			);
		} else {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID, post_title, post_status
					FROM {$wpdb->posts}
					WHERE post_parent = %d
					AND post_type = %s
					ORDER BY menu_order ASC
					LIMIT 100
					",
					$recipe_id,
					'uo-action'
				),
				ARRAY_A
			);
		}

		return $results;
	}

	/**
	 * Retrives the recipe conditions of the given recipe.
	 *
	 * @param int $recipe_id .
	 *
	 * @return mixed[]
	 */
	public function get_recipe_conditions( $recipe_id = 0, $parent_id = 0 ) {

		$recipe_conditions = get_post_meta( absint( $recipe_id ), 'actions_conditions', true );

		// Bail out if recipe_conditions is not string or not empty.
		if ( ! is_string( $recipe_conditions ) && ! empty( $recipe_conditions ) ) {
			return array();
		}

		$recipe_conditions = json_decode( $recipe_conditions, true );

		// Filter recipe action conditions by parent id.
		if ( ! empty( $parent_id ) ) {
			$recipe_conditions = array_filter(
				(array) $recipe_conditions,
				function ( $condition ) use ( $parent_id ) {
					return isset( $condition['parent_id'] ) && absint( $condition['parent_id'] ) === absint( $parent_id );
				}
			);
		}

		if ( ! is_array( $recipe_conditions ) ) {
			return array();
		}

		return $recipe_conditions;

	}

	/**
	 * @param $type
	 * @param $code
	 *
	 * @return bool
	 */
	public function child_item_not_found_handle( $type, $code ) {
		$item_not_found = true;

		if ( 'uo-trigger' === $type ) {
			$system_triggers = $this->triggers;
			if ( ! empty( $system_triggers ) ) {
				foreach ( $system_triggers as $trigger ) {
					if ( $trigger['code'] === $code ) {
						$item_not_found = false;
					}
				}
			} else {
				$item_not_found = false;
			}
		}

		if ( 'uo-action' === $type ) {
			$system_actions = $this->actions;
			if ( ! empty( $system_actions ) ) {
				foreach ( $system_actions as $action ) {
					if ( $action['code'] === $code ) {
						$item_not_found = false;
					}
				}
			} else {
				$item_not_found = false;
			}
		}

		if ( 'uo-closure' === $type ) {
			$system_closures = $this->closures;
			if ( ! empty( $system_closures ) ) {
				foreach ( $system_closures as $closure ) {
					if ( $closure['code'] === $code ) {
						$item_not_found = false;
					}
				}
			} else {
				$item_not_found = false;
			}
		}

		return $item_not_found;
	}

	/**
	 * Added this function to directly fetch trigger data instead of looping thru
	 * recipe and it's triggers for parsing. Specially needed for multi-trigger
	 * parsing
	 *
	 * @param $recipe_id
	 * @param $trigger_id
	 *
	 * @return array|mixed
	 * @since  2.9
	 * @author Saad S.
	 */
	public function get_trigger_data( $recipe_id = 0, $trigger_id = 0 ) {
		$recipe_data = $this->get_recipe_data( 'uo-trigger', $recipe_id );
		if ( ! $recipe_data ) {
			return array();
		}
		foreach ( $recipe_data as $trigger_data ) {
			if ( absint( $trigger_id ) !== absint( $trigger_data['ID'] ) ) {
				continue;
			}

			return $trigger_data;
		}

		return array();
	}

	/**
	 * Complete the action for the user
	 *
	 * @param null $user_id
	 * @param null $action_data
	 * @param null $recipe_id
	 * @param $error_message
	 * @param null $recipe_log_id
	 *
	 * @param $args
	 *
	 * @return null
	 * @deprecated 3.0
	 */
	public function complete_action( $user_id = null, $action_data = null, $recipe_id = null, $error_message = '', $recipe_log_id = null, $args = array() ) {
		if ( defined( 'AUTOMATOR_DEBUG_MODE' ) && true === AUTOMATOR_DEBUG_MODE ) {
			_doing_it_wrong( 'complete_actions', 'Please use Automator()->complete->action() instead.', 3.0 );
		}

		return $this->complete->action( $user_id, $action_data, $recipe_id, $error_message, $recipe_log_id, $args );
	}

	/**
	 * Complete a recipe
	 *
	 * @param $recipe_id     null||int
	 * @param $user_id       null||int
	 * @param $recipe_log_id null||int
	 *
	 * @param $args
	 *
	 * @return null|bool
	 * @deprecated 3.0
	 */
	public function complete_recipe( $recipe_id = null, $user_id = null, $recipe_log_id = null, $args = array() ) {
		if ( defined( 'AUTOMATOR_DEBUG_MODE' ) && true === AUTOMATOR_DEBUG_MODE ) {
			_doing_it_wrong( 'complete_recipe', 'Please use Automator()->complete->recipe() instead.', 3.0 );
		}

		return $this->complete->recipe( $recipe_id, $user_id, $recipe_log_id, $args );
	}

	/**
	 * Complete all actions in recipe
	 *
	 * @param null $recipe_id
	 * @param null $user_id
	 * @param null $recipe_log_id
	 *
	 * @param $args
	 *
	 * @return bool
	 * @deprecated 3.0
	 */
	public function complete_actions( $recipe_id = null, $user_id = null, $recipe_log_id = null, $args = array() ) {
		if ( defined( 'AUTOMATOR_DEBUG_MODE' ) && true === AUTOMATOR_DEBUG_MODE ) {
			_doing_it_wrong( 'complete_actions', 'Please use Automator()->complete->complete_actions() instead.', 3.0 );
		}

		return $this->complete->complete_actions( $recipe_id, $user_id, $recipe_log_id, $args );
	}

	/**
	 * Complete all closures in recipe
	 *
	 * @param null $recipe_id
	 * @param null $user_id
	 * @param null $recipe_log_id
	 * @param $args
	 *
	 * @return bool
	 * @deprecated 3.0
	 */
	public function complete_closures( $recipe_id = null, $user_id = null, $recipe_log_id = null, $args = array() ) {
		if ( defined( 'AUTOMATOR_DEBUG_MODE' ) && true === AUTOMATOR_DEBUG_MODE ) {
			_doing_it_wrong( 'complete_closures', 'Please use Automator()->complete->closures() instead.', 3.0 );
		}

		return $this->complete->closures( $recipe_id, $user_id, $recipe_log_id, $args );
	}

	/**
	 * Insert the trigger for the user
	 *
	 * @param $args
	 *
	 * @return null
	 * @deprecated 3.0
	 */
	public function insert_trigger_meta( $args ) {
		if ( defined( 'AUTOMATOR_DEBUG_MODE' ) && true === AUTOMATOR_DEBUG_MODE ) {
			_doing_it_wrong( 'insert_trigger_meta', 'Please use Automator()->process->user->insert_trigger_meta() instead.', 3.0 );
		}

		return $this->process->user->insert_trigger_meta( $args );
	}

	/**
	 *
	 * Complete a trigger once all validation & trigger entry added
	 * and number of times met, complete the trigger
	 *
	 * @param $args
	 *
	 * @return bool
	 * @deprecated 3.0 Automator()->process->user->maybe_trigger_complete
	 */
	public function maybe_trigger_complete( $args ) {
		if ( defined( 'AUTOMATOR_DEBUG_MODE' ) && true === AUTOMATOR_DEBUG_MODE ) {
			_doing_it_wrong( 'maybe_trigger_complete', 'Please use Automator()->process->user->maybe_trigger_complete() instead.', 3.0 );
		}

		return $this->process->user->maybe_trigger_complete( $args );
	}

	/**
	 * Complete the trigger for the user
	 *
	 * @param $args
	 *
	 * @return null
	 * @deprecated 3.0
	 */
	public function complete_trigger( $args = array() ) {
		if ( defined( 'AUTOMATOR_DEBUG_MODE' ) && true === AUTOMATOR_DEBUG_MODE ) {
			_doing_it_wrong( 'complete_trigger', 'Please use Automator()->complete->trigger() instead.', 3.0 );
		}

		return $this->complete->trigger( $args );
	}

	/**
	 *
	 * Matches recipes against trigger meta/code. If a recipe is found and not completed,
	 * add a trigger entry in to the DB and matches number of times.
	 *
	 * @param      $args
	 * @param $mark_trigger_complete
	 *
	 * @return array|bool|int|null
	 * @deprecated 3.0 Use Automator()->process->user->
	 */
	public function maybe_add_trigger_entry( $args, $mark_trigger_complete = true ) {
		if ( defined( 'AUTOMATOR_DEBUG_MODE' ) && true === AUTOMATOR_DEBUG_MODE ) {
			_doing_it_wrong( 'maybe_add_trigger_entry', 'Please use Automator()->process->user->maybe_add_trigger_entry() instead.', 3.0 );
		}

		return $this->process->user->maybe_add_trigger_entry( $args, $mark_trigger_complete );
	}


	/**
	 * @param $recipe_id
	 * @param $user_id
	 * @param $create_recipe
	 * @param $args
	 * @param $maybe_simulate
	 * @param null $maybe_add_log_id
	 *
	 * @return array
	 * @since  2.0
	 * @author Saad S. on Nov 15th, 2019
	 *
	 * Added $maybe_simulate in order to avoid unnecessary recipe logs in database.
	 * It'll return existing $recipe_log_id if there's one for a user & recipe, or
	 * simulate an ID for the next run.. The reason for simulate is to avoid unnecessary
	 * recipe_logs in the database since we insert recipe log first & check if trigger
	 * is valid after which means, recipe log is added and not used in this run.
	 * Once trigger is validated.. I pass $maybe_simulate ID to $maybe_add_log_id
	 * and insert recipe log at this point.
	 *
	 * @deprecated 3.0
	 */
	public function maybe_create_recipe_log_entry( $recipe_id, $user_id, $create_recipe = true, $args = array(), $maybe_simulate = false, $maybe_add_log_id = null ) {
		if ( defined( 'AUTOMATOR_DEBUG_MODE' ) && true === AUTOMATOR_DEBUG_MODE ) {
			_doing_it_wrong( 'maybe_create_recipe_log_entry', 'Please use Automator()->process->user->maybe_create_recipe_log_entry() instead.', 3.0 );
		}

		return $this->process->user->maybe_create_recipe_log_entry( $recipe_id, $user_id, $create_recipe, $args, $maybe_simulate, $maybe_add_log_id );
	}

	/**
	 *
	 * Record an entry in to DB against a trigger
	 *
	 * @param      $user_id
	 * @param      $trigger_id
	 * @param      $recipe_id
	 * @param null $recipe_log_id
	 *
	 * @return array
	 * @deprecated 3.0
	 */
	public function maybe_get_trigger_id( $user_id, $trigger_id, $recipe_id, $recipe_log_id = null ) {
		if ( defined( 'AUTOMATOR_DEBUG_MODE' ) && true === AUTOMATOR_DEBUG_MODE ) {
			_doing_it_wrong( 'maybe_get_trigger_id', 'Please use Automator()->process->user->maybe_get_trigger_id() instead.', 3.0 );
		}

		return $this->process->user->maybe_get_trigger_id( $user_id, $trigger_id, $recipe_id, $recipe_log_id );
	}

	/**
	 * @param        $data
	 * @param $type
	 *
	 * @return string
	 * @deprecated 3.0
	 */
	public function uap_sanitize( $data, $type = 'text' ) {
		if ( defined( 'AUTOMATOR_DEBUG_MODE' ) && true === AUTOMATOR_DEBUG_MODE ) {
			_doing_it_wrong( 'uap_sanitize', 'Please use Automator()->utilities->automator_sanitize() instead.', 3.0 );
		}

		return $this->utilities->automator_sanitize( $data, $type );
	}

	/**
	 * @param $args
	 * @param $check
	 *
	 * @return bool
	 */
	public function is_user_signed_in( $args ) {
		$is_signed_in = array_key_exists( 'is_signed_in', $args ) ? $args['is_signed_in'] : false;
		/**
		 * v3.9.1 or 3.10.
		 * Globally set `is_signed_in` to true if trigger type is "user"
		 */
		if ( isset( $args['code'] ) && false === $is_signed_in ) {
			$is_signed_in = Automator()->is_trigger_type_user( $args['code'] );
		}

		return true === $is_signed_in ? true : is_user_logged_in();
	}

	/**
	 * Register a new recipe type and creates a type if defined and the type does not exist
	 *
	 * @param null $recipe_type
	 * @param $recipe_details
	 *
	 * @return null|bool
	 * @deprecated v2.1 Automator()->register->recipe_type
	 * @use Automator()->register->recipe_type
	 */
	public function register_recipe_type( $recipe_type = null, $recipe_details = array() ) {
		if ( defined( 'AUTOMATOR_DEBUG_MODE' ) && true === AUTOMATOR_DEBUG_MODE ) {
			_doing_it_wrong( 'register_recipe_type', 'Please use Automator()->register->recipe_type instead.', 3.0 );
		}

		return $this->register->recipe_type( $recipe_type, $recipe_details );
	}

	/**
	 * Register a new trigger and creates a type if defined and the type does not exist
	 *
	 * @param $trigger
	 * @param $integration_code
	 * @param $integration
	 *
	 * @return null|bool
	 * @deprecated v2.1 Automator()->register->trigger
	 * @use Automator()->register->trigger
	 */
	public function register_trigger( $trigger = null, $integration_code = null, $integration = null ) {
		if ( defined( 'AUTOMATOR_DEBUG_MODE' ) && true === AUTOMATOR_DEBUG_MODE ) {
			_doing_it_wrong( 'register_trigger', 'Please use Automator()->register->trigger instead.', 3.0 );
		}

		return $this->register->trigger( $trigger, $integration_code, $integration );
	}

	/**
	 * Register a new uap action and creates a type if defined and the type does not exist
	 *
	 * @param $uap_action
	 * @param $integration_code
	 * @param $integration
	 *
	 * @return null|bool
	 * @deprecated v2.1 use Automator()->register->action()
	 * @use Automator()->register->action
	 */
	public function register_action( $uap_action = null, $integration_code = null, $integration = null ) {
		if ( defined( 'AUTOMATOR_DEBUG_MODE' ) && true === AUTOMATOR_DEBUG_MODE ) {
			_doing_it_wrong( 'register_action', 'Please use Automator()->register->action instead.', 3.0 );
		}

		return $this->register->action( $uap_action, $integration_code, $integration );
	}

	/**
	 * Registers a new closure and creates a type if defined and the type does not exist
	 *
	 * @param $closure
	 * @param $integration_code
	 * @param $integration
	 *
	 * @return null|bool
	 * @deprecated v2.1 Automator()->register->closure
	 * @use Automator()->register->closure
	 */
	public function register_closure( $closure = null, $integration_code = null, $integration = null ) {
		if ( defined( 'AUTOMATOR_DEBUG_MODE' ) && true === AUTOMATOR_DEBUG_MODE ) {
			_doing_it_wrong( 'register_closure', 'Please use Automator()->register->closure instead.', 3.0 );
		}

		return $this->register->closure( $closure, $integration_code, $integration );
	}

	/**
	 * Add a new integration
	 *
	 * @param $integration_code
	 * @param $integration
	 *
	 * @return null|bool
	 * @deprecated v2.1 Automator()->register->integration
	 * @use Automator()->register->integration
	 */
	public function register_integration( $integration_code = null, $integration = null ) {
		if ( defined( 'AUTOMATOR_DEBUG_MODE' ) && true === AUTOMATOR_DEBUG_MODE ) {
			_doing_it_wrong( 'register_integration', 'Please use Automator()->register->integration instead.', 3.0 );
		}

		return $this->register->integration( $integration_code, $integration );
	}

	/**
	 * @param $trigger_code
	 *
	 * @return false|string
	 */
	public function get_trigger_type( $trigger_code = null ) {
		if ( null === $trigger_code ) {
			return false;
		}
		$triggers = $this->triggers;
		if ( empty( $triggers ) ) {
			return false;
		}
		foreach ( $triggers as $trigger ) {
			if ( ! isset( $trigger['code'] ) ) {
				continue;
			}
			if ( (string) $trigger_code !== (string) $trigger['code'] ) {
				continue;
			}
			if ( ! isset( $trigger['type'] ) ) {
				return 'anonymous';
			}

			return (string) $trigger['type'];
		}

		return false;
	}

	/**
	 * Determines whether the trigger type is a user.
	 *
	 * @param string $trigger_code The trigger code.
	 *
	 * @return bool True if the trigger type is 'user'. Otherwise, false.
	 */
	public function is_trigger_type_user( $trigger_code = '' ) {

		return $this->is_trigger_type( 'user', $trigger_code );

	}

	/**
	 * Determines whether the trigger type is an anonymous.
	 *
	 * @param string $trigger_code The trigger code.
	 *
	 * @return bool True if the trigger type is 'anonymous'. Otherwise, false.
	 */
	public function is_trigger_type_anonymous( $trigger_code = '' ) {

		return $this->is_trigger_type( 'anonymous', $trigger_code );

	}

	/**
	 * Determines if the trigger type is equal to the given type.
	 *
	 * @param string $type The type (anonymous, user) you want to compare against the trigger.
	 * @param string $trigger_code The trigger code of the trigger.
	 *
	 * @return bool True if given type is equal to the type of the trigger. Otherwise, false.
	 */
	public function is_trigger_type( $type = '', $trigger_code = '' ) {

		return (string) $type === (string) $this->get_trigger_type( $trigger_code );

	}

	/**
	 * Load plugin textdomain.
	 *
	 * @since 1.0.0
	 */
	public function automator_load_textdomain() {
		$locale = determine_locale();

		/**
		 * Filter to adjust the Uncanny Automator locale to use for translations.
		 *
		 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
		 *
		 * Locales found in:
		 *      - WP_LANG_DIR/uncanny-automator/uncanny-automator-LOCALE.mo
		 *      - WP_LANG_DIR/plugins/uncanny-automator-LOCALE.mo
		 */
		$locale = apply_filters( 'plugin_locale', $locale, 'uncanny-automator' );

		unload_textdomain( 'uncanny-automator', true );
		load_textdomain( 'uncanny-automator', WP_LANG_DIR . '/uncanny-automator/uncanny-automator-' . $locale . '.mo' );
		load_plugin_textdomain( 'uncanny-automator', false, plugin_basename( dirname( AUTOMATOR_BASE_FILE ) ) . '/languages' );
	}

	/**
	 * Retrieves the timezone of the site as a string.
	 *
	 * @return string PHP timezone name or a ±HH:MM offset.
	 */
	public function get_timezone_string() {

		if ( function_exists( 'wp_timezone_string' ) ) {

			return wp_timezone_string();

		}

		return $this->get_timezone_string_offset();

	}

	/**
	 * Retrieves the timezone of the site as a string.
	 *
	 * Fallback function in-case `wp_timezone_string` is not available.
	 *
	 * @see https://developer.wordpress.org/reference/functions/wp_timezone_string/
	 *
	 * @return string PHP timezone name or a ±HH:MM offset.
	 */
	private function get_timezone_string_offset() {

		$timezone_string = get_option( 'timezone_string' );

		if ( $timezone_string ) {
			return $timezone_string;
		}

		$offset  = (float) get_option( 'gmt_offset' );
		$hours   = (int) $offset;
		$minutes = ( $offset - $hours );

		$sign      = ( $offset < 0 ) ? '-' : '+';
		$abs_hour  = abs( $hours );
		$abs_mins  = abs( $minutes * 60 );
		$tz_offset = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );

		return $tz_offset;

	}

	/**
	 * Decodes the supplied string parameters and parse with specified default values if key is not found.
	 *
	 * @param string $json_string
	 * @param mixed[] $defaults
	 *
	 * @return mixed[]
	 */
	public function json_decode_parse_args( $json_string = '', $defaults = array() ) {

		$args = json_decode( $json_string, true );

		return wp_parse_args( $args, $defaults );

	}

	/**
	 * Retrieve the recipe log meta using the meta_key, recipe id, and recipe log id.
	 *
	 * @param string $key The meta key.
	 * @param int $recipe_id
	 * @param int $recipe_log_id
	 *
	 * @return string
	 */
	public function get_recipe_meta( $key, $recipe_id, $recipe_log_id ) {

		global $wpdb;

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value
				FROM {$wpdb->prefix}uap_recipe_log_meta
				WHERE meta_key = %s
				AND recipe_id = %d
				AND meta_value <> '[]'
				AND recipe_log_id = %d",
				$key,
				$recipe_id,
				$recipe_log_id
			)
		);

		if ( ! is_string( $result ) || is_null( $result ) ) {
			return '';
		}

		return $result;

	}

	/**
	 * Retrieve all the conditions that failed.
	 *
	 * @param int $recipe_id
	 * @param int $recipe_log_id
	 *
	 * @return string[]
	 */
	public function get_conditions_failed( $recipe_id, $recipe_log_id ) {

		global $wpdb;

		$condition_id_failure_message = array();

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_value
					FROM {$wpdb->prefix}uap_recipe_log_meta
						WHERE meta_key = 'conditions_failed'
							AND recipe_id = %d
							AND meta_value <> '[]'
							AND recipe_log_id = %d",
				$recipe_id,
				$recipe_log_id
			),
			ARRAY_A
		);

		foreach ( $results as $result ) {
			$item = (array) json_decode( $result['meta_value'], true );
			foreach ( $item as $condition_id => $message ) {
				$condition_id_failure_message[ $condition_id ] = $message;
			}
		}

		return $condition_id_failure_message;

	}

	/**
	 * Retrieves the recipe closure.
	 *
	 * @param int $recipe_id The recipe ID.
	 *
	 * @return null|object The WordPress post object if not null.
	 */
	public function get_recipe_closure( $recipe_id ) {

		global $wpdb;

		if ( empty( $recipe_id ) ) {
			return null;
		}

		$closure = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->posts WHERE post_type='uo-closure' AND post_parent = %d",
				$recipe_id
			)
		);

		if ( empty( $closure ) ) {
			return null;
		}

		return $closure;

	}

	/**
	 * Retrieves the recipe object.
	 *
	 * @param string $format Could be OBJECT, ARRAY_A, 'JSON'
	 *
	 * @return mixed Returns the data in specified format. Otherwise, if not format is set, it will return the JSON string.
	 * @since 5.0 - _recipe object not returning actions when added. Need to fully flush cache
	 *
	 * @since 5.4 - Replace wp_cache_flush with automator_cache_delete_group
	 */
	public function get_recipe_object( $recipe_id, $format = 'JSON' ) {

		/**
		 * @since 5.0 - _recipe object not returning actions when added. Need to fully flush cache
		 */
		automator_cache_delete_group( 'automator_recipe' );

		$recipe_object = new Services\Recipe\Structure( absint( $recipe_id ) );

		if ( OBJECT === $format ) {
			return $recipe_object->retrieve();
		}

		if ( ARRAY_A === $format ) {
			return json_decode( $recipe_object->retrieve()->toJSON(), true );
		}

		return $recipe_object->retrieve()->toJSON();

	}

	/**
	 * Retrieves the recipe integrations.
	 *
	 * @param int $recipe_id
	 *
	 * @return string The JSON encoded integrations structure.
	 */
	public function get_recipe_integrations( $recipe_id ) {

		$integration_struc = new Services\Integrations\Structure( $recipe_id );

		return $integration_struc->restructure_integrations_object()->toJSON();

	}

	/**
	 * Creates a new instance of Loop_DB class and returns it.
	 *
	 * @return Loop_Db
	 */
	public function loop_db() {
		return new \Uncanny_Automator\Services\Structure\Actions\Item\Loop\Loop_Db();
	}

	/* Sets the properties of an action that will be displayed in the logs.
	 *
	 * @param array{array{type:string,label:string,content:string,code_language:string}} $properties_args The key `code_language` is optional. Only needed for non-text `type`.
	 *
	 * @return array{array{type:string,label:string,content:string,code_language:string}} Returns mixed array of the properties args.
	 */
	/**
	 * @param $properties_args
	 *
	 * @return Services\Properties
	 */
	public function set_properties( $properties_args = array() ) {

		$properties = new Services\Properties();

		foreach ( (array) $properties_args as $property_arg ) {

			$props = wp_parse_args(
				$property_arg,
				array(
					'type'          => '',
					'label'         => '',
					'value'         => '',
					'code_language' => '',
				)
			);

			$properties->add_item( $props );

		}

		$properties->dispatch();

		return $properties;

	}

	/**
	 * Sets the recipe part meta.
	 *
	 * @param int $post_id
	 * @param string $item_code
	 * @param string $integration_code
	 * @param string $post_type
	 * @param array $defaults
	 *
	 * @return void
	 */
	public function set_recipe_part_meta( $post_id, $item_code, $integration_code = '', $post_type = '', $defaults = array() ) {

		if ( empty( $post_id ) || empty( $item_code ) ) {
			return;
		}

		// Validate post type
		$post_type = empty( $post_type ) ? get_post_type( $post_id ) : $post_type;
		if ( ! in_array( $post_type, $this->recipe_part_post_types, true ) ) {
			return;
		}

		// Set defaults as array if not set.
		$defaults = is_array( $defaults ) ? $defaults : array();

		// Update the code meta.
		update_post_meta( $post_id, 'code', $item_code );

		// Get the integration code if not set.
		if ( empty( $integration_code ) ) {
			switch ( $post_type ) {
				case 'uo-trigger':
					$integration_code = Automator()->get->trigger_integration_from_trigger_code( $item_code );
					break;
				case 'uo-action':
					$integration_code = Automator()->get->action_integration_from_action_code( $item_code );
					break;
				case 'uo-closure':
					$integration_code = Automator()->get->closure_integration_from_closure_code( $item_code );
					break;
				case 'uo-loop':
					// TODO : REVIEW
					$integration_code = in_array( $item_code, array( 'LOOP_POSTS', 'LOOP_USERS', 'LOOP_TOKEN' ), true ) ? 'WP' : 'UNKNOWN';
					break;
				case 'uo-loop-filter':
					$integration_code = Automator()->get->loop_filter_integration_from_loop_filter_code( $item_code );
					break;
			}
		}

		if ( empty( $integration_code ) ) {
			return;
		}

		// Set type version key by removing uo- prefix.
		$version_key = 'uap_' . str_replace( 'uo-', '', $post_type ) . '_version';
		update_post_meta( $post_id, $version_key, Utilities::automator_get_version() );

		// Update the integration code.
		update_post_meta( $post_id, 'integration', $integration_code );

		// Update the integration name.
		if ( ! key_exists( 'integration_name', $defaults ) || empty( $defaults['integration_name'] ) ) {
			$name = $this->get_integration_name_by_code( $integration_code );
			update_post_meta( $post_id, 'integration_name', $name );
		}

		// Get the config for the recipe part.
		$config = $this->get_recipe_part_config( $post_type, $item_code, $integration_code );
		if ( empty( $config ) ) {
			return;
		}

		// Set the type meta ( pro | elite | free )
		if ( ! key_exists( 'type', $defaults ) || empty( $defaults['type'] ) ) {
			$is_pro   = isset( $config['is_pro'] ) ? (bool) $config['is_pro'] : false;
			$is_elite = isset( $config['is_elite'] ) ? (bool) $config['is_elite'] : false;
			$type     = $is_pro ? 'pro' : ( $is_elite ? 'elite' : 'free' );
			$type     = apply_filters( 'automator_recipe_part_type', $type, $post_type, $item_code, $integration_code );
			update_post_meta( $post_id, 'type', $type );
		}

		// Set the user type meta.
		if ( ! key_exists( 'user_type', $defaults ) || empty( $defaults['user_type'] ) ) {

			$requires_user = isset( $config['requires_user'] ) ? (bool) $config['requires_user'] : false;
			$type          = $requires_user ? 'user' : 'anonymous';
			if ( 'uo-trigger' === $post_type ) {
				$type = isset( $config['type'] ) ? $config['type'] : $type;
			}
			if ( 'uo-loop' === $post_type ) {
				$expression = get_post_meta( $post_id, 'iterable_expression', true );
				if ( isset( $expression['type'] ) && 'users' === $expression['type'] ) {
					$type = $expression['type'];
				}
			}

			$type = apply_filters( 'automator_recipe_part_user_type', $type, $post_type, $item_code, $integration_code );
			update_post_meta( $post_id, 'user_type', $type );
		}

	}

	/**
	 * Retrieves the recipe part config.
	 *
	 * @param string $part_post_type
	 * @param string $item_code
	 * @param string $integration_code
	 *
	 * @return mixed - array | null
	 */
	public function get_recipe_part_config( $part_post_type, $item_code, $integration_code ) {

		if ( empty( $part_post_type ) || empty( $item_code ) || empty( $integration_code ) ) {
			return null;
		}

		$config = null;
		switch ( $part_post_type ) {
			case 'uo-trigger':
				$config = $this->get_trigger( $item_code );
				break;
			case 'uo-action':
				$config = $this->get_action( $item_code );
				break;
			case 'uo-closure':
				$config = $this->get_closure( $item_code, $integration_code );
				break;
			case 'uo-loop':
				// TODO : REVIEW Elite loop settings?
				$config = array(
					'requires_user' => false,
					'is_pro'        => true,
					'is_elite'      => false,
				);
				break;
			case 'uo-loop-filter':
				$config    = $this->get_loop_filter( $item_code, $integration_code );
				$config    = ! empty( $config ) ? $config : array();
				$loop_type = isset( $config['loop_type'] ) ? $config['loop_type'] : 'posts';
				if ( 'users' === $loop_type ) {
					$config['requires_user'] = true;
				}
				$config['requires_user'] = isset( $config['requires_user'] ) ? $config['requires_user'] : false;
				$config['is_pro']        = isset( $config['is_pro'] ) ? $config['is_pro'] : true;
				$config['is_elite']      = isset( $config['is_elite'] ) ? $config['is_elite'] : false;
				break;
		}

		return $config;
	}

	/**
	 * Retrieves the date format from the WordPress options.
	 *
	 * This method fetches the 'date_format' option from the WordPress database.
	 * If the option is not set, it defaults to 'F j, Y'.
	 *
	 * @param string $default The default date format. Default is 'F j, Y'.
	 * @return string The date format.
	 */
	public function get_date_format( $default = 'F j, Y' ) {
		return get_option( 'date_format', $default );
	}

	/**
	 * Retrieves the time format from the WordPress options.
	 *
	 * This method fetches the 'time_format' option from the WordPress database.
	 * If the option is not set, it uses the provided default value.
	 *
	 * @param string $default The default time format. Default is 'g:i a'.
	 * @return string The time format.
	 */
	public function get_time_format( $default = 'g:i a' ) {
		return get_option( 'time_format', $default );
	}

	/**
	 * Checks if an app is connected.
	 *
	 * @param string $integration_code The integration code.
	 * @return bool True if it's not an app or the app is connected, false otherwise.
	 */
	public function is_app_connected( $integration_code ) {

		$all_integrations = Automator()->get_integrations();

		if ( false === $all_integrations[ $integration_code ]['connected'] ) {
			return false;
		}

		return true;
	}

	/**
	 * Determines if a recipe is throttled.
	 * 
	 * @param int $recipe_id The recipe ID.
	 * @param int $user_id   The user ID.
	 *
	 * @since 6.7.0 Moved from Automator_Recipe_Process_User class.
	 * @since 6.7.0
	 * - Added filter `automator_recipe_throttler_can_execute` to allow for custom throttling logic.
	 * 
	 * @return bool Returns true if the recipe is throttled, false otherwise.
	 */
	public function is_recipe_throttled( int $recipe_id, int $user_id ) {

		$data = (array) get_post_meta( $recipe_id, 'field_recipe_throttle', true );

		try {
			$throttler = new Services\Recipe\Process\Throttler( $recipe_id, $data );

			$filter_args = array(
				'throttler' => $throttler,
				'data'      => $data,
				'user_id'   => $user_id,
				'recipe_id' => $recipe_id,
			);

			// Allow people to override the throttler execution and introduce their own logic on runtime.
			$can_execute = apply_filters( 
				'automator_recipe_throttler_can_execute', 
				$throttler->can_execute( $user_id ),
				$filter_args
			);

			// If the recipe can execute, return false because the recipe is not throttled.
			if ( $can_execute ) {
				return false;
			}

		} catch ( \Exception $e ) {
			// Log the error.
			automator_log( 'Error creating throttler: ' . $e->getMessage(), 'error' );
			// Return false because the recipe can't be throttled due to an error.
			return false;
		}

		// Otherwise, return true.
		return true;
	}
}
