<?php

namespace Uncanny_Automator;

/**
 * Automator_Send_Webhook_Fields
 */
class Automator_Send_Webhook_Fields {
	/**
	 * Automator_Send_Webhook_Fields instance
	 *
	 * @var
	 */
	public static $instance;
	/**
	 * Store data types
	 *
	 * @var array
	 */
	private $data_format_types;
	/**
	 * @var array
	 */
	private $data_types = array();
	/**
	 * @var array
	 */
	private $data_types_key_store = array();

	/**
	 * Get instance
	 *
	 * @return Automator_Send_Webhook_Fields
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
	}

	/**
	 * Get data format types.
	 *
	 * @return mixed
	 */
	public function get_data_format_types() {
		return apply_filters(
			'automator_outgoing_webhook_content_types',
			array(
				'x-www-form-urlencoded' => 'x-www-form-urlencoded',
				'form-data'             => 'form-data',
				'json'                  => 'JSON',
				'plain'                 => 'Text',
				'html'                  => 'HTML',
				'xml'                   => 'XML',
				'GraphQL'               => 'GraphQL',
				'raw'                   => 'Raw',
			)
		);
	}

	/**
	 * Return options Group for all outgoing webhooks
	 *
	 * @param $action_meta
	 * @param bool $data_format_required
	 * @param null $default
	 * @param array $allowed
	 *
	 * @return array
	 */
	public function options_group( $action_meta, $data_format_required = true, $default = null, $allowed = array() ) {

		if ( null === $default ) {
			$default = 'x-www-form-urlencoded';
		}

		$fields = array();

		// Webhook URL
		$fields[] = array(
			'input_type'      => 'url',
			'option_code'     => 'WEBHOOK_URL',
			'label'           => esc_attr__( 'URL', 'uncanny-automator' ),
			'supports_tokens' => true,
			'required'        => true,
			'description'     => esc_attr__( 'Enter the URL of the destination webhook.', 'uncanny-automator' ),
		);

		// Action event
		$fields[] = array(
			'input_type'    => 'select',
			'option_code'   => 'ACTION_EVENT',
			/* translators: HTTP request method */
			'label'         => esc_attr__( 'Request method', 'uncanny-automator' ),
			'description'   => esc_attr__( 'Select the HTTP request method supported by the webhook destination. If you are unsure, leave this value unchanged unless you are experiencing issues.', 'uncanny-automator' ),
			'required'      => true,
			'default_value' => 'POST',
			'options'       => array(
				'GET'     => 'GET',
				'PUT'     => 'PUT',
				'POST'    => 'POST',
				'PATCH'   => 'PATCH',
				'DELETE'  => 'DELETE',
				'HEAD'    => 'HEAD',
				'OPTIONS' => 'OPTIONS',
			),
		);

		// Data format field
		if ( $data_format_required ) {
			$options  = $this->get_data_format_types();
			$new_list = array();
			if ( ! empty( $allowed ) ) {
				foreach ( $allowed as $allow ) {
					$value              = isset( $options[ $allow ] ) ? $options[ $allow ] : strtoupper( $allow );
					$new_list[ $allow ] = $value;
				}
				$options = $new_list;
			}
			$data_formats = array(
				'input_type'            => 'select',
				'option_code'           => 'DATA_FORMAT',
				/* translators: HTTP request method */
				'label'                 => esc_attr__( 'Data format', 'uncanny-automator' ),
				'description'           => esc_attr__( 'Select the data format supported by the webhook destination. If you are unsure, leave this value unchanged unless you are experiencing issues.', 'uncanny-automator' ),
				'required'              => false,
				'supports_custom_value' => false,
				'default_value'         => $default,
				'options'               => $options,
				'dynamic_visibility'    => array(
					'default_state'    => 'visible',
					'visibility_rules' => array(
						array(
							'operator'             => 'AND',
							'rule_conditions'      => array(
								array(
									'option_code' => 'ACTION_EVENT',
									'compare'     => '==',
									'value'       => 'GET',
								),
							),
							'resulting_visibility' => 'hide',
						),
					),
				),
			);
			$fields[]     = $data_formats;
		}

		// Authorizations.
		$fields[] = array(
			'input_type'  => 'text',
			'option_code' => 'WEBHOOK_AUTHORIZATIONS',
			'label'       => esc_attr__( 'Authorization', 'uncanny-automator' ),
			'description' => esc_attr__( 'The authorization string will be automatically incorporated into the header. Once saved, the value will be masked.', 'uncanny-automator' ),
			'required'    => false,
		);

		// Header
		$fields[] = array(
			'input_type'        => 'repeater',
			'option_code'       => 'WEBHOOK_HEADERS',
			'label'             => esc_attr__( 'Headers', 'uncanny-automator' ),
			'description'       => esc_attr__( 'Add any HTTP request headers required by the webhook destination.', 'uncanny-automator' ),
			'required'          => false,
			'default_value'     => array(
				array(
					'NAME'  => 'Accept',
					'VALUE' => 'application/json',
				),
			),
			'fields'            => array(
				array(
					'input_type'      => 'text',
					'option_code'     => 'NAME',
					'label'           => esc_attr__( 'Name', 'uncanny-automator' ),
					'supports_tokens' => true,
					'required'        => true,
				),
				array(
					'input_type'      => 'text',
					'option_code'     => 'VALUE',
					'label'           => esc_attr__( 'Value', 'uncanny-automator' ),
					'supports_tokens' => true,
					'required'        => true,
				),
			),

			/* translators: Non-personal infinitive verb */
			'add_row_button'    => esc_attr__( 'Add header', 'uncanny-automator' ),
			/* translators: Non-personal infinitive verb */
			'remove_row_button' => esc_attr__( 'Remove header', 'uncanny-automator' ),
		);

		// Fields.
		$fields[] = array(
			'input_type'        => 'repeater',
			'option_code'       => 'WEBHOOK_FIELDS',
			'label'             => esc_attr__( 'Body', 'uncanny-automator' ),
			'required'          => false,
			'fields'            => array(
				array(
					'input_type'      => 'text',
					'option_code'     => 'KEY',
					'label'           => esc_attr__( 'Key', 'uncanny-automator' ),
					'supports_tokens' => true,
					'required'        => false,
					'placeholder'     => esc_html__( 'first_name', 'uncanny-automator' ),
					'description'     => sprintf( '<i>%s</i>', esc_html__( 'Separate keys with / to build nested data.', 'uncanny-automator' ) ),
				),
				array(
					'option_code'     => 'VALUE_TYPE',
					'label'           => esc_html__( 'Data type', 'uncanny-automator' ),
					'input_type'      => 'select',
					'required'        => false,
					'options_show_id' => false,
					'options'         => $this->get_webhook_data_types(),
				),
				array(
					'input_type'      => 'text',
					'option_code'     => 'VALUE',
					'label'           => esc_attr__( 'Value', 'uncanny-automator' ),
					'supports_tokens' => true,
					'required'        => false,
				),
			),

			/* translators: Non-personal infinitive verb */
			'add_row_button'    => esc_attr__( 'Add pair', 'uncanny-automator' ),
			/* translators: Non-personal infinitive verb */
			'remove_row_button' => esc_attr__( 'Remove pair', 'uncanny-automator' ),
		);

		return array( $action_meta => $fields );
	}

