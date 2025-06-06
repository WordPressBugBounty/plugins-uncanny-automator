<?php

namespace Uncanny_Automator;

/**
 * Class Recipe_Post_Functions
 *
 * @package Uncanny_Automator
 */
class Recipe_Post_Utilities {

	/**
	 * Recipe_Post_Functions constructor.
	 */
	public function __construct() {
		// Add the custom columns to the uo-recipe.
		add_filter(
			'manage_uo-recipe_posts_columns',
			array(
				$this,
				'set_custom_columns',
			)
		);

		// Add the data to the custom columns for uo-recipe.
		add_action(
			'manage_uo-recipe_posts_custom_column',
			array(
				$this,
				'custom_column',
			),
			10,
			2
		);

		// Add admin post creation scripts.
		add_action(
			'admin_enqueue_scripts',
			array(
				$this,
				'automator_recipe_scripts',
			),
			999
		);

		// Change to before delete post.
		add_action(
			'_DEPRECATED_delete_post',
			array(
				$this,
				'delete_triggers_actions',
			),
			10,
			1
		);

		// Draft when recipe moved to trash.
		add_action(
			'wp_trash_post',
			array(
				$this,
				'draft_triggers_actions',
			),
			10,
			1
		);

		// Prepopulate recipe from a URL query (only for admins).
		if ( is_admin() ) {
			add_action(
				'wp_insert_post',
				array(
					'Uncanny_Automator\Populate_From_Query',
					'maybe_populate',
				),
				9,
				3
			);
		}

		// Change Default new recipe post from auto-draft to draft.
		add_action(
			'wp_insert_post',
			array(
				$this,
				'change_default_post_status',
			),
			10,
			3
		);

		// Add recipe and redirect to it in edit mode.
		add_filter(
			'replace_editor',
			array(
				$this,
				'redirect_to_recipe',
			),
			20,
			2
		);

		// Remove WordPress default publish box.
		add_action( 'admin_menu', array( $this, 'remove_publish_box' ) );

		// Add the recipe builder object
		add_filter(
			'automator_asset_script_localize_vars_uap-admin',
			function ( $localizable_vars ) {
				if ( ! Automator()->helpers->recipe->is_edit_page() ) {
					return $localizable_vars;
				}

				// @todo: It should be something like: UncannyAutomator.features.recipeBuilder
				$localizable_vars['UncannyAutomator'] = $this->assets_get_automator_main_object();

				return $localizable_vars;
			}
		);

		add_filter(
			'automator_asset_script_dependencies_uap-admin',
			function ( $dependencies ) {
				if ( ! Automator()->helpers->recipe->is_edit_page() ) {
					return $dependencies;
				}

				$dependencies[] = 'jquery';
				$dependencies[] = 'uap-codemirror';
				$dependencies[] = 'uap-codemirror-autorefresh';
				$dependencies[] = 'uap-codemirror-no-newlines';
				$dependencies[] = 'uap-codemirror-searchcursor';
				$dependencies[] = 'uap-codemirror-search';
				$dependencies[] = 'uap-codemirror-placeholder';
				$dependencies[] = 'uap-codemirror-mode-xml';
				$dependencies[] = 'uap-codemirror-mode-css';
				$dependencies[] = 'uap-codemirror-mode-javascript';
				$dependencies[] = 'uap-codemirror-mode-htmlmixed';
				$dependencies[] = 'uap-tinymce-plugin-fullpage';

				return $dependencies;
			}
		);
	}

	/**
	 * @param $value
	 * @param $post
	 *
	 * @return mixed
	 */
	public function redirect_to_recipe( $value, $post ) {

		global $current_screen;

		if ( $current_screen && 'add' === $current_screen->action && 'uo-recipe' === $current_screen->post_type ) {
			wp_safe_redirect( admin_url( 'post.php?post=' . $post->ID . '&action=edit' ) );
			die();
		}

		return $value;
	}

