<?php
// phpcs:disable WordPress.DB
namespace Uncanny_Automator;

/**
 * This class is used to run any configurations before the plugin is initialized
 *
 * @package Uncanny_Automator
 */
class Automator_DB {

	/**
	 * The instance of the class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      \Uncanny_Automator\Automator_DB
	 */
	private static $instance;

	/**
	 *
	 */
	public function __construct() {
		add_action( 'automator_daily_healthcheck', array( __CLASS__, 'fix_automator_db_tables' ) );
		add_action( 'uap_options_migration', array( __CLASS__, 'uap_options_migration_func' ) );
	}

	/**
	 * Creates singleton instance of class
	 *
	 * @return Automator_DB $instance
	 * @since 1.0.0
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Validates if all Automator tables exists
	 *
	 * @param false $execute
	 *
	 * @return array
	 * @since 3.0
	 */
	public static function verify_base_tables( $execute = false ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		if ( $execute ) {
			self::create_tables();
			self::create_views();
		}

		$queries        = dbDelta( self::get_schema(), false );
		$missing_tables = array();

		foreach ( $queries as $table_name => $result ) {
			if ( "Created table $table_name" === $result ) {
				$missing_tables[] = $table_name;
			}
		}

		if ( 0 < count( $missing_tables ) ) {
			automator_update_option( 'automator_schema_missing_tables', $missing_tables );
		} else {
			automator_update_option( 'uap_database_version', AUTOMATOR_DATABASE_VERSION );
			automator_delete_option( 'automator_schema_missing_tables' );
			automator_delete_option( 'automator_schema_missing_views' );
		}

		return apply_filters( 'automator_db_missing_tables', $missing_tables );
	}

	/**
	 * @return array
	 */
	public static function verify_base_views() {
		$missing_views = self::all_views( true );

		if ( ! empty( $missing_views ) ) {
			automator_update_option( 'automator_schema_missing_views', $missing_views );
		}

		return $missing_views;
	}

