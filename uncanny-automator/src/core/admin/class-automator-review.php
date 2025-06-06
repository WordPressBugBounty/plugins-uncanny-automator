<?php
/**
 * @class   Admin_Review
 * @since   3.0
 * @version 4.2
 * @author  Saad S.
 * @package Uncanny_Automator
 */

namespace Uncanny_Automator;

use WP_REST_Response;
use WP_REST_Request;

/**
 * Class Automator_Review
 *
 * @package Uncanny_Automator
 */
class Automator_Review {

	/**
	 * Constant REVIEW_BANNER_TMP_NUM_DAYS
	 *
	 * @var int The number of days set to show the banner again when 'maybe later' button is clicked.
	 */
	const REVIEW_BANNER_TMP_NUM_DAYS = 10;

	/**
	 * Constant N_CREDITS_TO_SHOW
	 *
	 * @var int The number of credits usage for the banner to show up.
	 */
	const N_CREDITS_TO_SHOW = 20;

	/**
	 * Constant N_EMAILS_COUNT
	 *
	 * @var int The number of emails sent for the banner to show up.
	 */
	const N_EMAILS_COUNT = 30;

	/**
	 * Constant N_COMPLETED_RECIPE_COUNT
	 *
	 * @var int The number of completed recipe count for the banner to show up.
	 */
	const N_COMPLETED_RECIPE_COUNT = 30;

	/**
	 * Method __construct.
	 *
	 * Registers the action hooks.
	 *
	 * @return void
	 */
	public function __construct() {

		$this->register_hooks();
	}

	/**
	 * Registers required hook for banner to show up.
	 *
	 * @return bool True, always.
	 */
	protected function register_hooks() {

		add_action( 'automator_show_internal_admin_notice', array( $this, 'maybe_ask_review' ) );

		add_action( 'admin_head', array( $this, 'hide_all_admin_notices_on_automator_pages' ) );

		add_action( 'admin_init', array( $this, 'maybe_ask_tracking' ) );

		add_action( 'init', array( $this, 'save_review_settings_action' ) );

		add_action( 'rest_api_init', array( $this, 'uo_register_api_for_reviews' ) );

		add_action( 'wp_ajax_automator_handle_feedback', array( $this, 'handle_feedback' ) );

		add_action(
			'wp_ajax_automator_handle_credits_notification_feedback',
			array(
				$this,
				'handle_feedback_credits',
			)
		);

		return true;
	}