	/**
	 * Return buttons for webhooks
	 *
	 * @param $action_meta
	 * @param string $support_link
	 *
	 * @return array
	 */
	public function buttons( $action_meta, $support_link = 'https://automatorplugin.com/knowledge-base/send-data-to-a-webhook/?utm_source=uncanny_automator&utm_medium=automator-send_data_to_webhook&utm_content=help_button' ) {
		return array(
			array(
				'show_in'     => $action_meta,
				'text'        => esc_attr__( 'Help', 'uncanny-automator' ),
				'css_classes' => 'uap-btn uap-btn--transparent',
				'on_click'    => 'function(){ window.open( "' . esc_url_raw( $support_link ) . '", "_blank" ); }',
			),
			array(
				'show_in'     => $action_meta,
				'text'        => esc_attr__( 'Check data format', 'uncanny-automator' ),
				'css_classes' => 'uap-btn uap-btn--primary',
				'on_click'    => Automator()->send_webhook->build_sample_data(),
				'modules'     => array( 'markdown' ),
			),
			array(
				'show_in'     => $action_meta,
				/* translators: Non-personal infinitive verb */
				'text'        => esc_attr__( 'Send test', 'uncanny-automator' ),
				'css_classes' => 'uap-btn uap-btn--red',
				'on_click'    => Automator()->send_webhook->send_test_js(),
				'modules'     => array( 'markdown' ),
			),
		);
	}

	/**
	 * @return mixed|null
	 */
	public function get_webhook_data_types() {
		return apply_filters(
			'automator_outgoing_webhook_data_types',
			array(
				array(
					'value' => 'text',
					'text' => esc_html__( 'Text', 'uncanny-automator' ),
				),
				array(
					'value' => 'float',
					'text' => esc_html__( 'Number', 'uncanny-automator' ),
				),
				array(
					'value' => 'bool',
					'text' => esc_html__( 'Boolean', 'uncanny-automator' ),
				),
				array(
					'value' => 'null',
					'text' => esc_html__( 'NULL', 'uncanny-automator' ),
				),
			)
		);
	}
}
