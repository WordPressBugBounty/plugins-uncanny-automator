<?php
namespace Uncanny_Automator;

/**
 * Class Add_ConvertKit_Integration
 *
 * @package Uncanny_Automator
 */
class Add_ConvertKit_Integration {

	use Recipe\Integrations;

	/**
	 * Add_Integration constructor.
	 */
	public function __construct() {
		$this->setup();
	}


	/**
	 * Integration Set-up.
	 *
	 * @return void.
	 */
	protected function setup() {

		$this->set_integration( 'CONVERTKIT' );

		$this->set_name( 'Kit' );

		$this->set_icon( __DIR__ . '/img/kit-icon.svg' );

		$this->set_connected( automator_get_option( 'automator_convertkit_client', false ) );

		$this->set_settings_url( automator_get_premium_integrations_settings_url( 'convertkit' ) );

	}

	/**
	 * Determines whether the plugin dependency is active or not.
	 *
	 * @return bool True. Always.
	 */
	public function plugin_active() {
		return true;
	}
}
