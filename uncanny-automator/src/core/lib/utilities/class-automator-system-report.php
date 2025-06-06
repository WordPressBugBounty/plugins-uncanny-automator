<?php

namespace Uncanny_Automator;

/**
 * Class System_Report
 *
 * @package Uncanny_Automator
 */
class Automator_System_Report {
	/**
	 * @var
	 */
	public static $instance;
	/**
	 * @var
	 */
	public $available_updates;

	/**
	 * @return Automator_System_Report
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @return array
	 */
	public function get() {

		$system_status = apply_filters(
			'automator_system_report_get',
			array(
				'automator_stats'    => $this->get_automator_stats(),
				'environment'        => $this->get_environment_info(),
				'database'           => $this->get_database_info(),
				'active_plugins'     => $this->get_active_plugins(),
				'inactive_plugins'   => $this->get_inactive_plugins(),
				'dropins_mu_plugins' => $this->get_dropins_mu_plugins(),
				'theme'              => $this->get_theme_info(),
			)
		);

		return $system_status;
	}

	/**
	 * Add prefix to table.
	 *
	 * @param string $table Table name.
	 *
	 * @return string
	 */
	protected function add_db_table_prefix( $table ) {
		global $wpdb;

		return $wpdb->prefix . $table;
	}

	/**
	 * Notation to numbers.
	 *
	 * This function transforms the php.ini notation for numbers (like '2M') to an integer.
	 *
	 * @param string $size Size value.
	 *
	 * @return int
	 */
	public function automator_string_to_num( $size ) {
		$l   = substr( $size, - 1 );
		$ret = (int) substr( $size, 0, - 1 );
		switch ( strtoupper( $l ) ) {
			case 'P':
				$ret *= 1024;
				// Fall-through intentional.
			case 'T':
				$ret *= 1024;
				// Fall-through intentional.
			case 'G':
				$ret *= 1024;
				// Fall-through intentional.
			case 'M':
				$ret *= 1024;
				// Fall-through intentional.
			case 'K':
				$ret *= 1024;
				// Fall-through intentional.
		}

		return $ret;
	}

