<?php

namespace Uncanny_Automator;

/**
 * Class Admin_Settings_Premium_Integrations
 *
 * @since   3.7
 * @version 3.7
 * @package Uncanny_Automator
 * @author  Agustin B.
 */
class Admin_Settings_Premium_Integrations {
	/**
	 * Class constructor
	 */
	public function __construct() {
		// Define the tab
		add_filter( 'automator_settings_sections', array( $this, 'create_tab' ) );
	}

	/**
	 * Adds the tab using the automator_settings_tab filter
	 */
	public function create_tab( $tabs ) {

		// App integrations
		$tabs['premium-integrations'] = (object) array(
			'name'     => esc_html__( 'App integrations', 'uncanny-automator' ),
			'function' => array( $this, 'tab_output' ),
			'preload'  => false, // Determines if the content should be loaded even if the tab is not selected
		);

		return $tabs;
	}

	/**
	 * Outputs the content of the "App integrations" tab
	 */
	public function tab_output() {
		// Get the tabs
		$integrations_tabs = apply_filters( 'automator_settings_premium_integrations_tabs', array() );

		// Get the current tab
		$current_integration = automator_filter_has_var( 'integration' ) ? sanitize_text_field( automator_filter_input( 'integration' ) ) : '';

		// Check if the user has access to the premium integrations
		// This will be true if the site is connected (Automator Free) or if the
		// user has Automator Pro activated
		$user_can_use_premium_integrations = Api_Server::is_automator_connected() || is_automator_pro_active();

		// Get the link to upgrade to Pro
		$upgrade_to_pro_url = add_query_arg(
			// UTM
			array(
				'utm_source'  => 'uncanny_automator',
				'utm_medium'  => 'settings',
				'utm_content' => 'premium_integrations_connect',
			),
			'https://automatorplugin.com/pricing/'
		);

		// Get the link to the article about credits
		$credits_article_url = add_query_arg(
			// UTM
			array(
				'utm_source'  => 'uncanny_automator',
				'utm_medium'  => 'settings',
				'utm_content' => 'premium_integrations_connect',
			),
			'https://automatorplugin.com/knowledge-base/what-are-credits/'
		);

		// Get the link to connect the site
		$connect_site_url = add_query_arg(
			array(
				'post_type' => 'uo-recipe',
				'page'      => 'uncanny-automator-setup-wizard',
			),
			admin_url( 'edit.php' )
		);

		// Check if the user is requesting the focus version
		$layout_version = automator_filter_has_var( 'automator_hide_settings_tabs' ) ? 'focus' : 'default';

		// Add the actions and get the selected tab
		foreach ( $integrations_tabs as $tab_key => &$tab ) {
			// Ensure the tab is an object.
			$tab = (object) $tab;
			// Maybe add an action to show the settings for a tab.
			$this->maybe_add_action_to_show_settings( $tab_key, $tab, $user_can_use_premium_integrations );
			// Set the selected state.
			$tab->is_selected = ! $user_can_use_premium_integrations && $tab->requires_credits
				? false
				: $tab_key === $current_integration;

			if ( $tab_key === $current_integration ) {
				$current_integration_tab = $tab;
			}
		}

		// Load the view
		include Utilities::automator_get_view( 'admin-settings/tab/premium-integrations.php' );
	}

	/**
	 * Maybe add an action to show the settings for a tab.
	 *
	 * @param string $tab_key The key of the tab.
	 * @param object $tab The tab object.
	 * @param boolean $user_can_use_premium_integrations Whether the user can use the premium integrations.
	 *
	 * @return void
	 */
	private function maybe_add_action_to_show_settings( $tab_key, $tab, $user_can_use_premium_integrations ) {
		// Bail if the tab function is not defined.
		if ( ! isset( $tab->function ) ) {
			return;
		}

		// Bail if user is not connected and the integration requires credits.
		if ( ! $user_can_use_premium_integrations && $tab->requires_credits ) {
			return;
		}

		add_action( 'automator_settings_premium_integrations_' . $tab_key . '_tab', $tab->function );
	}

	/**
	 * Returns the link of the premium integrations tab
	 *
	 * @param  string $selected_tab Optional. The ID of the integration
	 * @return string               The URL
	 */
	public static function utility_get_premium_integrations_page_link( $selected_tab = '' ) {
		// Define the list of URL parameters
		$url_parameters = array(
			'post_type' => 'uo-recipe',
			'page'      => 'uncanny-automator-config',
			'tab'       => 'premium-integrations',
		);

		// Check if there is a selected tab defined
		if ( ! empty( $selected_tab ) ) {
			$url_parameters['integration'] = $selected_tab;
		}

		// Return the URL
		return add_query_arg(
			$url_parameters,
			admin_url( 'edit.php' )
		);
	}
}

new Admin_Settings_Premium_Integrations();