	/**
	 * @return void
	 */
	public function handle_feedback() {

		if ( ! wp_verify_nonce( automator_filter_input( 'nonce' ), 'feedback_banner' ) ) {

			wp_die( 'Unauthorized. Error invalid nonce.' );

		}

		// Dismiss the banner.
		$type = automator_filter_input( 'type' );

		automator_update_option( '_uncanny_automator_review_reminder', $type );

		automator_update_option( '_uncanny_automator_review_reminder_date', time() );

		// Track step.
		$this->track_review_step(
			automator_filter_input( 'banner' ),
			automator_filter_input( 'track' )
		);

		// Handle redirects.
		$redirect_url = automator_filter_input( 'redirect_url' );

		if ( ! empty( $redirect_url ) ) {

			wp_redirect( $redirect_url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect

			exit;

		}

		wp_redirect( wp_get_referer() ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect

		exit;
	}

	/**
	 * @return void
	 */
	public function handle_feedback_credits() {

		if ( ! wp_verify_nonce( automator_filter_input( 'nonce' ), 'automator_handle_credits_notification_feedback' ) ) {

			wp_die( 'Unauthorized. Error invalid nonce.' );

		}

		$type = absint( automator_filter_input( 'type' ) );
		$proc = automator_filter_input( 'procedure' );

		if ( 'dismiss' === $proc ) {

			$this->dismiss_credits_notification( $type );

		}

		wp_redirect( wp_get_referer() ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect

		exit;
	}

	/**
	 * Dismisses the credits notification base on the type.
	 *
	 * @param int $type The type of notification.
	 *
	 * @return bool True, always.
	 */
	public function dismiss_credits_notification( $type = null ) {

		if ( null === $type ) {
			return;
		}

		automator_update_option( '_uncanny_credits_notification_' . $type, 'hide-forever', true );

		if ( 25 === $type ) {
			// Also hide '_uncanny_credits_notification_100' notification.
			automator_update_option( '_uncanny_credits_notification_100', 'hide-forever', true );
		}

		if ( 0 === $type ) {
			// Also hide '_uncanny_credits_notification_25' and '_uncanny_credits_notification_100' notifications.
			automator_update_option( '_uncanny_credits_notification_25', 'hide-forever', true );
			automator_update_option( '_uncanny_credits_notification_100', 'hide-forever', true );
		}

		return true;
	}

	/**
	 * Register rest api calls for misc tasks.
	 *
	 * @since 2.1.0
	 */
	public function uo_register_api_for_reviews() {

		register_rest_route(
			AUTOMATOR_REST_API_END_POINT,
			'/get-credits/',
			array(
				'methods'             => 'POST, GET',
				'callback'            => array( $this, 'get_credits' ),
				'permission_callback' => array( $this, 'rest_permissions' ),
			)
		);

		register_rest_route(
			AUTOMATOR_REST_API_END_POINT,
			'/get-recipes-using-credits/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_recipes_using_credits' ),
				'permission_callback' => array( $this, 'rest_permissions' ),
			)
		);

		register_rest_route(
			AUTOMATOR_REST_API_END_POINT,
			'/allow-tracking-switch/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save_tracking_settings' ),
				'permission_callback' => array( $this, 'rest_permissions' ),
			)
		);

