<?php

namespace Uncanny_Automator;

/**
 * Class MAKE_DONATION
 *
 * @package Uncanny_Automator
 */
class MAKE_DONATION {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'GIVEWP';

	/**
	 * @var string
	 */
	private $trigger_code;
	/**
	 * @var string
	 */
	private $trigger_meta;

	/**
	 * SetAutomatorTriggers constructor.
	 */
	public function __construct() {
		$this->trigger_code = 'MAKEDONATION';
		$this->trigger_meta = 'GIVEWPMAKEDONATION';
		$this->define_trigger();
	}

	/**
	 * Define trigger settings
	 */
	public function define_trigger() {

		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/givewp/' ),
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			/* translators: Logged-in trigger - GiveWP */
			'sentence'            => sprintf( esc_html__( 'A user makes a donation via {{a form:%1$s}}', 'uncanny-automator' ), $this->trigger_meta ),
			/* translators: Logged-in trigger - GiveWP */
			'select_option_name'  => esc_html__( 'A user makes a donation via {{a form}}', 'uncanny-automator' ),
			'action'              => 'give_update_payment_status',
			'priority'            => 10,
			'accepted_args'       => 3,
			'validation_function' => array( $this, 'givewp_make_donation' ),
			'options_callback'    => array( $this, 'load_options' ),
		);

		Automator()->register->trigger( $trigger );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					Automator()->helpers->recipe->give->options->list_all_give_forms(
						esc_html__( 'Form', 'uncanny-automator' ),
						$this->trigger_meta,
						array(),
						array(
							'DONATION_ID' => esc_attr__( 'Donation ID', 'uncanny-automator' ),
							'PAYMENT_ID'  => esc_attr__( 'Payment ID', 'uncanny-automator' ),
						)
					),
				),
			)
		);
	}

	/**
	 * @param $payment_id
	 * @param $status
	 * @param $old_status
	 */
	public function givewp_make_donation( $payment_id, $status, $old_status ) {

		if ( 'publish' !== $status ) {
			return;
		}

		$payment = new \Give_Payment( $payment_id );

		// Sanity check... fail if donation ID is invalid
		if ( empty( $payment ) ) {
			return;
		}
		$payment_exists = $payment->ID;
		if ( empty( $payment_exists ) ) {
			return;
		}

		$give_form_id = $payment->form_id;
		$user_id      = $payment->user_id;
		$amount       = $payment->total;

		if ( 0 === $user_id ) {
			// Its a logged in recipe and
			// user ID is 0. Skip process
			return;
		}

		$payment_data                    = json_decode( wp_json_encode( $payment ), true );
		$payment_data['give_form_id']    = $give_form_id;
		$payment_data['give_form_title'] = $payment->form_title;
		$payment_data['currency']        = $payment->currency;
		$payment_data['give_price_id']   = $payment->price_id;
		$payment_data['price']           = $payment->total;
		$payment_data['user_info']       = give_get_payment_meta_user_info( $payment_id );
		$payment_data['user_email']      = $payment_data['user_info']['email'];
		$payment_data['give_comment']    = esc_html__( '-', 'uncanny-automator' );

		if ( give_is_donor_comment_field_enabled( $give_form_id ) ) {
			global $wpdb;
			$payment_data['give_comment'] = $wpdb->get_var( $wpdb->prepare( "SELECT comment_content  FROM {$wpdb->prefix}give_comments WHERE comment_type LIKE %s AND comment_parent=%d", '%%donor_donation%%', $payment_id ) );
		}

		$form_fields       = Automator()->helpers->recipe->give->get_form_fields_and_ffm( $give_form_id );
		$custom_field_data = give_get_meta( $payment_id, '_give_payment_meta', true );

		foreach ( $form_fields as $i => $field ) {
			if ( $field['custom'] == true ) {
				$payment_data[ $field['key'] ] = $custom_field_data[ $field['key'] ];
			}
		}

		$recipes            = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		$required_form      = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );
		$matched_recipe_ids = array();

		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$trigger_id = $trigger['ID'];//return early for all products
				if ( isset( $required_form[ $recipe_id ] ) && isset( $required_form[ $recipe_id ][ $trigger_id ] ) ) {
					//Add where option is set to Any Form
					if ( intval( '-1' ) === intval( $required_form[ $recipe_id ][ $trigger_id ] ) || absint( $give_form_id ) === absint( $required_form[ $recipe_id ][ $trigger_id ] ) ) {
						$matched_recipe_ids[] = array(
							'recipe_id'  => $recipe_id,
							'trigger_id' => $trigger_id,
						);
					}
				}
			}
		}

		if ( empty( $matched_recipe_ids ) ) {
			return;
		}
		foreach ( $matched_recipe_ids as $matched_recipe_id ) {
			$pass_args = array(
				'code'             => $this->trigger_code,
				'meta'             => $this->trigger_meta,
				'user_id'          => $user_id,
				'recipe_to_match'  => $matched_recipe_id['recipe_id'],
				'trigger_to_match' => $matched_recipe_id['trigger_id'],
				'ignore_post_id'   => true,
			);

			$args = Automator()->maybe_add_trigger_entry( $pass_args, false );

			if ( $args ) {
				foreach ( $args as $result ) {
					if ( true === $result['result'] ) {

						$trigger_meta = array(
							'user_id'        => $user_id,
							'trigger_id'     => $result['args']['trigger_id'],
							'trigger_log_id' => $result['args']['get_trigger_id'],
							'run_number'     => $result['args']['run_number'],
						);

						$trigger_meta['meta_key']   = $this->trigger_meta . '_ID';
						$trigger_meta['meta_value'] = maybe_serialize( $give_form_id );
						Automator()->insert_trigger_meta( $trigger_meta );

						$trigger_meta['meta_key']   = $this->trigger_meta;
						$trigger_meta['meta_value'] = maybe_serialize( $payment_data['give_form_title'] );
						Automator()->insert_trigger_meta( $trigger_meta );

						$trigger_meta['meta_key']   = 'ACTUALDONATEDAMOUNT';
						$trigger_meta['meta_value'] = maybe_serialize( $amount );
						Automator()->insert_trigger_meta( $trigger_meta );

						$trigger_meta['meta_key']   = 'payment_data';
						$trigger_meta['meta_value'] = maybe_serialize( $payment_data );
						Automator()->insert_trigger_meta( $trigger_meta );

						Automator()->db->token->save( 'DONATION_ID', $payment->number, $trigger_meta );
						Automator()->db->token->save( 'PAYMENT_ID', $payment_id, $trigger_meta );

						Automator()->maybe_trigger_complete( $result['args'] );
					}
				}
			}
		}
	}
}