	/**
	 * Return create queries for Automator tables
	 *
	 * @return string
	 * @since 3.0
	 */
	public static function get_schema() {

		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		// Automator Recipe log
		$tbl_recipe_log = $wpdb->prefix . 'uap_recipe_log';
		// Automator Recipe log meta.
		$tbl_recipe_log_meta = $wpdb->prefix . 'uap_recipe_log_meta';
		// Automator trigger log.
		$tbl_trigger_log = $wpdb->prefix . 'uap_trigger_log';
		// Automator trigger meta data log
		$tbl_trigger_log_meta = $wpdb->prefix . 'uap_trigger_log_meta';
		// Automator Action log
		$tbl_action_log = $wpdb->prefix . 'uap_action_log';
		// Automator action meta data log
		$tbl_action_log_meta = $wpdb->prefix . 'uap_action_log_meta';
		// Automator Closure Log
		$tbl_closure_log = $wpdb->prefix . 'uap_closure_log';
		// Automator closure meta data log
		$tbl_closure_log_meta = $wpdb->prefix . 'uap_closure_log_meta';
		// Tokens log
		$tbl_tokens_log = $wpdb->prefix . 'uap_tokens_log';
		// API retries log
		$tbl_api_response_log = $wpdb->prefix . 'uap_api_log_response';
		// API logs tables.
		$tbl_api_log = $wpdb->prefix . 'uap_api_log';
		// Count recipe runs
		$tbl_recipe_counts = $wpdb->prefix . 'uap_recipe_count';
		// Automator options
		$tbl_automator_options = $wpdb->prefix . 'uap_options';
		// Automator throttle.
		$tbl_automator_throttle = $wpdb->prefix . 'uap_recipe_throttle_log';

		return "CREATE TABLE {$tbl_recipe_log} (
`ID` bigint unsigned NOT NULL auto_increment,
`date_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
`user_id` bigint unsigned NOT NULL,
`automator_recipe_id` bigint unsigned NOT NULL,
`log_number` bigint unsigned,
`completed` tinyint(1) NOT NULL,
`run_number` mediumint unsigned NOT NULL DEFAULT 1,
PRIMARY KEY  (`ID`),
KEY completed (`completed`),
KEY user_id (`user_id`),
KEY automator_recipe_id (`automator_recipe_id`)
) ENGINE=InnoDB {$charset_collate};
CREATE TABLE {$tbl_recipe_log_meta} (
`ID` bigint NOT NULL AUTO_INCREMENT,
`user_id` bigint NOT NULL,
`recipe_id` bigint NOT NULL,
`recipe_log_id` bigint NOT NULL,
`meta_key` varchar(255) NOT NULL,
`meta_value` longtext NOT NULL,
PRIMARY KEY (`ID`),
KEY recipe_id (`recipe_id`),
KEY user_id (`user_id`)
) ENGINE=InnoDB {$charset_collate};
CREATE TABLE {$tbl_tokens_log} (
`ID` bigint NOT NULL AUTO_INCREMENT,
`recipe_id` bigint NOT NULL,
`recipe_log_id` bigint NOT NULL,
`run_number` bigint NOT NULL,
`tokens_record` longtext NOT NULL,
`date_added` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`ID`),
KEY recipe_id (`recipe_id`)
) ENGINE=InnoDB {$charset_collate};
CREATE TABLE {$tbl_api_response_log} (
`ID` bigint NOT NULL AUTO_INCREMENT,
`api_log_id` bigint NOT NULL,
`item_log_id` bigint NOT NULL,
`result` varchar(255) NOT NULL,
`message` text NOT NULL,
`date_added` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`ID`)
) ENGINE=InnoDB {$charset_collate};
CREATE TABLE {$tbl_trigger_log} (
`ID` bigint unsigned NOT NULL auto_increment,
`date_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
`user_id` bigint unsigned NOT NULL,
`automator_trigger_id` bigint unsigned NOT NULL,
`automator_recipe_id` bigint unsigned NOT NULL,
`automator_recipe_log_id` bigint unsigned NULL,
`completed` tinyint(1) unsigned NOT NULL,
PRIMARY KEY  (`ID`),
KEY user_id (`user_id`),
KEY completed (`completed`),
KEY automator_recipe_id (`automator_recipe_id`),
KEY automator_trigger_id (`automator_trigger_id`),
KEY automator_recipe_log_id (`automator_recipe_log_id`)
) ENGINE=InnoDB {$charset_collate};
CREATE TABLE {$tbl_trigger_log_meta} (
`ID` bigint unsigned NOT NULL auto_increment,
`user_id` bigint unsigned NOT NULL,
`automator_trigger_log_id` bigint unsigned NULL,
`automator_trigger_id` bigint unsigned NOT NULL,
`meta_key` varchar(255) DEFAULT '' NOT NULL,
`meta_value` longtext NULL,
`run_number` mediumint unsigned NOT NULL DEFAULT 1,
`run_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY  (`ID`),
KEY user_id (`user_id`),
KEY run_number (`run_number`),
KEY automator_trigger_id (`automator_trigger_id`),
KEY automator_trigger_log_id (`automator_trigger_log_id`),
KEY meta_key (meta_key(20))
) ENGINE=InnoDB {$charset_collate};
CREATE TABLE {$tbl_action_log} (
`ID` bigint unsigned NOT NULL auto_increment,
`date_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
`user_id` bigint unsigned NOT NULL,
`automator_action_id` bigint unsigned NOT NULL,
`automator_recipe_id` bigint unsigned NOT NULL,
`automator_recipe_log_id` bigint unsigned NULL,
`completed` tinyint(1) unsigned NOT NULL,
`error_message` longtext NULL,
PRIMARY KEY  (`ID`),
KEY user_id (`user_id`),
KEY completed (`completed`),
KEY automator_action_id (`automator_action_id`),
KEY automator_recipe_log_id (`automator_recipe_log_id`),
KEY automator_recipe_id (`automator_recipe_id`)
) ENGINE=InnoDB {$charset_collate};
CREATE TABLE {$tbl_action_log_meta} (
`ID` bigint unsigned NOT NULL auto_increment,
`user_id` bigint unsigned NOT NULL,
`automator_action_log_id` bigint unsigned NULL,
`automator_action_id` bigint unsigned NOT NULL,
`meta_key` varchar(255) DEFAULT '' NOT NULL,
`meta_value` longtext NULL,
PRIMARY KEY  (`ID`),
KEY user_id (`user_id`),
KEY automator_action_log_id (`automator_action_log_id`),
KEY automator_action_id (`automator_action_id`),
KEY meta_key (meta_key(20))
) ENGINE=InnoDB {$charset_collate};
CREATE TABLE {$tbl_closure_log} (
`ID` bigint unsigned NOT NULL auto_increment,
`date_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
`user_id` bigint unsigned NOT NULL,
`automator_closure_id` bigint unsigned NOT NULL,
`automator_recipe_id` bigint unsigned NOT NULL,
`automator_recipe_log_id` bigint unsigned NOT NULL,
`completed` tinyint(1) unsigned NOT NULL,
PRIMARY KEY  (`ID`),
KEY user_id (`user_id`),
KEY automator_recipe_id (`automator_recipe_id`),
KEY automator_closure_id (`automator_closure_id`),
KEY completed (`completed`)
) ENGINE=InnoDB {$charset_collate};
CREATE TABLE {$tbl_closure_log_meta} (
`ID` bigint unsigned NOT NULL auto_increment,
`user_id` bigint unsigned NOT NULL,
`automator_closure_id` bigint unsigned NOT NULL,
`automator_closure_log_id` bigint unsigned NOT NULL,
`meta_key` varchar(255) DEFAULT '' NOT NULL,
`meta_value` longtext NULL,
PRIMARY KEY  (`ID`),
KEY user_id (`user_id`),
KEY automator_closure_id (`automator_closure_id`),
KEY meta_key (meta_key(15))
) ENGINE=InnoDB {$charset_collate};
CREATE TABLE {$tbl_api_log} (
`ID` bigint unsigned NOT NULL auto_increment,
`date_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
`type` varchar(255) DEFAULT '' NOT NULL,
`recipe_log_id` bigint unsigned NOT NULL,
`item_log_id` bigint unsigned NOT NULL,
`endpoint` varchar(255) DEFAULT '' NULL,
`params` longtext NULL,
`request` longtext NULL,
`response` longtext NULL,
`status` varchar(255) DEFAULT '' NULL,
`price` bigint unsigned NULL,
`balance` bigint unsigned NULL,
`time_spent` bigint unsigned NULL,
`notes` longtext NULL,
PRIMARY KEY  (`ID`),
KEY item_log_id (`item_log_id`)
) ENGINE=InnoDB {$charset_collate};
CREATE TABLE {$tbl_recipe_counts} (
`ID` bigint unsigned NOT NULL auto_increment,
`recipe_id` bigint unsigned NOT NULL,
`runs` bigint unsigned DEFAULT 0 NOT NULL,
PRIMARY KEY  (`ID`),
KEY recipe_id (`recipe_id`)
) ENGINE=InnoDB {$charset_collate};
CREATE TABLE {$tbl_automator_options} (
`option_id` bigint unsigned NOT NULL AUTO_INCREMENT,
`option_name` varchar(191) NOT NULL DEFAULT '',
`option_value` longtext NOT NULL,
`autoload` varchar(8) NOT NULL DEFAULT 'yes',
`type` varchar(10) NOT NULL DEFAULT 'string',
PRIMARY KEY (`option_id`),
UNIQUE KEY `option_name` (`option_name`),
KEY `autoload` (`autoload`)
) ENGINE=InnoDB {$charset_collate};
CREATE TABLE {$tbl_automator_throttle} (
`ID` bigint unsigned NOT NULL auto_increment,
`date_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
`user_id` bigint unsigned NOT NULL,
`recipe_id` bigint unsigned NOT NULL,
`meta_key` varchar(255) NOT NULL,
`last_run` int unsigned NOT NULL,
PRIMARY KEY (`ID`),
UNIQUE KEY `user_recipe_key` (`user_id`,`recipe_id`,`meta_key`),
KEY `cleanup` (`last_run`)
) ENGINE=InnoDB {$charset_collate};
";
	}

	/**
	 * The code that runs during plugin activation.
	 *
	 * Update DB code to use InnoDB Engine instead of MyISAM.
	 * Indexes updated
	 *
	 * @since    1.0.0
	 * @version  2.5
	 * @author   Saad
	 */
	public static function activation() {

		do_action( 'automator_activation_before' );

		automator_update_option( 'automator_over_time', array( 'installed_date' => time() ) );

		$db_version = automator_get_option( 'uap_database_version', null );

		self::async_wp_options_migration();

		if ( null !== $db_version && (string) AUTOMATOR_DATABASE_VERSION === (string) $db_version ) {
			// bail. No db upgrade needed!
			return;
		}

		self::create_tables();

		do_action( 'automator_activation_after' );
	}

	/**
	 * Create tables
	 *
	 * @since 3.0
	 */
	public static function create_tables() {
		$sql = self::get_schema();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		automator_update_option( 'uap_database_version', AUTOMATOR_DATABASE_VERSION );
	}

	/**
	 * Added this to fix MySQL 8 AUTO_INCREMENT issue
	 * with already created tables
	 *
	 * @since  2.9
	 * @author Saad S.
	 */
	public function mysql_8_auto_increment_fix() {
		global $wpdb;

		$wpdb->query( "ANALYZE TABLE `{$wpdb->prefix}uap_recipe_log`;" );
		$wpdb->query( "ANALYZE TABLE `{$wpdb->prefix}uap_action_log`;" );
		$wpdb->query( "ANALYZE TABLE `{$wpdb->prefix}uap_action_log_meta`;" );
		$wpdb->query( "ANALYZE TABLE `{$wpdb->prefix}uap_closure_log`;" );
		$wpdb->query( "ANALYZE TABLE `{$wpdb->prefix}uap_closure_log_meta`;" );
		$wpdb->query( "ANALYZE TABLE `{$wpdb->prefix}uap_trigger_log`;" );
		$wpdb->query( "ANALYZE TABLE `{$wpdb->prefix}uap_trigger_log_meta`;" );
		$wpdb->query( "ANALYZE TABLE `{$wpdb->prefix}uap_api_log`;" );
	}

	/**
	 * Call views instead of complex queries on log pages
	 *
	 * @version 2.5.1
	 * @author  Saad
	 */
	public function automator_generate_views() {

		do_action( 'automator_database_views_before' );

		if ( AUTOMATOR_DATABASE_VIEWS_VERSION !== automator_get_option( 'uap_database_views_version', 0 ) ) {
			self::create_views();
		}

		do_action( 'automator_activation_views_after' );
	}

	/**
	 * Generate VIEWS
	 *
	 * @since 3.0
	 */
	public static function create_views() {

		// Return empty if VIEWS are disabled
		if ( ! AUTOMATOR_DATABASE_VIEWS_ENABLED ) {
			return;
		}

		global $wpdb;

		$recipe_view       = "{$wpdb->prefix}uap_recipe_logs_view";
		$recipe_view_query = self::recipe_log_view_query();
		$wpdb->query( "CREATE OR REPLACE VIEW $recipe_view AS $recipe_view_query" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$trigger_view       = "{$wpdb->prefix}uap_trigger_logs_view";
		$trigger_view_query = self::trigger_log_view_query();

		$wpdb->query( "CREATE OR REPLACE VIEW $trigger_view AS $trigger_view_query" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$action_view       = "{$wpdb->prefix}uap_action_logs_view";
		$action_view_query = self::action_log_view_query();

		$wpdb->query( "CREATE OR REPLACE VIEW $action_view AS $action_view_query" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$api_view       = "{$wpdb->prefix}uap_api_logs_view";
		$api_view_query = self::api_log_view_query();

		$wpdb->query( "CREATE OR REPLACE VIEW $api_view AS $api_view_query" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		automator_update_option( 'uap_database_views_version', AUTOMATOR_DATABASE_VIEWS_VERSION );
	}

	/**
	 * Get the query for recipe log view.
	 *
	 * @since 6.2 - Added log_number.
	 *
	 * @return string Filtered SQL query for the recipe log view.
	 */
	public static function recipe_log_view_query() {

		global $wpdb;

		// Base query to fetch recipe log data.
		$query = "SELECT 
					r.ID AS recipe_log_id,
					r.user_id,
					r.date_time AS recipe_date_time,
					r.completed AS recipe_completed,
					r.run_number,
					r.log_number,
					r.completed,
					r.automator_recipe_id,
					u.user_email,
					u.display_name,
					p.post_title AS recipe_title
				FROM {$wpdb->prefix}uap_recipe_log r
				LEFT JOIN {$wpdb->users} u
					ON u.ID = r.user_id
				JOIN {$wpdb->posts} p
					ON p.ID = r.automator_recipe_id";

		/**
		 * Filter the recipe log view query.
		 *
		 * @param string $query SQL query string.
		 */
		return apply_filters( 'automator_recipe_log_view_query', $query );
	}

	/**
	 * @return string
	 */
	public static function trigger_log_view_query() {
		global $wpdb;

		return apply_filters(
			'automator_trigger_log_view_query',
			"SELECT u.ID AS user_id, u.user_email,
				u.display_name,
				t.automator_trigger_id,
				t.date_time AS trigger_date,
				t.completed AS trigger_completed,
				t.automator_recipe_id,
				t.ID,
				pt.post_title AS trigger_title,
				tm.meta_value AS trigger_sentence,
				tm.run_number AS trigger_run_number,
				tm.run_time AS trigger_run_time,
				pm.meta_value AS trigger_total_times,
				p.post_title AS recipe_title,
				t.automator_recipe_log_id AS recipe_log_id,
				r.date_time AS recipe_date_time,
				r.completed AS recipe_completed,
				r.run_number AS recipe_run_number
			FROM {$wpdb->prefix}uap_trigger_log t
			LEFT JOIN {$wpdb->users} u
			ON u.ID = t.user_id
			LEFT JOIN {$wpdb->posts} p
			ON p.ID = t.automator_recipe_id
			LEFT JOIN {$wpdb->posts} pt
			ON pt.ID = t.automator_trigger_id
			LEFT JOIN {$wpdb->prefix}uap_trigger_log_meta tm
			ON tm.automator_trigger_log_id = t.ID AND tm.meta_key = 'sentence_human_readable'
			LEFT JOIN {$wpdb->prefix}uap_recipe_log r
			ON t.automator_recipe_log_id = r.ID
			LEFT JOIN {$wpdb->postmeta} pm
			ON pm.post_id = t.automator_trigger_id AND pm.meta_key = 'NUMTIMES'"
		);
	}

	/**
	 * @param $group_by
	 *
	 * @return string
	 */
	public static function action_log_view_query( $group_by = true ) {
		global $wpdb;
		$qry = "SELECT a.automator_action_id,
					a.date_time AS action_date,
					a.completed AS action_completed,
					a.error_message,
					a.automator_recipe_id,
					a.ID AS action_log_id,
					a.automator_recipe_log_id AS recipe_log_id,
					r.date_time AS recipe_date_time,
					r.completed AS recipe_completed,
					r.run_number AS recipe_run_number,
					pa.post_title AS action_title,
					am.meta_value AS action_sentence,
					p.post_title AS recipe_title,
					u.ID AS user_id,
					u.user_email,
					u.display_name
			FROM {$wpdb->prefix}uap_action_log a
			LEFT JOIN {$wpdb->prefix}uap_recipe_log r
			ON a.automator_recipe_log_id = r.ID
			LEFT JOIN {$wpdb->posts} p
			ON p.ID = a.automator_recipe_id
			JOIN {$wpdb->posts} pa
			ON pa.ID = a.automator_action_id
			LEFT JOIN {$wpdb->prefix}uap_action_log_meta am
			ON a.automator_action_id = am.automator_action_id AND am.automator_action_log_id = a.ID AND am.user_id = a.user_id AND am.meta_key = 'sentence_human_readable_html'
			LEFT JOIN {$wpdb->users} u
			ON a.user_id = u.ID";
		if ( $group_by ) {
			$qry .= ' GROUP BY a.ID';
		}

		return apply_filters(
			'automator_action_log_view_query',
			$qry
		);
	}

	/**
	 * @param $group_by
	 *
	 * @return string
	 */
	public static function api_log_view_query() {

		global $wpdb;
		$qry = "SELECT
				api.ID,
				api.date_time AS date,
				u.user_email,
				u.display_name,
				u.ID AS user_id,
				pr.post_title AS recipe_title,
				rl.automator_recipe_id,
				al.automator_recipe_log_id AS recipe_log_id,
				rl.date_time AS recipe_date_time,
				rl.completed AS recipe_completed,
				rl.run_number AS recipe_run_number,
				pa.post_title AS title,
				asen.meta_value AS sentence,
				al.automator_action_id as item_id,
				al.completed AS completed,
				al.error_message as error_message,
				api.type,
				api.item_log_id,
				api.status,
				api.params,
				api.price,
				api.balance,
				api.notes,
				api.time_spent,
				api.endpoint
				FROM {$wpdb->prefix}uap_api_log api
				LEFT JOIN {$wpdb->prefix}uap_action_log al
				ON al.ID = api.item_log_id
				LEFT JOIN {$wpdb->prefix}uap_recipe_log rl
				ON al.automator_recipe_log_id = rl.ID
				LEFT JOIN {$wpdb->posts} pr
				ON pr.ID = al.automator_recipe_id
				JOIN {$wpdb->posts} pa
				ON pa.ID = al.automator_action_id
				LEFT JOIN {$wpdb->prefix}uap_action_log_meta asen
				ON asen.automator_action_log_id = al.ID AND asen.meta_key = 'sentence_human_readable_html'
				LEFT JOIN {$wpdb->users} u
				ON al.user_id = u.ID
				WHERE api.type = 'action'
				UNION SELECT
				api.ID,
				api.date_time AS date,
				u.user_email,
				u.display_name,
				u.ID AS user_id,
				pr.post_title AS recipe_title,
				rl.automator_recipe_id,
				tl.automator_recipe_log_id as recipe_log_id,
				rl.date_time AS recipe_date_time,
				rl.completed AS recipe_completed,
				rl.run_number AS recipe_run_number,
				pt.post_title AS title,
				tsen.meta_value AS sentence,
				tl.automator_trigger_id as item_id,
				tl.completed AS completed,
				'' as error_message,
				api.type,
				api.item_log_id,
				api.status,
				api.params,
				api.price,
				api.balance,
				api.notes,
				api.time_spent,
				api.endpoint
				FROM {$wpdb->prefix}uap_api_log api
				LEFT JOIN {$wpdb->prefix}uap_trigger_log tl
				ON tl.ID = api.item_log_id
				LEFT JOIN {$wpdb->prefix}uap_recipe_log rl
				ON tl.automator_recipe_log_id = rl.ID
				LEFT JOIN {$wpdb->posts} pr
				ON pr.ID = tl.automator_recipe_id
				JOIN {$wpdb->posts} pt
				ON pt.ID = tl.automator_trigger_id
				LEFT JOIN {$wpdb->prefix}uap_trigger_log_meta tsen
				ON tsen.automator_trigger_log_id = tl.ID AND tsen.meta_key = 'sentence_human_readable'
				LEFT JOIN {$wpdb->users} u
				ON tl.user_id = u.ID
				WHERE api.type = 'trigger'";

		return apply_filters(
			'automator_api_log_view_query',
			$qry
		);
	}

	/**
	 * Check if specific VIEW is missing.
	 *
	 * @param $type
	 *
	 * @return bool
	 */
	public static function is_view_exists( $type = 'recipe' ) {

		if ( ! AUTOMATOR_DATABASE_VIEWS_ENABLED ) {
			return false;
		}

		global $wpdb;
		$recipe_view = '';
		if ( 'recipe' === $type ) {
			$recipe_view = "{$wpdb->prefix}uap_recipe_logs_view";
		}
		if ( 'trigger' === $type ) {
			$recipe_view = "{$wpdb->prefix}uap_trigger_logs_view";
		}
		if ( 'action' === $type ) {
			$recipe_view = "{$wpdb->prefix}uap_action_logs_view";
		}
		if ( 'api' === $type ) {
			$recipe_view = "{$wpdb->prefix}uap_api_logs_view";
		}

		if ( empty( $recipe_view ) ) {
			return false;
		}
		$results = self::all_views( true );
		if ( ! in_array( $recipe_view, $results, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if all Automator VIEWS exists. Return empty if all VIEWS exists else only the ones that are missing.
	 *
	 * @param $return_missing
	 *
	 * @return array
	 * @version 3.0
	 */
	public static function all_views( $return_missing = false ) {

		// Return empty if VIEWS are disabled
		if ( ! AUTOMATOR_DATABASE_VIEWS_ENABLED ) {
			return array();
		}

		global $wpdb;
		$db      = DB_NAME;
		$results = $wpdb->get_results( "SHOW FULL TABLES IN `$db` WHERE TABLE_TYPE LIKE '%VIEW%'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$return  = array(
			"{$wpdb->prefix}uap_recipe_logs_view",
			"{$wpdb->prefix}uap_trigger_logs_view",
			"{$wpdb->prefix}uap_action_logs_view",
			"{$wpdb->prefix}uap_api_logs_view",
		);

		if ( ! $results ) {
			return $return_missing ? $return : array();
		}
		foreach ( $results as $r ) {
			if ( ! is_object( $r ) ) {
				continue;
			}
			foreach ( $r as $rr ) {
				$return = array_diff( $return, array( $rr ) );
			}
		}

		return $return;
	}

	/**
	 * Returns the list of Automator views.
	 *
	 * @return array The list of Automator views.
	 */
	public static function get_views() {

		global $wpdb;

		return array(
			'uap_recipe_logs_view'  => $wpdb->prefix . 'uap_recipe_logs_view',
			'uap_trigger_logs_view' => $wpdb->prefix . 'uap_trigger_logs_view',
			'uap_action_logs_view'  => $wpdb->prefix . 'uap_action_logs_view',
			'uap_api_logs_view'     => $wpdb->prefix . 'uap_api_logs_view',
		);
	}

	/**
	 * Purges a specific table.
	 *
	 * @param string $table_name
	 *
	 * @return bool|int|\mysqli_result|null
	 */
	public static function empty_table( $table_name = '' ) {

		global $wpdb;

		$prefixed_tb_name = $wpdb->prefix . $table_name;

		return $wpdb->query( "TRUNCATE `$prefixed_tb_name`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Drops the selected view.
	 *
	 * @param string $view_name The name of the view.
	 *
	 * @return boolean True if view was dropped. Otherwise, false.
	 */
	public static function drop_view( $view_name = '' ) {

		global $wpdb;

		$dropped = false;

		$views = self::get_views();

		// Only allow dropping of view owned by Automator.
		if ( in_array( $view_name, array_keys( $views ), true ) ) {

			$dropped = $wpdb->query(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				str_replace(
					"'",
					'',
					$wpdb->prepare(
						'DROP VIEW IF EXISTS `%s`',
						esc_sql( filter_var( $views[ $view_name ], FILTER_SANITIZE_FULL_SPECIAL_CHARS ) )
					)
				)
			);

		}

		return $dropped;
	}

	/**
	 * @return true
	 */
	public static function purge_tables() {

		global $wpdb;

		$wpdb->query( "TRUNCATE TABLE `{$wpdb->prefix}uap_recipe_log`;" );
		$wpdb->query( "TRUNCATE TABLE `{$wpdb->prefix}uap_recipe_log_meta`;" );
		$wpdb->query( "TRUNCATE TABLE `{$wpdb->prefix}uap_action_log`;" );
		$wpdb->query( "TRUNCATE TABLE `{$wpdb->prefix}uap_action_log_meta`;" );
		$wpdb->query( "TRUNCATE TABLE `{$wpdb->prefix}uap_closure_log`;" );
		$wpdb->query( "TRUNCATE TABLE `{$wpdb->prefix}uap_closure_log_meta`;" );
		$wpdb->query( "TRUNCATE TABLE `{$wpdb->prefix}uap_trigger_log`;" );
		$wpdb->query( "TRUNCATE TABLE `{$wpdb->prefix}uap_trigger_log_meta`;" );
		$wpdb->query( "TRUNCATE TABLE `{$wpdb->prefix}uap_api_log`;" );
		$wpdb->query( "TRUNCATE TABLE `{$wpdb->prefix}uap_api_log_response`;" );
		$wpdb->query( "TRUNCATE TABLE `{$wpdb->prefix}uap_tokens_log`;" );

		do_action( 'automator_tables_purged' );

		return true;
	}

	/**
	 * @return void
	 */
	public static function fix_automator_db_tables() {
		$missing_views = self::all_views( true );

		// If nothing is missing, bail
		if ( empty( $missing_views ) ) {
			return;
		}

		self::verify_base_tables( true );
	}

	/**
	 * Schedule a one time event to update the uap_options table with wp_options data
	 * @return void
	 */
	public static function async_wp_options_migration() {
		if ( '' !== get_option( 'uncanny_automator_v6_options_migrated', '' ) ) {
			return;
		}
		wp_schedule_single_event( time() + 8, 'uap_options_migration' );
	}

	/**
	 * Fetch all the uap_options that have empty values and update them with wp_options data
	 * @return void
	 */
	public static function uap_options_migration_func() {
		global $wpdb;

		$like_patterns = array(
			'automator_%',
			'uncanny_automator_%',
			'uap_%',
			'_uoa_%',
			'_uncanny_automator%',
			'_uncanny_credits%',
			'UO_REDIRECTURL_%',
			'_uncannyowl_zoom_%',
			'uoa_setup_wiz_has_connected',
			'zoho_campaigns_%',
			'USERROLEADDED_migrated',
			'_uncannyowl_slack_settings',
			'affwp_insert_referral_migrated',
			'ua_facebook%',
			'_uncannyowl_gtt%',
		);

		// Generate LIKE conditions dynamically
		$like_conditions = array_map(
			function ( $pattern ) use ( $wpdb ) {
				return $wpdb->prepare( 'option_name LIKE %s', $pattern );
			},
			$like_patterns
		);

		// Combine all conditions with OR
		$conditions_query = implode( ' OR ', $like_conditions );

		// SQL query for UPSERT (Insert or Update if option_name exists)
		$sql_query = "INSERT INTO {$wpdb->prefix}uap_options (option_name, option_value, autoload)
SELECT option_name, option_value, autoload
FROM {$wpdb->prefix}options
WHERE $conditions_query
ON DUPLICATE KEY UPDATE
	option_value = VALUES(option_value),
	                   autoload = VALUES(autoload)";

		// Execute the query
		$wpdb->query( $sql_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$async_actions = self::get_automator_async_run_with_hash();

		if ( ! empty( $async_actions ) ) {
			foreach ( $async_actions as $hash ) {
				$action = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->options} WHERE option_name = %s", $hash['hash'] ) );
				$wpdb->insert(
					$wpdb->prefix . 'uap_options',
					array(
						'option_name'  => $action->option_name,
						'option_value' => $action->option_value,
						'autoload'     => $action->autoload,
					),
					array(
						'%s',
						'%s',
						'%s',
					)
				);
			}
		}

		add_option( 'uncanny_automator_v6_options_migrated', time() );
	}

	/**
	 * @return array
	 */
	public static function get_automator_async_run_with_hash() {
		// Ensure the Action Scheduler library is available.
		if ( ! class_exists( 'ActionScheduler' ) ) {
			return array();
		}

		$query_args = array(
			'hook'     => 'automator_async_run_with_hash',
			'status'   => \ActionScheduler_Store::STATUS_PENDING, // Change if needed: 'complete', 'failed', 'canceled'
			'per_page' => -1, // Retrieve all actions
		);

		$store   = \ActionScheduler::store();
		$actions = $store->query_actions( $query_args );

		$results = array();

		if ( ! empty( $actions ) ) {
			foreach ( $actions as $action_id ) {
				$args      = $store->fetch_action( $action_id )->get_args();
				$first_arg = ! empty( $args ) ? $args[0] : null; // Get the first argument

				$results[] = array(
					'hash' => $first_arg,
				);
			}
		}

		return $results;
	}
}