	/**
	 * @param string[] $fields
	 *
	 * @return array
	 */
	public function get_environment_info( $fields = array( 'environment' ) ) {
		$enable_remote_post = $this->check_if_field_item_exists(
			'environment',
			array(
				'remote_post_successful',
				'remote_post_response',
			),
			$fields
		);

		$enable_remote_get = $this->check_if_field_item_exists(
			'environment',
			array(
				'remote_get_successful',
				'remote_get_response',
			),
			$fields
		);

		// Figure out cURL version, if installed.
		$curl_version = '';
		if ( function_exists( 'curl_version' ) ) {
			$curl_version = curl_version();
			$curl_version = $curl_version['version'] . ', ' . $curl_version['ssl_version'];
		} elseif ( extension_loaded( 'curl' ) ) {
			$curl_version = esc_html__( 'cURL installed but unable to retrieve version.', 'uncanny-automator' );
		}

		// WP memory limit.
		$wp_memory_limit = $this->automator_string_to_num( WP_MEMORY_LIMIT );
		if ( function_exists( 'memory_get_usage' ) ) {
			$wp_memory_limit = max( $wp_memory_limit, $this->automator_string_to_num( @ini_get( 'memory_limit' ) ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
		$automator_check_url = 'https://licensing.uncannyowl.com/automator-check?version=' . AUTOMATOR_PLUGIN_VERSION;
		// Test POST requests.
		$post_response_successful = null;
		$post_response_code       = null;
		if ( $enable_remote_post ) {
			$post_response_code = get_transient( 'automator_test_remote_post' );

			if ( false === $post_response_code || is_wp_error( $post_response_code ) ) {
				$response = wp_safe_remote_post(
					$automator_check_url,
					array(
						'timeout'    => 5,
						'user-agent' => 'Automator/' . AUTOMATOR_PLUGIN_VERSION,
						'body'       => array(
							'cmd' => '_notify-validate',
						),
						'headers'    => array(
							'X-UO-Destination' => 'ap',
						),
					)
				);
				if ( ! is_wp_error( $response ) ) {
					$post_response_code = $response['response']['code'];
				}
				set_transient( 'automator_test_remote_post', $post_response_code, HOUR_IN_SECONDS );
			}

			$post_response_successful = ! is_wp_error( $post_response_code ) && $post_response_code >= 200 && $post_response_code < 300;
		}

		// Test GET requests.
		$get_response_successful = null;
		$get_response_code       = null;
		if ( $enable_remote_get ) {
			$get_response_code = get_transient( 'automator_test_remote_get' );

			if ( false === $get_response_code || is_wp_error( $get_response_code ) ) {
				$response = wp_safe_remote_get( $automator_check_url );
				if ( ! is_wp_error( $response ) ) {
					$get_response_code = $response['response']['code'];
				}
				set_transient( 'automator_test_remote_get', $get_response_code, HOUR_IN_SECONDS );
			}

			$get_response_successful = ! is_wp_error( $get_response_code ) && $get_response_code >= 200 && $get_response_code < 300;
		}

		$database_version = $this->get_server_database_version();
		$last_updated     = automator_get_option( 'automator_last_updated' );

		if ( ! empty( $last_updated ) ) {
			$last_updated = sprintf( '(Updated: %s)', $last_updated );
		}

		// Return all environment info. Described by JSON Schema.
		return array(
			'home_url'                => get_option( 'home' ),
			'site_url'                => get_option( 'siteurl' ),
			'version'                 => sprintf( '%s %s', AUTOMATOR_PLUGIN_VERSION, $last_updated ),
			'pro_version'             => defined( 'AUTOMATOR_PRO_PLUGIN_VERSION' ) ? AUTOMATOR_PRO_PLUGIN_VERSION : null,
			'log_directory'           => UA_DEBUG_LOGS_DIR,
			'log_directory_writable'  => wp_is_writable( dirname( UA_DEBUG_LOGS_DIR . 'test.log' ) ),
			'wp_version'              => get_bloginfo( 'version' ),
			'wp_multisite'            => is_multisite(),
			'wp_memory_limit'         => $wp_memory_limit,
			'wp_debug_mode'           => ( defined( 'WP_DEBUG' ) && WP_DEBUG ),
			'wp_cron'                 => ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ),
			'language'                => get_locale(),
			'external_object_cache'   => wp_using_ext_object_cache(),
			'server_info'             => isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '',
			'php_version'             => phpversion(),
			'php_post_max_size'       => $this->automator_string_to_num( ini_get( 'post_max_size' ) ),
			'php_max_execution_time'  => (int) ini_get( 'max_execution_time' ),
			'php_max_input_vars'      => (int) ini_get( 'max_input_vars' ),
			'curl_version'            => $curl_version,
			'max_upload_size'         => wp_max_upload_size(),
			'mysql_version'           => $database_version['number'],
			'mysql_version_string'    => $database_version['string'],
			'default_timezone'        => date_default_timezone_get(),
			'mbstring_enabled'        => extension_loaded( 'mbstring' ),
			'remote_post_successful'  => $post_response_successful,
			'remote_post_response'    => is_wp_error( $post_response_code ) ? $post_response_code->get_error_message() : $post_response_code,
			'remote_get_successful'   => $get_response_successful,
			'remote_get_response'     => is_wp_error( $get_response_code ) ? $get_response_code->get_error_message() : $get_response_code,
			'automator_cache'         => Automator()->cache->is_cache_enabled(),
			'automator_bg_processing' => '1' === automator_get_option( Background_Actions::OPTION_NAME, '1' ) ? true : false,
			'permalink_structure'     => get_option( 'permalink_structure' ),
		);
	}

	/**
	 * @return array|string[]
	 */
	public function get_server_database_version() {
		global $wpdb;

		if ( empty( $wpdb->is_mysql ) ) {
			return array(
				'string' => '',
				'number' => '',
			);
		}

		// phpcs:disable WordPress.DB.RestrictedFunctions, PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
		$server_info = '"mysqli_get_server_info" does not exist.';
		if ( \function_exists( 'mysqli_get_server_info' ) ) {
			$server_info = mysqli_get_server_info( $wpdb->dbh );
		}

		// phpcs:enable WordPress.DB.RestrictedFunctions, PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved

		return array(
			'string' => $server_info,
			'number' => preg_replace( '/([^\d.]+).*/', '', $server_info ),
		);
	}

	/**
	 * @param $section
	 * @param $items
	 * @param $fields
	 *
	 * @return bool
	 */
	private function check_if_field_item_exists( $section, $items, $fields ) {
		if ( ! in_array( $section, $fields, true ) ) {
			return false;
		}

		$exclude = array();
		foreach ( $fields as $field ) {
			$values = explode( '.', $field );

			if ( $section !== $values[0] || empty( $values[1] ) ) {
				continue;
			}

			$exclude[] = $values[1];
		}

		return 0 <= count( array_intersect( $items, $exclude ) );
	}

	/**
	 *
	 */
	public function output_tables_info() {
		$missing_tables = Automator_DB::verify_base_tables();
		if ( 0 === count( $missing_tables ) ) {
			return;
		}
		?>

		<br>
		<strong style="color:#a00;">
			<span class="dashicons dashicons-warning"></span>
			<?php
			echo esc_html(
				sprintf(
				// translators: Comma seperated list of missing tables.
					esc_html__( 'Missing base tables: %s. Some Automator functionality may not work as expected.', 'uncanny-automator' ),
					implode( ', ', $missing_tables )
				)
			);
			?>
		</strong>

		<?php
	}

	/**
	 * @return array
	 */
	public function get_database_info() {
		global $wpdb;

		$tables        = array();
		$database_size = array();

		// It is not possible to get the database name from some classes that replace wpdb (e.g., HyperDB)
		// and that is why this if condition is needed.
		if ( defined( 'DB_NAME' ) ) {

			$database_table_information = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT
					    TABLE_NAME AS 'name',
						ENGINE AS 'engine',
					    round( ( data_length / 1024 / 1024 ), 2 ) 'data',
					    round( ( index_length / 1024 / 1024 ), 2 ) 'index'
					FROM information_schema.TABLES
					WHERE TABLE_SCHEMA = %s
					ORDER BY name ASC;",
					DB_NAME
				)
			);

			// Automator Core tables to check existence of.
			$core_tables = (object) apply_filters(
				'automator_database_tables',
				(object) array(
					'recipe_logs'        => 'uap_recipe_logs_view',
					'trigger_logs'       => 'uap_trigger_logs_view',
					'action_logs'        => 'uap_action_logs_view',
					'api_logs'           => 'uap_api_logs_view',
					'recipe'             => 'uap_recipe_log',
					'recipe_meta'        => 'uap_recipe_log_meta',
					'trigger'            => 'uap_trigger_log',
					'trigger_meta'       => 'uap_trigger_log_meta',
					'action'             => 'uap_action_log',
					'action_meta'        => 'uap_action_log_meta',
					'closure'            => 'uap_closure_log',
					'closure_meta'       => 'uap_closure_log_meta',
					'api'                => 'uap_api_log',
					'api_response_logs'  => 'uap_api_log_response',
					'tokens_logs'        => 'uap_tokens_log',
					'recipe_count'       => 'uap_recipe_count',
					'automator_options'  => 'uap_options',
					'automator_throttle' => 'uap_recipe_throttle_log',
				)
			);

			/**
			 * Adding the prefix to the tables array, for backwards compatibility.
			 *
			 * If we changed the tables above to include the prefix, then any filters against that table could break.
			 */
			$core_tables = array_map( array( $this, 'add_db_table_prefix' ), (array) $core_tables );

			/**
			 * Organize Automator and non-Automator tables separately for display purposes later.
			 *
			 * To ensure we include all Automator tables, even if they do not exist, pre-populate the Automator array with all the tables.
			 */
			$tables = array(
				'automator' => array_fill_keys( $core_tables, false ),
				'other'     => array(),
			);

			$database_size = array(
				'data'  => 0,
				'index' => 0,
			);

			$site_tables_prefix = $wpdb->get_blog_prefix( get_current_blog_id() );

			$global_tables = $wpdb->tables( 'global', true );

			foreach ( $database_table_information as $table ) {

				$table_name   = isset( $table->name ) ? $table->name : null;
				$table_index  = isset( $table->index ) ? $table->index : null;
				$table_engine = isset( $table->engine ) ? $table->engine : null;
				$table_data   = isset( $table->data ) ? $table->data : null;

				// Only include tables matching the prefix of the current site, this is to prevent displaying all tables on a MS install not relating to the current.
				if ( is_multisite() && 0 !== strpos( $table_name, $site_tables_prefix ) && ! in_array( $table_name, $global_tables, true ) ) {
					continue; // skip
				}

				$table_type = in_array( $table_name, $core_tables, true ) ? 'automator' : 'other';

				$tables[ $table_type ][ $table_name ] = array(
					'data'   => $table_data,
					'index'  => $table_index,
					'engine' => $table_engine,
				);

				$database_size['data']  += $table_data;
				$database_size['index'] += $table_index;

			}
		}

		// Return all database info. Described by JSON Schema.
		return array(
			'automator_database_version'                => automator_get_option( 'uap_database_version' ),
			'automator_database_available_version'      => AUTOMATOR_DATABASE_VERSION,
			'automator_database_views_version'          => automator_get_option( 'uap_database_views_version' ),
			'automator_database_available_view_version' => AUTOMATOR_DATABASE_VIEWS_VERSION,
			'database_prefix'                           => $wpdb->prefix,
			'database_tables'                           => $tables,
			'database_size'                             => $database_size,
		);
	}