	/**
	 * Remove the WP standard Post publish metabox
	 */
	public function remove_publish_box() {
		remove_meta_box( 'submitdiv', 'uo-recipe', 'side' );
	}

	/**
	 * @param $post_ID
	 * @param $post
	 * @param $update
	 */
	public function change_default_post_status( $post_ID, $post, $update ) {

		if ( 'uo-recipe' !== (string) $post->post_type ) {
			return;
		}
		if ( 'auto-draft' !== (string) $post->post_status ) {
			return;
		}

		// Update post
		$args = array(
			'ID'          => $post_ID,
			'post_status' => 'draft',
			'post_title'  => '',
		);

		// Update the post into the database
		wp_update_post( $args );

		// Save automator version for future use in case
		// something has to be changed for older recipes
		update_post_meta( $post_ID, 'uap_recipe_version', Utilities::automator_get_version() );
		update_post_meta( $post_ID, 'recipe_completions_allowed', '-1' );
		update_post_meta( $post_ID, 'recipe_max_completions_allowed', '-1' );
	}

	/**
	 * Enqueue scripts only on custom post type edit pages
	 *
	 * @param $hook
	 */
	public function automator_recipe_scripts( $hook ) {
		// Add scripts ONLY to recipe custom post type
		if ( 'post-new.php' !== $hook && 'post.php' !== $hook ) {
			return;
		}
		if ( 'uo-recipe' !== (string) get_post_type() ) {
			return;
		}

		// Add TinyMCE plugins
		$this->assets_vendor_tinymce_plugins();

		// Add CodeMirror
		$this->assets_vendor_codemirror();

		// Add TinyMCE
		$this->assets_vendor_tinymce();

		// Remove conflictive assets
		// These shouldn't load in the recipe builder
		self::dequeue_conflictive_assets();
	}

	/**
	 * Enqueue additional TinyMCE plugins for WordPress' editor
	 */
	private function assets_vendor_tinymce_plugins() {
		Utilities::enqueue_legacy_vendor_asset(
			'uap-tinymce-plugin-fullpage',
			'tinymce/plugins/fullpage/plugin.min.js',
			array(
				'wp-tinymce',
			)
		);
	}

	/**
	 * Enqueue CodeMirror assets
	 */
	private function assets_vendor_codemirror() {
		// Base handle for the codemirror assets
		$handle = 'uap-codemirror';

		Utilities::enqueue_legacy_vendor_asset(
			$handle,
			'codemirror/css/codemirror.min.css'
		);

		Utilities::enqueue_legacy_vendor_asset(
			$handle,
			'codemirror/js/codemirror.min.js'
		);

		Utilities::enqueue_legacy_vendor_asset(
			$handle . '-autorefresh',
			'codemirror/js/autorefresh.js',
			array( 'jquery', $handle )
		);

		Utilities::enqueue_legacy_vendor_asset(
			$handle . '-no-newlines',
			'codemirror/js/no-newlines.js',
			array( 'jquery', $handle )
		);

		Utilities::enqueue_legacy_vendor_asset(
			$handle . '-placeholder',
			'codemirror/js/placeholder.js',
			array( 'jquery', $handle )
		);

		Utilities::enqueue_legacy_vendor_asset(
			$handle . '-searchcursor',
			'codemirror/js/searchcursor.js',
			array( 'jquery', $handle )
		);

		Utilities::enqueue_legacy_vendor_asset(
			$handle . '-search',
			'codemirror/js/search.js',
			array( 'jquery', $handle, $handle . '-searchcursor' )
		);

		Utilities::enqueue_legacy_vendor_asset(
			$handle . '-mode-xml',
			'codemirror/js/modes/xml/xml.min.js',
			array( 'jquery', $handle )
		);

		Utilities::enqueue_legacy_vendor_asset(
			$handle . '-mode-css',
			'codemirror/js/modes/css/css.min.js',
			array( 'jquery', $handle )
		);

		Utilities::enqueue_legacy_vendor_asset(
			$handle . '-mode-javascript',
			'codemirror/js/modes/javascript/javascript.min.js',
			array( 'jquery', $handle )
		);

		Utilities::enqueue_legacy_vendor_asset(
			$handle . '-mode-htmlmixed',
			'codemirror/js/modes/htmlmixed/htmlmixed.min.js',
			array( 'jquery', $handle )
		);
	}