		register_rest_route(
			AUTOMATOR_REST_API_END_POINT,
			'/track-review/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'track_review_steps' ),
				'permission_callback' => array( $this, 'rest_permissions' ),
			)
		);
	}

	/**
	 * Valiate the rest api permissions.
	 *
	 * @return bool
	 */
	public function rest_permissions() {
		return is_user_logged_in() && current_user_can( 'manage_options' );
	}

	/**
	 * Callback for tracking review results.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function track_review_steps( $request ) {

		// Validate nonce ( in case called from external source ).
		if ( ! wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' ) ) {
			return new WP_REST_Response( array( 'success' => false ), 401 );
		}

		// Track the event.
		return $this->track_review_step(
			$request->get_param( 'banner' ),
			$request->get_param( 'event' )
		);
	}

	/**
	 * Callback for saving user selection for review by querystring.
	 *
	 * @param object $request
	 *
	 * @since 2.11
	 */
	public function save_review_settings_action() {

		$_action  = automator_filter_has_var( 'action' ) ? automator_filter_input( 'action' ) : false;
		$is_admin = function_exists( 'is_admin' ) && is_admin();

		if ( ! $_action || ! $is_admin ) {
			return;
		}

		switch ( $_action ) {
			// @TODO: Review I don't believe these are being used anywhere.
			case 'uo-maybe-later':
			case 'uo-hide-forever':
				automator_update_option( '_uncanny_automator_review_reminder', str_replace( 'uo-', '', $_action ) );
				automator_update_option( '_uncanny_automator_review_reminder_date', time() );
				wp_safe_redirect( remove_query_arg( 'action' ) );
				die;

			case 'uo-allow-tracking':
			case 'uo-hide-track':
				automator_update_option( '_uncanny_automator_tracking_reminder', 'hide-forever' );
				if ( 'uo-allow-tracking' === $_action ) {
					automator_update_option( 'automator_reporting', true );
				}

				wp_safe_redirect( remove_query_arg( 'action' ) );
				die;
		}
	}

	/**
	 * Callback for getting api credits.
	 *
	 * @param object $request
	 *
	 * @return object
	 * @since 3.1
	 */
	public function get_credits() {

		// The rest response object
		$response = (object) array();

		// Default return message
		$response->message       = esc_html__( 'Information is missing.', 'uncanny-automator' );
		$response->success       = true;
		$response->credits_left  = 0;
		$response->total_credits = 0;
		$existing_data           = Api_Server::is_automator_connected();

		if ( empty( $existing_data ) ) {
			return new \WP_REST_Response( $response, 200 );
		}

		$response->credits_left = $existing_data['usage_limit'] - $existing_data['paid_usage_count'];

		$response->total_credits = $existing_data['usage_limit'];

		return new \WP_REST_Response( $response, 200 );
	}

	/**
	 * Callback for getting recipes using api credits.
	 *
	 * @param object $request
	 *
	 * @return object
	 * @since 3.1
	 */
	public function get_recipes_using_credits() {

		$response = new \WP_REST_Response(
			array(
				'success' => true,
				'recipes' => Automator()->get->fetch_recipe_with_apps(),
			),
			200
		);

		return $response;
	}

	/**
	 * Rest API callback for saving user selection for review.
	 *
	 * @param object $request
	 *
	 * @return object
	 * @since 2.1.4
	 */
	public function save_tracking_settings( $request ) {

		// check if its a valid request.
		$data = $request->get_params();

		if ( isset( $data['action'] ) && 'tracking-settings' === $data['action'] ) {

			if ( 'true' === $data['swtich'] ) {
				automator_update_option( 'automator_reporting', true );
			} else {
				automator_delete_option( 'automator_reporting' );
			}

			if ( isset( $data['hide'] ) ) {
				automator_update_option( '_uncanny_automator_tracking_reminder', 'hide-forever' );
			}

			return new WP_REST_Response( array( 'success' => true ), 200 );
		}

		return new WP_REST_Response( array( 'success' => false ), 200 );
	}

	/**
	 * Admin notice for review this plugin.
	 *
	 * @since 2.1.4
	 */
	public function maybe_ask_tracking() {

		$_is_reminder = automator_get_option( '_uncanny_automator_tracking_reminder', '' );

		$_reminder_date = automator_get_option( '_uncanny_automator_tracking_reminder_date', time() );

		if ( ! empty( $_is_reminder ) && 'hide-forever' === $_is_reminder ) {
			return;
		}

		$automator_reporting = automator_get_option( 'automator_reporting', false );

		if ( $automator_reporting ) {
			return;
		}
		add_action(
			'admin_notices',
			function () {

				// Check only Automator related pages.
				global $typenow;

				if ( empty( $typenow ) || 'uo-recipe' !== $typenow ) {
					return;
				}

				$screen = get_current_screen();

				if ( 'post' === $screen->base ) {
					return;
				}

				// Get data about Automator's version
				$is_pro  = false;
				$version = AUTOMATOR_PLUGIN_VERSION;
				if ( defined( 'AUTOMATOR_PRO_FILE' ) || class_exists( '\Uncanny_Automator_Pro\InitializePlugin' ) ) {
					$is_pro  = true;
					$version = \Uncanny_Automator_Pro\InitializePlugin::PLUGIN_VERSION;
				}

				if ( $is_pro ) {
					return;
				}

				// Send review URL
				$url_send_review = add_query_arg( array( 'action' => 'uo-allow-tracking' ) );

				// Send feedback URL
				$url_send_feedback_version = $is_pro ? 'Uncanny%20Automator%20Pro%20' . $version : 'Uncanny%20Automator%20' . $version;
				$url_send_feedback_source  = $is_pro ? 'uncanny_automator_pro' : 'uncanny_automator';
				$url_remind_later          = add_query_arg( array( 'action' => 'uo-hide-track' ) );
				include Utilities::automator_get_view( 'tracking-banner.php' );
			}
		);
	}

	/**
	 * Callback method to `automator_show_internal_admin_notice`.
	 *
	 * Registers the admin notice depending on the condition.
	 *
	 * @return void
	 */
	public function maybe_ask_review() {
		// Credits notifications.
		add_action( 'admin_notices', array( $this, 'load_credits_notif_required_assets' ) );
		// Review banner notices.
		add_action( 'admin_notices', array( $this, 'view_review_banner' ) );
	}

	/**
	 * @return void
	 */
	public function hide_all_admin_notices_on_automator_pages() {

		$current_screen = get_current_screen();

		// Do not show if we cannot identify which screen it is.
		if ( ! $current_screen instanceof \WP_Screen ) {
			return;
		}

		// Safe check in case WP_Screen changed its structure.
		if ( ! isset( $current_screen->id ) ) {
			return;
		}

		$allowed_pages = self::get_allowed_page_for_credits_notif( true );

		// Only remove admin_notices if its on Automator pages
		if ( in_array( $current_screen->id, $allowed_pages, true ) ) {
			// Remove all admin notices
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'all_admin_notices' );
		}

		do_action( 'automator_show_internal_admin_notice' );
	}

	/**
	 * @return void
	 */
	public function load_credits_notif_required_assets() {

		if ( ! $this->should_display_credits_notif() ) {
			return;
		}

		if ( ! $this->has_credits_notification() ) {
			return;
		}

		self::load_banner_assets();
	}

	/**
	 * @return void
	 */
	public static function load_banner_assets() {

		$screen = get_current_screen();

		// Bail if there is no screen ID.
		if ( ! isset( $screen->id ) ) {
			return;
		}

		$admin_menu_instance = Admin_Menu::get_instance();

		Utilities::enqueue_asset(
			'uap-admin',
			'main',
			array(
				'localize' => array(
					'UncannyAutomatorBackend' => $admin_menu_instance->get_js_backend_inline_data( $screen->id ),
					'UncannyAutomator'        => array(),
				),
			)
		);
	}

	/**
	 * Callback method to 'admin_notices.
	 *
	 * Loads template for review banner.
	 *
	 * @return void
	 */
	public function view_review_banner() {

		// Disable both credits notification and review banner notification in the "uncanny-automator-app-integrations" page.
		if ( automator_filter_has_var( 'page' ) && 'uncanny-automator-app-integrations' === automator_filter_input( 'page' ) ) {
			return;
		}

		// Do check before rendering the credits notification.
		if ( $this->should_display_credits_notif() && $this->has_credits_notification() ) {
			return $this->display_credits_notification();
		}

		/**
		 * Proceed to review banner rendering.
		 */
		if ( ! $this->is_page_automator_related() ) {
			// Bail if not on automator related pages.
			return;
		}

		// Bail if banner was hidden permanently.
		if ( $this->is_banner_hidden_forever() ) {
			return;
		}

		// Bail if banner was hidden temporarily and banner hidden days is less than the defined num of days.
		if ( $this->is_banner_hidden_temporarily() && $this->get_banner_hidden_days() <= self::REVIEW_BANNER_TMP_NUM_DAYS ) {
			return;
		}

		// Check if we should display the banner.
		$config = $this->get_review_banner_config();
		if ( ! empty( $config ) ) {
			// Track the display of the banner.
			$this->track_review_step( $config['banner'], 'displayed' );
			// Include the love / don't love banners.
			$this->review_love_dont_love_banners( $config );
			// Include main banner.
			$this->get_template( $config['banner'], $config );
		}
	}

	/**
	 * Determines whether the current page should display the credits notification.
	 *
	 * @return bool
	 */
	public function should_display_credits_notif() {

		// Make sure to only display on admin side.
		if ( ! is_admin() ) {
			return false;
		}

		return self::can_display_credits_notif();
	}

	/**
	 * Determine whether it's automator page or not.
	 *
	 * @return bool
	 */
	public static function is_automator_page() {

		$current_screen = get_current_screen();

		$automator_pages = array(
			'edit-recipe_category',
			'edit-recipe_tag',
			'uo-recipe_page_uncanny-automator-dashboard',
			'uo-recipe_page_uncanny-automator-integrations',
			'uo-recipe_page_uncanny-automator-config',
			'uo-recipe_page_uncanny-automator-admin-tools',
			'uo-recipe_page_uo-recipe-scheduled-actions',
			'uo-recipe_page_uncanny-automator-pro-upgrade',
			'uo-recipe',
			'edit-uo-recipe',
		);

		// Do not show if we cannot identify which screen it is.
		if ( ! $current_screen instanceof \WP_Screen ) {
			return false;
		}

		// Safe check in case WP_Screen changed its structure.
		if ( ! isset( $current_screen->id ) ) {
			return false;
		}

		return in_array( $current_screen->id, $automator_pages, true );
	}

	/**
	 * Determines whether the current screen can display the credits notification or not.
	 *
	 * @return boolean
	 */
	public static function can_display_credits_notif() {

		$current_screen = get_current_screen();

		// Do not show if we cannot identify which screen it is.
		if ( ! $current_screen instanceof \WP_Screen ) {
			return false;
		}

		// Safe check in case WP_Screen changed its structure.
		if ( ! isset( $current_screen->id ) ) {
			return false;
		}

		return in_array( $current_screen->id, self::get_allowed_page_for_credits_notif(), true );
	}

	/**
	 * Get the pages that are allowed for credits notification.
	 *
	 * @return string[]
	 */
	public static function get_allowed_page_for_credits_notif( $remove_notices_action = false ) {

		$wp_pages = array(
			'dashboard',
			'plugins',
			'edit-page', // The 'edit-page' refers to wp-admin/edit.php?post-type=page not the edit screen.
			'edit-post', // The 'edit-post' refers to wp-admin/edit.php not the edit screen.
		);

		$automator_pages = array(
			'edit-recipe_category',
			'edit-recipe_tag',
			'uo-recipe_page_uncanny-automator-dashboard',
			'uo-recipe_page_uncanny-automator-integrations',
			'uo-recipe_page_uncanny-automator-template-library',
			'uo-recipe_page_uncanny-automator-addons',
			'uo-recipe_page_uncanny-automator-config',
			'uo-recipe_page_uncanny-automator-admin-logs',
			'uo-recipe_page_uncanny-automator-admin-tools',
			'uo-recipe_page_uncanny-automator-config',
			'uo-recipe_page_uncanny-automator-pro-upgrade',
			'uo-recipe',
			'edit-uo-recipe',
		);

		$allowed_pages = $automator_pages;

		if ( false === $remove_notices_action ) {
			$allowed_pages = array_merge( $allowed_pages, $wp_pages );
		}

		return $allowed_pages;
	}

	/**
	 * Determines if there is a credits notification.
	 *
	 * @return bool
	 */
	public function has_credits_notification() {

		if ( defined( 'AUTOMATOR_PRO_PLUGIN_VERSION' ) ) {
			return false;
		}

		$is_credits_less_than_100 = $this->get_credits_remaining( $this->get_connected_user() ) <= 100;

		// Return false immediately if credits is less than 100.
		if ( ! $is_credits_less_than_100 ) {
			return false;
		}

		// Otherwise, if either of the option below is not 'hidden_forever', return true.
		$has_undismissed_notification = ! $this->is_credits_notification_hidden_forever( 100 )
										|| ! $this->is_credits_notification_hidden_forever( 25 )
										|| ! $this->is_credits_notification_hidden_forever( 0 );

		if ( $has_undismissed_notification ) {
			return true;
		}

		return false;
	}

	/**
	 * @return false|int|void
	 */
	public function display_credits_notification() {

		$user_connected = $this->get_connected_user();

		if ( false === $user_connected ) {
			return false;
		}

		$credits_remaining = $this->get_credits_remaining( $user_connected );

		$credits_remaining_args = array(
			'credits_remaining' => $credits_remaining,
			'customer_name'     => $user_connected['customer_name'],
			'credits_used'      => $this->get_usage_count(),
		);

		// Bail, plenty of credits remaining.
		if ( $credits_remaining > 100 ) {
			return;
		}

		$checks = array( 0, 25, 100 );
		foreach ( $checks as $check ) {
			if ( $credits_remaining <= $check ) {
				if ( $this->is_credits_notification_hidden_forever( $check ) ) {
					continue;
				}

				$credits_remaining_args['dismiss_link'] = $this->credits_feedback_url( $check, 'dismiss' );
				$credits_remaining_args['key']          = 'credits-remaining-' . $check;
				return $this->get_template( 'credits-remaining-' . $check, $credits_remaining_args );
			}
		}
	}

	/**
	 * @param $type
	 * @param $procedure
	 *
	 * @return string
	 */
	public function credits_feedback_url( $type = 100, $procedure = 'dismiss' ) {

		$action = 'automator_handle_credits_notification_feedback';

		return add_query_arg(
			array(
				'action'    => $action,
				'procedure' => $procedure,
				'type'      => $type,
				'nonce'     => wp_create_nonce( $action ),
			),
			admin_url( 'admin-ajax.php' )
		);
	}

	/**
	 * Gets config to display review banner templates.
	 *
	 * @return mixed - array|bool - The template array if the banner should be displayed. Otherwise, false.
	 */
	public function get_review_banner_config() {

		// User spent N_CREDITS_TO_SHOW (20 @ 4.10) credits. Only shows if Automator Pro is not enabled.
		if ( $this->has_spent_credits( self::N_CREDITS_TO_SHOW ) && ! defined( 'AUTOMATOR_PRO_PLUGIN_VERSION' ) ) {
			return array(
				'credits_used' => $this->get_usage_count(),
				'banner'       => 'review-credits-used',
			);
		}

		// Sent count is greater than or equal to self::N_EMAILS_COUNT (30 @ 4.10).
		if ( $this->get_sent_emails_count() >= self::N_EMAILS_COUNT ) {
			return array(
				'emails_sent' => $this->get_sent_emails_count(),
				'banner'      => 'review-emails-sent',
			);
		}

		// Completed recipes count is greater or equals to N_COMPLETED_RECIPE_COUNT (30 @ 4.10).
		if ( $this->get_completed_recipes_count() >= self::N_COMPLETED_RECIPE_COUNT ) {
			return array(
				'total_recipe_completion_count' => $this->get_completed_recipes_count(),
				'banner'                        => 'review-recipes-count',
			);
		}

		return false;
	}

	/**
	 * Include the love and dont love banners.
	 *
	 * @param array $config - The current banner config array.
	 *
	 * @return void
	 */
	public function review_love_dont_love_banners( $config ) {
		/**
		 * Always load the following templates.
		 *
		 * Up to JS to show it conditionally base on clicked button renderend on the template above.
		 **/
		$this->get_template( 'review-user-love-automator', $config );

		$this->get_template( 'review-user-dont-love-automator', $config );
	}

	/**
	 * Retrieves the template.
	 *
	 * @param string $template The name of the template.
	 * @param array $args The arguments you want to pass to the template.
	 *
	 * @return int 1 if the view was successfully included. Otherwise, throws E_WARNING.
	 */
	public function get_template( $template = '', $args = array() ) {
		$banner = isset( $args['banner'] ) && ! empty( $args['banner'] ) ? $args['banner'] : '';
		$vars   = array_merge( $this->get_common_vars( $banner ), $args );

		return include_once Utilities::automator_get_view( sanitize_file_name( $template . '.php' ) );
	}

	/**
	 * Retrieves the common variables used in the template.
	 *
	 * @param string $banner - The banner key.
	 *
	 * @return array
	 */
	public function get_common_vars( $banner = '' ) {

		$args = array();

		if ( ! empty( $banner ) ) {
			$args['banner'] = $banner;
		}

		$wp_args       = array_merge( $args, array( 'redirect_url' => 'https://wordpress.org/support/plugin/uncanny-automator/reviews/?filter=5#new-post' ) );
		$feedback_args = array_merge( $args, array( 'redirect_url' => $this->get_feedback_url() ) );
		return array(
			'url_wordpress'    => $this->get_banner_url( 'hide-forever', $wp_args ),
			'url_feedback'     => $this->get_banner_url( 'hide-forever', $feedback_args ),
			'url_maybe_later'  => $this->get_banner_url( 'maybe-later', $args ),
			'url_already_did'  => $this->get_banner_url( 'hide-forever', $args ),
			'url_close_button' => $this->get_banner_url( 'hide-forever', $args ),
		);
	}

	/**
	 * Retrieves the banner URL.
	 *
	 * @param $type
	 * @param $args
	 *
	 * @return string
	 */
	public function get_banner_url( $type, $args = array() ) {

		$defaults = array(
			'type'   => $type,
			'nonce'  => wp_create_nonce( 'feedback_banner' ),
			'action' => 'automator_handle_feedback',
		);

		$args = array_merge( $defaults, $args );
		if ( isset( $args['redirect_url'] ) ) {
			$args['redirect_url'] = rawurlencode( esc_url( $args['redirect_url'] ) );
		}

		return add_query_arg( $args, admin_url( 'admin-ajax.php' ) );
	}

	/**
	 * Retrieves the feedback URL.
	 *
	 * @return string
	 */
	public function get_feedback_url() {

		$is_pro = false;

		$version = AUTOMATOR_PLUGIN_VERSION;

		if ( defined( 'AUTOMATOR_PRO_PLUGIN_VERSION' ) ) {

			$is_pro = true;

			$version = AUTOMATOR_PRO_PLUGIN_VERSION;

		}

		// Send feedback URL
		$url_send_feedback_version = $is_pro ? 'Uncanny%20Automator%20Pro%20' . $version : 'Uncanny%20Automator%20' . $version;

		$url_send_feedback_source = $is_pro ? 'uncanny_automator_pro' : 'uncanny_automator';

		return esc_url( 'https://automatorplugin.com/feedback/?version=' . $url_send_feedback_version . '&utm_source=' . $url_send_feedback_source . '&utm_medium=review_banner' );
	}

	/**
	 * Method is_page_automator_related.
	 *
	 * Check if current loaded page is related to Automator.
	 *
	 * @return boolean True if it is. Otherwise, false.
	 */
	public function is_page_automator_related() {

		// Check only Automator related pages.
		global $typenow;

		// Get current page
		$page = automator_filter_input( 'page' );

		if ( ( 'uncanny-automator-dashboard' !== $page ) && ( empty( $typenow ) || 'uo-recipe' !== $typenow ) ) {
			return false;
		}

		$screen = get_current_screen();

		if ( 'post' === $screen->base ) {
			return false;
		}

		return true;
	}

	/**
	 * Retrieves the number of days has passed since the banner was last hidden.
	 *
	 * @return false|float
	 */
	public function get_banner_hidden_days() {

		$date_updated = automator_get_option( '_uncanny_automator_review_reminder_date', 0 );

		$current_datetime = strtotime( current_time( 'mysql' ) );

		$seconds_passed = absint( $current_datetime - $date_updated );

		return floor( $seconds_passed / ( 60 * 60 * 24 ) );
	}

	/**
	 * Determines whether the banner was hidden temporarily.
	 *
	 * @return bool
	 */
	public function is_banner_hidden_temporarily() {
		return 'maybe-later' === automator_get_option( '_uncanny_automator_review_reminder' );
	}

	/**
	 * Determines whether the banner is hidden forever.
	 *
	 * @return bool
	 */
	public function is_banner_hidden_forever() {
		return 'hide-forever' === automator_get_option( '_uncanny_automator_review_reminder' );
	}

	/**
	 * Determines whether the banner is hidden forever.
	 *
	 * @param int $notification_type The type of notification. E.g. 100, 25, 0.
	 *
	 * @return bool
	 */
	public function is_credits_notification_hidden_forever( $notification_type = 100 ) {
		return 'hide-forever' === automator_get_option( '_uncanny_credits_notification_' . $notification_type );
	}

	/**
	 * Retrieves the number of credits remaining.
	 *
	 * @return mixed|null
	 */
	public function get_credits_remaining( $user_connected ) {

		if ( false === $user_connected || empty( $user_connected['usage_limit'] ) || empty( $user_connected['paid_usage_count'] ) ) {
			// Assume unused if credits are empty.
			return apply_filters( 'automator_review_get_credits_remaining', 250, $this );
		}

		$credits_remaining = absint( intval( $user_connected['usage_limit'] ) - intval( $user_connected['paid_usage_count'] ) );

		return apply_filters( 'automator_review_get_credits_remaining', $credits_remaining, $this );
	}

	/**
	 * @return false|null
	 */
	public function get_connected_user() {

		return Api_Server::is_automator_connected();
	}

	/**
	 * Determines whether the user has spent number of credits.
	 *
	 * @param int $number_of_credits The number of credits allowed.
	 *
	 * @return bool True if the number of credits used is greater and equals to the provided number of credits.
	 */
	public function has_spent_credits( $number_of_credits = 0 ) {

		$usage_count = $this->get_usage_count();

		// Return false if 'paid_usage_count' is not set.
		if ( false === $usage_count ) {
			return false;
		}

		return $usage_count >= $number_of_credits;
	}

	/**
	 * Retrieves the usage count.
	 *
	 * @return int|bool The usage count. Returns false, if 'paid_usage_count' is not set.
	 */
	protected function get_usage_count() {

		$credits = Api_Server::is_automator_connected();

		$usage_count = isset( $credits['paid_usage_count'] ) ? absint( $credits['paid_usage_count'] ) : false;

		// Allow overide for testing purposes.
		return absint( apply_filters( 'automator_review_get_usage_count', $usage_count, $this ) );
	}

	/**
	 * Retrieves the number of emails sent.
	 *
	 * @return int The number of emails sent.
	 */
	public function get_sent_emails_count() {

		return absint( apply_filters( 'automator_review_get_sent_emails_count', automator_get_option( 'automator_sent_email_completed', 0 ), $this ) );
	}

	/**
	 * Retrieves the number of completed recipes.
	 *
	 * @return int The number of completed recipes.
	 */
	public function get_completed_recipes_count() {

		return apply_filters( 'automator_review_get_completed_recipe_count', absint( Automator()->get->completed_recipes_count() ), $this );
	}

	/**
	 * Track review step.
	 *
	 * @param string $banner - The banner key.
	 * @param string $event - The event of the review process.
	 *
	 * @return void
	 */
	public function track_review_step( $banner, $event ) {

		// Bail if any of the values are empty.
		if ( empty( $banner ) || empty( $event ) ) {
			return new WP_REST_Response( array( 'success' => false ), 401 );
		}

		// Create a new WP_REST_Request object
		$request = new WP_REST_Request(
			'POST',
			AUTOMATOR_REST_API_END_POINT . '/log-event/'
		);

		// Set the parameters for the request
		$request->set_body_params(
			array(
				'event' => 'review-tracking',
				'value' => array(
					'banner' => $banner,
					'event'  => $event,
				),
			)
		);

		// Get the instance of Usage_Reports class.
		$reports = Automator_Load::get_core_class_instance( 'Usage_Reports' );
		$reports = is_a( $reports, 'Usage_Reports' ) ? $reports : new Usage_Reports();

		// Log the event.
		return $reports->log_event( $request );
	}
}
