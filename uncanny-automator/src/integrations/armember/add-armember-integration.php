<?php

namespace Uncanny_Automator;

/**
 * Class Add_Armember_Integration
 *
 * @package Uncanny_Automator
 */
class Add_Armember_Integration {

	use Recipe\Integrations;

	/**
	 * Add_Affwp_Integration constructor.
	 */
	public function __construct() {
		$this->setup();
	}

	/**
	 *
	 */
	protected function setup() {
		$this->set_integration( 'ARMEMBER' );
		$this->set_name( 'ARMember' );
		$this->set_icon( 'armember-icon.svg' );
		$this->set_icon_path( __DIR__ . '/img/' );
		$this->set_plugin_file_path( 'armember-membership/armember-membership.php' );
	}

	/**
	 * @return bool
	 */
	public function plugin_active() {
		// Check if the plugin is active
		if ( ! DEFINED( 'MEMBERSHIPLITE_DIR_NAME' ) && ! defined( 'MEMBERSHIP_DIR_NAME' ) ) {
			return false;
		}

		include_once __DIR__ . '/triggers/armember-membership-added.php';

		new \Uncanny_Automator\ARMEMBER_MEMBERSHIP_ADDED();

		return true;
	}
}