	/**
	 * Dequeues conflictive assets that shouldn't be loading in the recipe builder
	 */
	public static function dequeue_conflictive_assets() {

		// Set conflictive scripts
		$conflictive_scripts = array(
			// General
			'select2',

			// WooCommerce
			'selectWoo',
			'wc-enhanced-select',

			// LearnDash
			'learndash-select2-jquery-script',

			// The Events Calendar
			'tribe-select2',

			// Studiocart
			'sc-select2_js',

			// JW Player 6 for WordPress
			'jquerySelect2',
			'jwp6media',

			// YouTube Embed Plus
			'__ytprefs_admin__',

			/**
			 * Groundhogg
			 * Creates a conflict with select2 dropdowns
			 */
			'groundhogg-select2',
		);

		$conflictive_styles = array(
			// General
			'select2',

			// LearnDash
			'learndash-select2-jquery-style',

			// Studiocart
			'sc-select2_css',

			// JW Player 6 for WordPress
			'jquerySelect2Style',

			// Advanced Custom Fields Pro
			'acf-input',

			/**
			 * Groundhogg
			 * Creates a conflict with select2 dropdowns
			 */
			'groundhogg-select2',

			/**
			 * MemberPress for LearnDash
			 * Creates a conflict with select2 dropdowns
			 */
			'learndash-memberpress-edit-membership',
		);

		$conflictive_assets = array(
			'scripts' => $conflictive_scripts,
			'styles'  => $conflictive_styles,
		);

		$conflictive_assets = apply_filters( 'automator_conflictive_assets', $conflictive_assets );

		// Check if the array is valid
		if ( empty( $conflictive_assets ) || ! isset( $conflictive_assets['scripts'] ) || ! isset( $conflictive_assets['styles'] ) ) {
			// Someone made a mess and this is empty now. Bail
			return;
		}
		foreach ( $conflictive_assets['scripts'] as $conflictive_script ) {
			wp_deregister_script( $conflictive_script );
		}

		foreach ( $conflictive_assets['styles'] as $conflictive_style ) {
			wp_deregister_style( $conflictive_style );
		}
	}

	/**
	 *
	 */
	private function assets_vendor_tinymce() {
		wp_enqueue_editor();
		wp_enqueue_media();
	}

