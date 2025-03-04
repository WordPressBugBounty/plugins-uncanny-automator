<?php

namespace Uncanny_Automator;

use Uncanny_Automator_Pro\Give_Pro_Helpers;

/**
 * Class Give_Helpers
 *
 * @package Uncanny_Automator
 */
class Give_Helpers {

	/**
	 * @var Give_Helpers
	 */
	public $options;
	/**
	 * @var Give_Pro_Helpers
	 */
	public $pro;
	/**
	 * @var true
	 */
	public $load_options = true;

	/**
	 * Give_Helpers constructor.
	 */
	public function __construct() {

	}

	/**
	 * @param Give_Helpers $options
	 */
	public function setOptions( Give_Helpers $options ) {
		$this->options = $options;
	}

	/**
	 * @param Give_Pro_Helpers $pro
	 */
	public function setPro( Give_Pro_Helpers $pro ) {
		$this->pro = $pro;
	}

	/**
	 * @param $label
	 * @param $option_code
	 * @param $args
	 * @param $tokens
	 *
	 * @return mixed|null
	 */
	public function list_all_give_forms( $label = null, $option_code = 'MAKEDONATION', $args = array(), $tokens = array() ) {

		if ( ! $label ) {
			$label = esc_html__( 'Form', 'uncanny-automator' );
		}

		$token          = key_exists( 'token', $args ) ? $args['token'] : false;
		$is_ajax        = key_exists( 'is_ajax', $args ) ? $args['is_ajax'] : false;
		$target_field   = key_exists( 'target_field', $args ) ? $args['target_field'] : '';
		$end_point      = key_exists( 'endpoint', $args ) ? $args['endpoint'] : '';
		$default_tokens = array(
			'ACTUALDONATEDAMOUNT' => esc_attr__( 'Donated amount', 'uncanny-automator' ),
			$option_code          => esc_attr__( 'Form title', 'uncanny-automator' ),
			$option_code . '_ID'  => esc_attr__( 'Form ID', 'uncanny-automator' ),
		);
		if ( ! empty( $tokens ) ) {
			$default_tokens = $default_tokens + $tokens;
		}
		$options = array();

		$query_args = array(
			'post_type'      => 'give_forms',
			'posts_per_page' => 9999,
			'post_status'    => 'publish',
		);
		$options    = Automator()->helpers->recipe->wp_query( $query_args, true, esc_html__( 'Any form', 'uncanny-automator' ) );
		$type       = 'select';

		$option = array(
			'option_code'     => $option_code,
			'label'           => $label,
			'input_type'      => $type,
			'required'        => true,
			'supports_tokens' => $token,
			'is_ajax'         => $is_ajax,
			'fill_values_in'  => $target_field,
			'endpoint'        => $end_point,
			'options'         => $options,
			'relevant_tokens' => $default_tokens,
		);

		return apply_filters( 'uap_option_list_all_give_forms', $option );
	}

	/**
	 * @param null $form_id
	 *
	 * @return mixed|void
	 */
	public function get_form_fields_and_ffm( $form_id = null ) {

		$fields = array(
			'give_title'    => array(
				'type'     => 'text',
				'required' => true,
				'label'    => esc_html__( 'Name title prefix', 'uncanny-automator' ),
				'key'      => 'title',
			),
			'give_first'    => array(
				'type'     => 'text',
				'required' => true,
				'label'    => esc_html__( 'First name', 'uncanny-automator' ),
				'key'      => 'first_name',
			),
			'give_last'     => array(
				'type'     => 'text',
				'required' => false,
				'label'    => esc_html__( 'Last name', 'uncanny-automator' ),
				'key'      => 'last_name',
			),
			'give_email'    => array(
				'type'     => 'email',
				'required' => true,
				'label'    => esc_html__( 'Email', 'uncanny-automator' ),
				'key'      => 'user_email',
			),
			'give-amount'   => array(
				'type'     => 'tel',
				'required' => true,
				'label'    => esc_html__( 'Donation amount', 'uncanny-automator' ),
				'key'      => 'price',
			),
			'give_currency' => array(
				'type'     => 'text',
				'required' => true,
				'label'    => esc_html__( 'Currency', 'uncanny-automator' ),
				'key'      => 'currency',
			),
			'give_comment'  => array(
				'type'     => 'text',
				'required' => true,
				'label'    => esc_html__( 'Comment', 'uncanny-automator' ),
				'key'      => 'give_comment',
			),
			'address1'      => array(
				'type'     => 'text',
				'required' => true,
				'label'    => esc_html__( 'Address line 1', 'uncanny-automator' ),
				'key'      => 'address1',
			),
			'address2'      => array(
				'type'     => 'text',
				'required' => true,
				'label'    => esc_html__( 'Address line 2', 'uncanny-automator' ),
				'key'      => 'address2',
			),
			'city'          => array(
				'type'     => 'text',
				'required' => true,
				'label'    => esc_html__( 'City', 'uncanny-automator' ),
				'key'      => 'city',
			),
			'state'         => array(
				'type'     => 'text',
				'required' => true,
				'label'    => esc_html__( 'State', 'uncanny-automator' ),
				'key'      => 'state',
			),
			'zip'           => array(
				'type'     => 'text',
				'required' => true,
				'label'    => esc_html__( 'Zip', 'uncanny-automator' ),
				'key'      => 'zip',
			),
			'country'       => array(
				'type'     => 'text',
				'required' => true,
				'label'    => esc_html__( 'Country', 'uncanny-automator' ),
				'key'      => 'country',
			),
		);

		if ( class_exists( '\Give_FFM_Render_Form' ) && $form_id != null && $form_id != '-1' ) {

			$customFormFields = array();
			if ( method_exists( '\Give_FFM_Render_Form', 'get_input_fields' ) ) {
				$customFormFields = \Give_FFM_Render_Form::get_input_fields( $form_id );
			} elseif ( class_exists( '\GiveFormFieldManager\Helpers\Form' ) && method_exists( '\GiveFormFieldManager\Helpers\Form', 'get_input_fields' ) ) {
				$customFormFields = \GiveFormFieldManager\Helpers\Form::get_input_fields( $form_id );
			}

			if ( ! empty( $customFormFields ) ) {
				if ( ! empty( $customFormFields[2] ) && is_array( $customFormFields[2] ) ) {
					foreach ( $customFormFields[2] as $custom_form_field ) {
						$custom_form_field['required']        = ( 'no' === $custom_form_field['required'] ) ? false : true;
						$fields[ $custom_form_field['name'] ] = array(
							'type'     => $custom_form_field['input_type'],
							'required' => $custom_form_field['required'],
							'label'    => $custom_form_field['label'],
							'key'      => $custom_form_field['name'],
							'custom'   => true,
						);
					}
				}
			}
		}

		return apply_filters( 'automator_give_wp_form_field', $fields );
	}

}
