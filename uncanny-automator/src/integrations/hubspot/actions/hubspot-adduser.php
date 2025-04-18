<?php

namespace Uncanny_Automator;

/**
 * Class HUBSPOT_ADDUSER
 *
 * @package Uncanny_Automator
 */
class HUBSPOT_ADDUSER {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'HUBSPOT';

	/**
	 *
	 * @var string
	 */
	private $action_code;

	/**
	 *
	 * @var string
	 */
	private $action_meta;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		$this->action_code = 'HUBSPOTADDUSER';
		$this->action_meta = 'HUBSPOTCONTACT';
		$this->define_action();

	}

	/**
	 * Define and register the action by pushing it into the Automator object.
	 */
	public function define_action() {

		$action = array(
			'author'                => Automator()->get_author_name( $this->action_code ),
			'support_link'          => Automator()->get_author_support_link( $this->action_code, 'integration/hubspot/' ),
			'integration'           => self::$integration,
			'code'                  => $this->action_code,
			// translators: The user
			'sentence'              => sprintf( esc_html__( 'Add/Update {{the user:%1$s}} in HubSpot', 'uncanny-automator' ), $this->action_meta ),
			'select_option_name'    => esc_html__( 'Add/Update {{the user}} in HubSpot', 'uncanny-automator' ),
			'priority'              => 10,
			'accepted_args'         => 1,
			'requires_user'         => true,
			'execution_function'    => array( $this, 'add_contact' ),
			'options_callback'      => array( $this, 'load_options' ),
			'background_processing' => true,
		);

		Automator()->register->action( $action );
	}

	/**
	 * load_options
	 *
	 * @return void
	 */
	public function load_options() {
		return array(
			'options_group' => array(
				$this->action_meta => array(
					array(
						'option_code'       => 'CUSTOM_FIELDS',
						'input_type'        => 'repeater',
						'relevant_tokens'   => array(),
						'label'             => esc_html__( 'Custom fields', 'uncanny-automator' ),
						'description'       => esc_html__( "* User Email Address, First and Last names will be taken from the user's account. Leaving a field value empty will not update the field. To delete a value from a field, set its value to [delete], including the square brackets.", 'uncanny-automator' ),
						'required'          => false,
						'fields'            => array(
							array(
								'option_code'           => 'FIELD_NAME',
								'label'                 => esc_html__( 'Field', 'uncanny-automator' ),
								'input_type'            => 'select',
								'supports_tokens'       => false,
								'supports_custom_value' => false,
								'required'              => true,
								'read_only'             => false,
								'options'               => Automator()->helpers->recipe->hubspot->get_fields( array( 'email', 'firstname', 'lastname' ) ),
							),
							Automator()->helpers->recipe->field->text_field( 'FIELD_VALUE', esc_html__( 'Value', 'uncanny-automator' ), true, 'text', '', false ),
						),
						'add_row_button'    => esc_html__( 'Add field', 'uncanny-automator' ),
						'remove_row_button' => esc_html__( 'Remove field', 'uncanny-automator' ),
						'hide_actions'      => false,
					),
					array(
						'option_code'   => 'UPDATE',
						'input_type'    => 'checkbox',
						'label'         => esc_html__( 'If the contact already exists, update their info', 'uncanny-automator' ),
						'description'   => '',
						'required'      => false,
						'default_value' => true,
					),
				),
			),

		);
	}

	/**
	 * Action validation function.
	 *
	 * @return mixed
	 */
	public function add_contact( $user_id, $action_data, $recipe_id, $args ) {

		$helpers = Automator()->helpers->recipe->hubspot->options;

		$user_data = get_userdata( $user_id );

		$email      = $user_data->user_email;
		$first_name = $user_data->first_name;
		$last_name  = $user_data->last_name;

		$properties = array();

		$properties[] = array(
			'property' => 'email',
			'value'    => $email,
		);

		if ( ! empty( $first_name ) ) {
			$properties[] = array(
				'property' => 'firstname',
				'value'    => $first_name,
			);
		}

		if ( ! empty( $last_name ) ) {
			$properties[] = array(
				'property' => 'lastname',
				'value'    => $last_name,
			);
		}

		$update = true;

		if ( ! empty( $action_data['meta']['UPDATE'] ) ) {
			$update = filter_var( $action_data['meta']['UPDATE'], FILTER_VALIDATE_BOOLEAN );
		}

		if ( ! empty( $action_data['meta']['CUSTOM_FIELDS'] ) ) {

			$json = Automator()->parse->text( $action_data['meta']['CUSTOM_FIELDS'], $recipe_id, $user_id, $args );

			// Replace line breaks to prevent invalid json
			$json = str_replace( "\r\n", '\r\n', $json );

			$custom_fields = json_decode( $json, true );

			if ( ! empty( $custom_fields ) ) {
				foreach ( $custom_fields as $field ) {

					if ( empty( $field['FIELD_NAME'] ) || empty( $field['FIELD_VALUE'] ) ) {
						continue;
					}

					$properties[] = array(
						'property' => $field['FIELD_NAME'],
						'value'    => $field['FIELD_VALUE'],
					);

				}
			}
		}

		$properties = apply_filters(
			'automator_hubspot_add_contact_properties',
			$properties,
			array(
				'user_id'     => $user_id,
				'action_data' => $action_data,
				'recipe_id'   => $recipe_id,
				'args'        => $args,
			)
		);

		try {
			$response = $helpers->create_contact( $properties, $update, $action_data );
			Automator()->complete_action( $user_id, $action_data, $recipe_id );
		} catch ( \Exception $e ) {
			$helpers->log_action_error( $e->getMessage(), $user_id, $action_data, $recipe_id );
		}
	}
}