	/**
	 * @return mixed|void
	 */
	private function assets_get_automator_main_object() {
		global $post;

		// $post return $post->ID as a string, Our JS expects an int... change it
		$post_id = (int) $post->ID;

		// Get source
		$source = get_post_meta( $post_id, 'source', true );
		// Create fields array
		$fields = array(
			'existingUser' => array(),
			'newUser'      => array(),
		);
		// Check if the user defined a valid source
		if ( in_array( $source, array( 'existingUser', 'newUser' ), true ) ) {
			// If the user did it, then add the fields
			$fields[ $source ] = get_post_meta( $post_id, 'fields', true );
		}

		$editable_roles = get_editable_roles();
		$roles          = array();
		foreach ( $editable_roles as $role_key => $role_data ) {
			$roles[ $role_key ] = $role_data['name'];
		}

		// Remove any cached extra options
		delete_post_meta( $post_id, 'extra_options' );

		Automator()->automator_load_textdomain();

		// Integrations object (new).
		try {
			$core_integrations = new Services\Integrations\Structure( $post_id );
			$integrations      = '';
			if ( ! is_null( $core_integrations ) ) {
				$integrations = json_decode( $core_integrations->toJSON(), true );
			}
		} catch ( \Error $e ) {
			automator_log( $e->getMessage(), $post_id, AUTOMATOR_DEBUG_MODE, '$core_integrations' );
		} catch ( \Exception $e ) {
			automator_log( $e->getMessage(), $post_id, AUTOMATOR_DEBUG_MODE, '$core_integrations' );
		}

		// Add the Create first recipe walkthrough.
		add_filter( 'automator_get_user_walkthroughs', array( $this, 'should_show_create_recipe_walkthrough' ), 10, 3 );
		$_integrations   = Automator()->get_integrations();
		$_pro_only_items = Utilities::get_pro_only_items();

		$api_setup = array(
			// UncannyAutomator._recipe
			'_recipe'        => Automator()->get_recipe_object( $post_id, ARRAY_A ),

			// UncannyAutomator._site
			'_site'          => array(
				// UncannyAutomator._site.rest
				'rest'                => array(
					// UncannyAutomator._site.rest.url
					'url'   => esc_url_raw( rest_url() . AUTOMATOR_REST_API_END_POINT ),
					// UncannyAutomator._site.rest.nonce
					'nonce' => \wp_create_nonce( 'wp_rest' ),
				),

				// UncannyAutomator._site.in_walkthrough_mode
				// 'in_walkthrough_mode' => $this->should_show_walkthrough(),

				// UncannyAutomator._site.walkthroughs
				'walkthroughs'        => Automator()->utilities->get_user_walkthroughs( get_current_user_id() ),

				// UncannyAutomator._site.has_debug_enabled
				'has_debug_enabled'   => (bool) AUTOMATOR_DEBUG_MODE,

				// UncannyAutomator._site.is_multisite
				'is_multisite'        => is_multisite(),

				// UncannyAutomator._site.is_rtl
				'is_rtl'              => is_rtl(),

				// UncannyAutomator._site.date_format
				'date_format'         => get_option( 'date_format' ),

				// UncannyAutomator._site.time_format
				'time_format'         => get_option( 'time_format' ),

				// UncannyAutomator._site.timezone
				'timezone'            => Automator()->get_timezone_string(),

				// UncannyAutomator._site.automator
				'automator'           => array(
					// UncannyAutomator._site.automator.version
					'version'               => AUTOMATOR_PLUGIN_VERSION,

					//UncannyAutomator._site.automator.has_pro
					'has_pro'               => defined( 'AUTOMATOR_PRO_PLUGIN_VERSION' ),

					// UncannyAutomator._site.automator.version_pro
					'version_pro'           => defined( 'AUTOMATOR_PRO_PLUGIN_VERSION' ) ? AUTOMATOR_PRO_PLUGIN_VERSION : '',

					// UncannyAutomator._site.automator.has_account_connected
					'has_account_connected' => ( ! Api_Server::is_automator_connected( automator_filter_has_var( 'ua_connecting_integration' ) ) ? false : true ),

					// UncannyAutomator._site.automator.has_valid_pro_license
					'has_valid_pro_license' => ( defined( 'AUTOMATOR_PRO_FILE' ) && 'valid' === automator_get_option( 'uap_automator_pro_license_status' ) ),

					// UncannyAutomator._site.automator.links.marketing_referer
					'marketing_referer'     => automator_get_option( 'uncannyautomator_source', '' ),

					// UncannyAutomator._site.automator.links
					'links'                 => array(
						// UncannyAutomator._site.automator.links.debugging_guide
						'debugging_guide'         => 'https://automatorplugin.com/knowledge-base/troubleshooting-plugin-errors/?utm_source=uncanny_automator&utm_medium=recipe-wizard-error-modal&utm_content=learn-more-debugging',

						// UncannyAutomator._site.automator.links.contact_support
						'contact_support'         => add_query_arg(
							array(
								'utm_source'  => defined( 'AUTOMATOR_PRO_PLUGIN_VERSION' ) ? 'uncanny_automator_pro' : 'uncanny_automator',
								'utm_medium'  => 'error_handler',
								'utm_content' => 'get_support_link',
								'subject'     => 'technical-support',
								'version'     => AUTOMATOR_PLUGIN_VERSION,
								'site_url'    => get_site_url(),
							),
							'https://automatorplugin.com/automator-support/'
						),

						// UncannyAutomator._site.automator.links.loops_guide
						'loops_guide'             => array(
							// UncannyAutomator._site.automator.links.loops_guide.users_loops
							'users_loops' => 'https://automatorplugin.com/knowledge-base/user-loops/',

							// UncannyAutomator._site.automator.links.loops_guide.posts_loops
							'posts_loops' => 'https://automatorplugin.com/knowledge-base/post-loops/',

							// UncannyAutomator._site.automator.links.loops_guide.token_loops
							'token_loops' => 'https://automatorplugin.com/knowledge-base/token-loops/',
						),

						// UncannyAutomator._site.automator.links.plugin_required_missing
						'plugin_required_missing' => 'https://automatorplugin.com/knowledge-base/the-action-trigger-requires-a-plugin-that-is-not-installed-or-active-message/',

						// UncannyAutomator._site.automator.links.all_recipes
						'all_recipes'             => admin_url( 'edit.php?post_type=uo-recipe' ),

						// UncannyAutomator._site.automator.links.tools
						'tools'                   => admin_url( 'edit.php?post_type=uo-recipe&page=uncanny-automator-tools' ),

						// UncannyAutomator._site.automator.links.manage_license
						'manage_license'          => admin_url( 'edit.php?post_type=uo-recipe&page=uncanny-automator-config&tab=general&general=license' ),

						// UncannyAutomator._site.automator.links.styles_for_tinymce
						// TinyMCE needs the Automator styles to be loaded again inside the iframe
						// of the "Visual" tab. For that, we need to define an array with the URLs
						// of both Automator stylesheets
						'styles_for_tinymce'      => array(
							add_query_arg( array( 'ver' => AUTOMATOR_PLUGIN_VERSION ), Utilities::automator_get_asset( 'build/main.css' ) ),
						),
					),
				),

				// UncannyAutomator._site.links
				'links'               => array(
					// UncannyAutomator._site.links.wp_admin
					'wp_admin'      => admin_url( 'admin.php' ),

					// UncannyAutomator._site.links.wp_permalinks
					'wp_permalinks' => esc_url( admin_url( 'options-permalink.php' ) ),

					// UncannyAutomator._site.links.wp_timezone
					'wp_timezone'   => admin_url( 'options-general.php#timezone_string' ),
				),

				// UncannyAutomator._site.permalink_structure
				'permalink_structure' => get_option( 'permalink_structure' ),
			),

			// UncannyAutomator._integrations
			'_integrations'  => $integrations,

			// UncannyAutomator.triggers
			'triggers'       => array_values( Automator()->get_triggers() ),
			// UncannyAutomator.actions
			'actions'        => array_values( Automator()->get_actions() ),
			// UncannyAutomator.closures
			'closures'       => array_values( Automator()->get_closures() ),
			// UncannyAutomator.pro_items
			'pro_items'      => $this->get_pro_items(),

			// UncannyAutomator.integrations
			'integrations'   => automator_array_merge( $_integrations, $_pro_only_items ),

			// TODO Remove once the JS stops using both `recipes_object` and `recipe` objects
			'recipes_object' => Automator()->get_recipes_data( true, $post_id ),
			'recipe'         => array(
				// UncannyAutomator.recipe.requiresUserData
				'requiresUserData' => Automator()->get->get_recipe_requires_user( $post_id ),
				// UncannyAutomator.recipe.errorMode
				'errorMode'        => false,
				// UncannyAutomator.recipe.isValid
				'isValid'          => false,
				// UncannyAutomator.recipe.userSelector
				'userSelector'     => array(
					'source'    => $source,
					'data'      => $fields,
					'isValid'   => false,
					'resources' => array(
						'roles' => $roles,
					),
				),
				// UncannyAutomator.recipe.hasLive
				'hasLive'          => array(
					// UncannyAutomator.recipe.hasLive.trigger
					'trigger' => false,
					// UncannyAutomator.recipe.hasLive.action
					'action'  => false,
					// UncannyAutomator.recipe.hasLive.closure
					'closure' => false,
				),
				// UncannyAutomator.recipe.message
				'message'          => array(
					// UncannyAutomator.recipe.message.error
					'error'   => '',
					// UncannyAutomator.recipe.message.warning
					'warning' => '',
				),
				// UncannyAutomator.recipe.items
				'items'            => array(),
				// UncannyAutomator.recipe.publish
				'publish'          => array(),
			),
		);

		$api_setup = apply_filters_deprecated( 'uap_api_setup', array( $api_setup ), '3.0', 'automator_api_setup' ); // deprecate

		return apply_filters( 'automator_api_setup', $api_setup );
	}