	/**
	 * @return array
	 */
	public function get_active_plugins() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		if ( ! function_exists( 'get_plugin_data' ) ) {
			return array();
		}

		$active_plugins = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$network_activated_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
			$active_plugins            = array_merge( $active_plugins, $network_activated_plugins );
		}

		$active_plugins_data = array();

		foreach ( $active_plugins as $plugin ) {
			if ( is_file( WP_PLUGIN_DIR . '/' . $plugin ) ) {
				$data                  = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
				$active_plugins_data[] = $this->format_plugin_data( $plugin, $data );
			}
		}

		return $active_plugins_data;
	}

	/**
	 * Get the total sizes of the table.
	 *
	 * @return float
	 */
	public static function get_tables_total_size() {

		global $wpdb;

		$sum = 0;

		$tables = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery
				"SELECT TABLE_NAME AS `table_name`, ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS `size_mb`
					FROM information_schema.TABLES
					WHERE TABLE_SCHEMA = %s
					AND TABLE_NAME like %s AND TABLE_NAME NOT LIKE %s
					ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC;
				",
				DB_NAME,
				'%%uap%%',
				'%%_view'
			),
			ARRAY_A
		);

		foreach ( $tables as $table ) {
			$sum += floatval( $table['size_mb'] );
		}

		return round( $sum, 2 );
	}

	/**
	 * Get a list of inplugins active on the site.
	 *
	 * @return array
	 */
	public function get_inactive_plugins() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		if ( ! function_exists( 'get_plugins' ) ) {
			return array();
		}

		$plugins        = get_plugins();
		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$network_activated_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
			$active_plugins            = array_merge( $active_plugins, $network_activated_plugins );
		}

		$plugins_data = array();

		foreach ( $plugins as $plugin => $data ) {
			if ( in_array( $plugin, $active_plugins, true ) ) {
				continue;
			}
			$plugins_data[] = $this->format_plugin_data( $plugin, $data );
		}

		return $plugins_data;
	}

	/**
	 * Format plugin data, including data on updates, into a standard format.
	 *
	 * @param string $plugin Plugin directory/file.
	 * @param array $data Plugin data from WP.
	 *
	 * @return array Formatted data.
	 * @since 3.6.0
	 */
	protected function format_plugin_data( $plugin, $data ) {
		require_once ABSPATH . 'wp-admin/includes/update.php';

		if ( ! function_exists( 'get_plugin_updates' ) ) {
			return array();
		}

		// Use WP API to lookup latest updates for plugins. Automator_Helper injects updates for premium plugins.
		if ( empty( $this->available_updates ) ) {
			$this->available_updates = get_plugin_updates();
		}

		$version_latest = $data['Version'];

		// Find latest version.
		if ( isset( $this->available_updates[ $plugin ]->update->new_version ) ) {
			$version_latest = $this->available_updates[ $plugin ]->update->new_version;
		}

		return array(
			'plugin'            => $plugin,
			'name'              => $data['Name'],
			'version'           => $data['Version'],
			'version_latest'    => $version_latest,
			'url'               => $data['PluginURI'],
			'author_name'       => $data['AuthorName'],
			'author_url'        => esc_url_raw( $data['AuthorURI'] ),
			'network_activated' => $data['Network'],
		);
	}

	/**
	 * Get a list of Dropins and MU plugins.
	 *
	 * @return array
	 * @since 3.6.0
	 */
	public function get_dropins_mu_plugins() {
		$dropins = get_dropins();
		$plugins = array(
			'dropins'    => array(),
			'mu_plugins' => array(),
		);
		foreach ( $dropins as $key => $dropin ) {
			$plugins['dropins'][] = array(
				'plugin' => $key,
				'name'   => $dropin['Name'],
			);
		}

		$mu_plugins = get_mu_plugins();
		foreach ( $mu_plugins as $plugin => $mu_plugin ) {
			$plugins['mu_plugins'][] = array(
				'plugin'      => $plugin,
				'name'        => $mu_plugin['Name'],
				'version'     => $mu_plugin['Version'],
				'url'         => $mu_plugin['PluginURI'],
				'author_name' => $mu_plugin['AuthorName'],
				'author_url'  => esc_url_raw( $mu_plugin['AuthorURI'] ),
			);
		}

		return $plugins;
	}

	/**
	 * Get info on the current active theme, info on parent theme (if presnet)
	 * and a list of template overrides.
	 *
	 * @return array
	 */
	public function get_theme_info() {
		$active_theme = wp_get_theme();

		// Get parent theme info if this theme is a child theme, otherwise
		// pass empty info in the response.
		if ( is_child_theme() ) {
			$parent_theme      = wp_get_theme( $active_theme->template );
			$parent_theme_info = array(
				'parent_name'       => $parent_theme->name,
				'parent_version'    => $parent_theme->version,
				'parent_author_url' => $parent_theme->{'Author URI'},
			);
		} else {
			$parent_theme_info = array(
				'parent_name'       => '',
				'parent_version'    => '',
				'parent_author_url' => '',
			);
		}

		$active_theme_info = array(
			'name'           => $active_theme->name,
			'version'        => $active_theme->version,
			'author_url'     => esc_url_raw( $active_theme->{'Author URI'} ),
			'is_child_theme' => is_child_theme(),
		);

		return array_merge( $active_theme_info, $parent_theme_info );
	}

	/**
	 * @param $plugins
	 */
	public function output_plugins_info( $plugins ) {
		foreach ( $plugins as $plugin ) {
			if ( ! empty( $plugin['name'] ) ) {
				// Link the plugin name to the plugin url if available.
				$plugin_name = esc_html( $plugin['name'] );
				if ( ! empty( $plugin['url'] ) ) {
					$plugin_name = '<a href="' . esc_url( $plugin['url'] ) . '" aria-label="' . esc_attr__( 'Visit plugin homepage', 'uncanny-automator' ) . '" target="_blank">' . $plugin_name . '</a>';
				}

				$version_string = $plugin['version'];
				$network_string = '';
				?>
				<tr>
					<td><?php echo wp_kses_post( $plugin_name ); ?></td>
					<td class="help">&nbsp;</td>
					<td>
						<?php
						/* translators: %s: plugin author */
						printf( esc_html__( 'by %s', 'uncanny-automator' ), esc_html( $plugin['author_name'] ) );
						echo ' &ndash; ' . esc_html( $version_string ) . $network_string; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</td>
				</tr>
				<?php
			}
		}
	}

	/**
	 * @return array
	 */
	public function get_automator_stats() {

		if (
			// Disable in Status > Tools page.
			'uncanny-automator-admin-tools' === filter_input( INPUT_GET, 'page' )
			&& 'tools' === filter_input( INPUT_GET, 'tab' ) ) {
			return;
		}

		if ( ! class_exists( '\Uncanny_Automator\Admin_Menu' ) ) {
			include_once UA_ABSPATH . 'src/core/admin/class-admin-menu.php';
		}
		$dashboard = Admin_Menu::get_dashboard_details();
		$credits   = 0;
		if ( $dashboard->has_site_connected ) {
			$credits = $dashboard->miscellaneous->free_credits;
		} else {
			$credits = esc_html__( 'Not connected', 'uncanny-automator' );
		}
		if ( $dashboard->is_pro ) {
			$credits = esc_html__( 'Unlimited', 'uncanny-automator' );
		}

		return array(
			'total_recipes'        => Automator()->get->total_recipes(),
			'live_recipes'         => Automator()->get->total_recipes( 'publish' ),
			'completed_recipes'    => Automator()->get->completed_recipes_count(),
			'completed_last_week'  => Automator()->get->completed_runs( WEEK_IN_SECONDS ),
			'recipe_logs_count'    => Automator()->get->recipe_log_count(),
			'not_completed_status' => Automator()->get->recipe_log_count( false ),
			'credits_left'         => $credits,
			'credit_recipes'       => Automator()->get->recipes_using_credits( true ),
		);
	}
}
