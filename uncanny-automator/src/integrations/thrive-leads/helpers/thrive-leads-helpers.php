<?php

namespace Uncanny_Automator;

use TCB\inc\helpers\FormSettings;

/**
 * Class Thrive_Leads_Helpers
 *
 * @package Uncanny_Automator
 */
class Thrive_Leads_Helpers {

	/**
	 * @param $args
	 *
	 * @return array|mixed|void
	 */
	public function get_all_thrive_lead_forms( $args = array() ) {
		$defaults = array(
			'option_code'           => 'TL_FORMS',
			'label'                 => esc_attr__( 'Form', 'uncanny-automator' ),
			'is_any'                => false,
			'is_all'                => false,
			'supports_custom_value' => false,
			'relevant_tokens'       => array(),
		);

		$args = wp_parse_args( $args, $defaults );

		$all_forms = array();
		$lg_ids    = $this->get_thrive_leads();
		$processed_forms = array(); // Track processed forms to avoid duplicates
		
		foreach ( $lg_ids as $lg_id => $lg_parent ) {
			$variations = tve_leads_get_form_variations( $lg_parent );
			foreach ( $variations as $variation ) {
				// Use form parent ID as key to avoid duplicates
				$form_key = $lg_parent;
				$form_title = $variation['post_title'];
				
				// Only add if we haven't processed this form parent ID yet
				if ( ! isset( $processed_forms[ $form_key ] ) ) {
					$all_forms[ $form_key ] = $form_title;
					$processed_forms[ $form_key ] = true;
				}
			}
		}

		if ( true === $args['is_any'] ) {
			$all_forms = array( '-1' => esc_html__( 'Any form', 'uncanny-automator' ) ) + $all_forms;
		}

		if ( true === $args['is_all'] ) {
			$all_forms = array( '-1' => esc_html__( 'All forms', 'uncanny-automator' ) ) + $all_forms;
		}

		$option = array(
			'option_code'           => $args['option_code'],
			'label'                 => $args['label'],
			'input_type'            => 'select',
			'required'              => true,
			'options_show_id'       => false,
			'relevant_tokens'       => $args['relevant_tokens'],
			'options'               => $all_forms,
			'supports_custom_value' => $args['supports_custom_value'],
		);

		return apply_filters( 'uap_option_get_all_thrive_lead_forms', $option );
	}

	/**
	 * @param $form_id
	 *
	 * @return array
	 */
	public function get_form_fields_by_form_id( $form_id ) {
		$fields = array();
		$lg_ids = $this->get_thrive_leads( $form_id );
		foreach ( $lg_ids as $lg_id => $lg_parent ) {
			$lg_post   = FormSettings::get_one( $lg_id );
			$lg_config = $lg_post->get_config( false );
			foreach ( $lg_config['inputs'] as $key => $input ) {
				if ( 'password' !== $input['type'] && 'confirm_password' !== $input['type'] ) {
					$fields[ $key ] = $input;
				}
			}
		}

		return $fields;
	}

	/**
	 * @param $form_id
	 *
	 * @return int[]|\WP_Post[]
	 */
	public function get_thrive_leads( $form_id = null ) {
		$lead_args = array(
			'post_type'      => '_tcb_form_settings',
			'posts_per_page' => 99999, //phpcs:ignore
			'post_status'    => 'any',
		);

		if ( is_numeric( $form_id ) ) {
			$lead_args['post_parent'] = $form_id;
		}

		$leads  = get_posts( $lead_args );
		$return = array();

		if ( $leads ) {
			foreach ( $leads as $l ) {
				$return[ $l->ID ] = $l->post_parent;
			}
		}

		return $return;
	}
}