	/**
	 * List of Pro features to upsell Automator Pro
	 *
	 * @return array
	 */
	private function get_pro_items() {

		return Utilities::get_pro_items_list();
	}

	/**
	 * Check user progress for Create first recipe walkthrough.
	 *
	 * @param array $walkthroughs
	 * @param int   $user_id
	 * @param Automator_User_Walkthroughs $walkthroughs_object
	 *
	 * @return array
	 */
	public function should_show_create_recipe_walkthrough( $walkthroughs, $user_id, $walkthroughs_object ) {

		$id = 'create-recipe';

		// Check if progress has been set.
		$create_progress = $walkthroughs_object->get_progress_by_id( $id, false );
		if ( ! empty( $create_progress ) ) {
			// If progress has already been set, return it with defaults parsed.
			$walkthroughs[ $id ] = $walkthroughs_object->get_progress_by_id( $id );
			return $walkthroughs;
		}

		// Check if current user has any published recipes.
		$user_recipes = get_posts(
			array(
				'post_type' => 'uo-recipe',
				'author'    => $user_id,
				'status'    => 'publish',
				'fields'    => 'ids',
			)
		);

		// Set our progress to enabled.
		$progress = array(
			'show' => count( $user_recipes ) > 0 ? 0 : 1,
			'step' => '',
		);

		// Save the progress so we don't run the checks every time.
		$walkthroughs_object->set_progress_by_id( $id, $progress );

		// Return walkthrough with enabled status.
		$walkthroughs[ $id ] = $walkthroughs_object->get_progress_by_id( $id );

		return $walkthroughs;
	}

