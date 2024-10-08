<?php


namespace Uncanny_Automator;

use Uncanny_Automator_Pro\Paid_Memberships_Pro_Pro_Helpers;

/**
 * Class Paid_Memberships_Pro_Helpers
 *
 * @package Uncanny_Automator
 */
class Paid_Memberships_Pro_Helpers {

	/**
	 * @var Paid_Memberships_Pro_Helpers
	 */
	public $options;

	/**
	 * @var Paid_Memberships_Pro_Pro_Helpers
	 */
	public $pro;

	/**
	 * @var bool
	 */
	public $load_options = true;

	/**
	 * Paid_Memberships_Pro_Helpers constructor.
	 */
	public function __construct() {

	}

	/**
	 * @param Paid_Memberships_Pro_Pro_Helpers $pro
	 */
	public function setPro( Paid_Memberships_Pro_Pro_Helpers $pro ) { // phpcs:ignore
		$this->pro = $pro;
	}

	/**
	 * @param Paid_Memberships_Pro_Helpers $options
	 */
	public function setOptions( Paid_Memberships_Pro_Helpers $options ) { // phpcs:ignore
		$this->options = $options;
	}

	/**
	 * @param null $label
	 * @param string $option_code
	 *
	 * @return mixed|void
	 */
	public function all_memberships( $label = null, $option_code = 'PMPMEMBERSHIP' ) {
		if ( ! $this->load_options ) {

			return Automator()->helpers->recipe->build_default_options_array( $label, $option_code );
		}

		if ( ! $label ) {
			$label = esc_attr__( 'Membership', 'uncanny-automator' );
		}

		global $wpdb;
		$qry = "SELECT * FROM $wpdb->pmpro_membership_levels ORDER BY id ASC";
		// Ignored no need to escape since there are no arguments to accept.
		$levels  = $wpdb->get_results( $qry ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$options = array();
		if ( $levels ) {
			$options['-1'] = esc_attr__( 'Any membership', 'uncanny-automator' );
			foreach ( $levels as $level ) {
				$options[ $level->id ] = $level->name;
			}
		}
		$option = array(
			'option_code'     => $option_code,
			'label'           => $label,
			'input_type'      => 'select',
			'required'        => true,
			'options'         => $options,
			'relevant_tokens' => array(
				$option_code                          => esc_attr__( 'Membership title', 'uncanny-automator' ),
				$option_code . '_ID'                  => esc_attr__( 'Membership ID', 'uncanny-automator' ),
				$option_code . '_USER_ID'             => esc_attr__( 'User ID', 'uncanny-automator' ),
				$option_code . '_DISCOUNT_CODE'       => esc_attr__( 'Discount code', 'uncanny-automator' ),
				$option_code . '_DISCOUNT_CODE_ID'    => esc_attr__( 'Discount code ID', 'uncanny-automator' ),
				$option_code . '_INITIAL_AMOUNT'      => esc_attr__( 'Initial amount', 'uncanny-automator' ),
				$option_code . '_SUBSCRIPTION_ID'     => esc_attr__( 'Subscription ID', 'uncanny-automator' ),
				$option_code . '_SUBSCRIPTION_AMOUNT' => esc_attr__( 'Subscription amount', 'uncanny-automator' ),
				$option_code . '_SUBSCRIPTION_PERIOD' => esc_attr__( 'Subscription period', 'uncanny-automator' ),
				$option_code . '_SUBSCRIPTION_CYCLE'  => esc_attr__( 'Subscription cycle number', 'uncanny-automator' ),
				$option_code . '_SUBSCRIPTION_START'  => esc_attr__( 'Subscription start date', 'uncanny-automator' ),
				$option_code . '_SUBSCRIPTION_END'    => esc_attr__( 'Subscription end date', 'uncanny-automator' ),
			),
		);

		return apply_filters( 'uap_option_all_pmp_memberships', $option );
	}
}