	/**
	 * Delete all children triggers and actions of recipe
	 *
	 * @param $post_ID
	 *
	 * @deprecated 4.15.2
	 */
	public function delete_triggers_actions( $post_ID ) {

		$post = get_post( $post_ID );
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		if ( 'uo-recipe' === $post->post_type ) {

			// delete recipe logs
			self::delete_recipe_logs( $post_ID );
		}

		if ( 'uo-action' === (string) $post->post_type ) {
			Automator()->db->action->delete( $post_ID );
		}

		if ( 'uo-trigger' === (string) $post->post_type ) {
			Automator()->db->trigger->delete( $post_ID );
		}

		if ( 'uo-closure' === (string) $post->post_type ) {
			Automator()->db->closure->delete( $post_ID );
		}
	}

	/**
	 * Delete all logs and meta for triggers
	 *
	 * @param $post_ID
	 */
	public static function delete_recipe_logs( $post_ID ) {
		Automator()->db->recipe->delete( $post_ID );

		$args = array(
			'post_parent'    => $post_ID,
			'post_status'    => 'any',
			'post_type'      => 'uo-trigger',
			'posts_per_page' => 999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
		);

		$children = get_children( $args );

		if ( is_array( $children ) && count( $children ) > 0 ) {

			// Delete all the Children of the Parent Page
			foreach ( $children as $child ) {

				wp_delete_post( $child->ID, true );

				Automator()->db->trigger->delete( $post_ID );
			}
		}

		$args = array(
			'post_parent'    => $post_ID,
			'post_status'    => 'any',
			'post_type'      => 'uo-action',
			'posts_per_page' => 999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
		);

		$children = get_children( $args );

		if ( is_array( $children ) && count( $children ) > 0 ) {

			// Delete all the Children of the Parent Page
			foreach ( $children as $child ) {

				wp_delete_post( $child->ID, true );

				Automator()->db->action->delete( $post_ID );
			}
		}

		$args = array(
			'post_parent'    => $post_ID,
			'post_status'    => 'any',
			'post_type'      => 'uo-closure',
			'posts_per_page' => 999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
		);

		$children = get_children( $args );

		if ( is_array( $children ) && count( $children ) > 0 ) {

			// Delete all the Children of the Parent Page
			foreach ( $children as $child ) {

				wp_delete_post( $child->ID, true );

				Automator()->db->closure->delete( $post_ID );
			}
		}
	}

	/**
	 * Draft all children triggers and actions of recipe
	 *
	 * @param $post_ID
	 */
	public function draft_triggers_actions( $post_ID ) {

		$post = get_post( $post_ID );

		if ( $post && 'uo-recipe' === $post->post_type ) {

			$args = array(
				'post_parent'    => $post->ID,
				'post_status'    => 'any',
				'post_type'      => 'uo-trigger',
				'posts_per_page' => 999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
			);

			$children = get_children( $args );

			if ( is_array( $children ) && count( $children ) > 0 ) {

				// Delete all the Children of the Parent Page
				foreach ( $children as $child ) {

					$child_update = array(
						'ID'          => $child->ID,
						'post_status' => 'draft',
					);

					wp_update_post( $child_update );
				}
			}

			$args = array(
				'post_parent'    => $post->ID,
				'post_status'    => 'any',
				'post_type'      => 'uo-action',
				'posts_per_page' => 999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
			);

			$children = get_children( $args );

			if ( is_array( $children ) && count( $children ) > 0 ) {

				// Delete all the Children of the Parent Page
				foreach ( $children as $child ) {

					$child_update = array(
						'ID'          => $child->ID,
						'post_status' => 'draft',
					);

					wp_update_post( $child_update );
				}
			}

			$args = array(
				'post_parent'    => $post->ID,
				'post_status'    => 'any',
				'post_type'      => 'uo-closure',
				'posts_per_page' => 999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
			);

			$children = get_children( $args );

			if ( is_array( $children ) && count( $children ) > 0 ) {

				// Delete all the Children of the Parent Page
				foreach ( $children as $child ) {

					$child_update = array(
						'ID'          => $child->ID,
						'post_status' => 'draft',
					);

					wp_update_post( $child_update );
				}
			}
		}
	}

	/**
	 * Add data to custom columns in the recipe list
	 *
	 * @param $column
	 * @param $post_id
	 */
	public function custom_column( $column, $post_id ) {

		global $wpdb;

		switch ( $column ) {
			case 'triggers':
				$trigger_titles = $wpdb->get_results( $wpdb->prepare( "SELECT post_status, post_title FROM {$wpdb->posts} WHERE post_parent = %d AND post_type = %s", $post_id, 'uo-trigger' ) );
				?>
				<div class="uap">
					<div class="uo-post-column__list">
						<?php
						foreach ( $trigger_titles as $title ) {
							?>
							<div class="uo-post-column__item">
								<?php echo 'publish' === $title->post_status ? '<span class="dashicons dashicons-yes-alt recipe-ui-dash" title="Live"></span>' : '<span class="dashicons dashicons-warning recipe-ui-dash" title="Draft"></span>'; ?>
								<?php echo esc_html( $title->post_title ); ?>
							</div>
						<?php } ?>
					</div>
				</div>
				<?php

				break;
			case 'actions':
				$action_titles = $wpdb->get_results( $wpdb->prepare( "SELECT post_status, post_title FROM {$wpdb->posts} WHERE post_parent=%d AND post_type=%s ORDER BY `menu_order` ASC", $post_id, 'uo-action' ) );
				?>
				<div class="uap">
					<div class="uo-post-column__list">
						<?php foreach ( $action_titles as $title ) { ?>
							<div class="uo-post-column__item">
								<?php echo 'publish' === $title->post_status ? '<span class="dashicons dashicons-yes-alt recipe-ui-dash" title="Live"></span>' : '<span class="dashicons dashicons-warning recipe-ui-dash" title="Draft"></span>'; ?>
								<?php echo esc_html( $title->post_title ); ?>
							</div>
						<?php } ?>
					</div>
				</div>
				<?php
				break;
			case 'runs':
				//$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(run_number) FROM {$wpdb->prefix}uap_recipe_log WHERE automator_recipe_id=%d AND completed = %d", $post_id, 1 ) );
				$count = Automator()->utilities->get_recipe_total_runs( $post_id );

				$url = add_query_arg(
					array(
						'post_type' => 'uo-recipe',
						'page'      => 'uncanny-automator-admin-logs',
						'recipe_id' => $post_id,
					),
					admin_url( 'edit.php' )
				);
				printf( '<a href="%s">%s</a>', $url, $count ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				break;
			case 'type':
				$type = get_post_meta( $post_id, 'uap_recipe_type', true );
				echo empty( $type ) ? esc_html__( 'User', 'uncanny-automator' ) : esc_html( ucfirst( $type ) );
				break;
			case 'recipe_status':
				$post_status = get_post_status( $post_id );
				echo 'publish' === $post_status ? '<span class="dashicons dashicons-yes-alt recipe-ui-dash" title="Live"></span>' . esc_html__( 'Live', 'uncanny-automator' ) : '<span class="dashicons dashicons-warning recipe-ui-dash" title="Draft"></span>' . esc_html__( 'Draft', 'uncanny-automator' );
				break;
			case 'recipe_notes':
				$note_meta = get_post_meta( $post_id, 'uap_recipe_notes', true );
				$notes     = '';
				if ( ! empty( $note_meta ) ) {
					// Limit the text 10 words.
					$limited_text = wp_trim_words( $note_meta, 10, '...' );
					$show_full    = strlen( $note_meta ) > strlen( $limited_text );
					$limited_aria = $show_full ? 'false' : 'true';

					// Create a wrapper.
					$notes = '<div class="recipe-ui-list-notes__wrapper">';

					// Add a span with the limited text.
					$notes .= sprintf(
						'<span class="recipe-ui-list-notes__limited" aria-hidden="%s">%s</span>',
						esc_attr( $limited_aria ),
						esc_html( $limited_text )
					);

					// Add another span with the full text (hidden by default).
					if ( $show_full ) {
						$notes .= sprintf(
							// translators: %s: The full note text.
							'<span class="recipe-ui-list-notes__full" aria-hidden="false">%s</span>',
							nl2br( esc_textarea( $note_meta ) )
						);
					}

					$notes .= '</div>';
				}
				echo wp_kses(
					$notes,
					array(
						'div'  => array(
							'class' => array(),
						),
						'span' => array(
							'class'       => array(),
							'aria-hidden' => array(),
						),
						'br'   => array(), // For nl2br output
					)
				);
				break;
		}
	}

	/**
	 * Create custom columns in the recipe list
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function set_custom_columns( $columns ) {

		$new_columns = array();

		foreach ( $columns as $key => $column ) {

			if ( 'author' === $key ) {

				//$new_columns['type']     = esc_attr__( 'Recipe type', 'uncanny-automator' );
				$new_columns['triggers'] = esc_attr__( 'Triggers', 'uncanny-automator' );
				$new_columns['actions']  = esc_attr__( 'Actions', 'uncanny-automator' );
				/* translators: The number of times a recipe was completed */
				$new_columns['runs']          = esc_attr__( 'Completed runs', 'uncanny-automator' );
				$new_columns['recipe_status'] = esc_attr__( 'Recipe status', 'uncanny-automator' );
				$new_columns['recipe_notes']  = esc_attr__( 'Notes', 'uncanny-automator' );
				$new_columns[ $key ]          = $column;

			} else {
				$new_columns[ $key ] = $column;
			}
		}

		return $new_columns;
	}
}
