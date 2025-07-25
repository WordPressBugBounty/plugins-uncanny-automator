<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
namespace Uncanny_Automator;

/**
 * Class AC_ANNON_ADD
 *
 * @package Uncanny_Automator
 */
class AC_ANNON_ADD {

	use Recipe\Actions;

	public $prefix = 'AC_ANNON_ADD';

	protected $ac_endpoint_uri = AUTOMATOR_API_URL . 'v2/active-campaign';

	/**
	 * Constructor.
	 *
	 * @return void.
	 */
	public function __construct() {
		$this->setup_action();
	}

	/**
	 * Setup Action.
	 *
	 * @return void.
	 */
	protected function setup_action() {

		$this->set_integration( 'ACTIVE_CAMPAIGN' );
		$this->set_action_code( $this->prefix . '_CODE' );
		$this->set_action_meta( $this->prefix . '_META' );
		$this->set_is_pro( false );
		$this->set_requires_user( false );

		// translators: %1$s is the contact email.
		$this->set_sentence( sprintf( esc_attr_x( 'Add {{a contact:%1$s}} to ActiveCampaign', 'ActiveCampaign', 'uncanny-automator' ), $this->get_action_meta() ) );

		$this->set_readable_sentence( esc_attr_x( 'Add {{a contact}} to ActiveCampaign', 'ActiveCampaign', 'uncanny-automator' ) );

		$this->set_options_callback( array( $this, 'load_options' ) );

		$this->set_background_processing( true );

		$this->register_action();
	}

	/**
	 * Load the options.
	 *
	 * @return array
	 */
	public function load_options() {
		$options_group = array(
			$this->get_action_meta() => $this->get_fields(),
		);

		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => $options_group,
			)
		);
	}

	/**
	 * Proccess our action.
	 *
	 * @param int $user_id
	 * @param array $action_data
	 * @param int $recipe_id
	 * @param array $args
	 * @param $parsed
	 *
	 * @return void.
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$email     = isset( $parsed[ $this->get_action_meta() ] ) ? sanitize_text_field( $parsed[ $this->get_action_meta() ] ) : 0;
		$firstname = isset( $parsed[ $this->prefix . '_FIRST_NAME' ] ) ? sanitize_text_field( $parsed[ $this->prefix . '_FIRST_NAME' ] ) : 0;
		$lastname  = isset( $parsed[ $this->prefix . '_LAST_NAME' ] ) ? sanitize_text_field( $parsed[ $this->prefix . '_LAST_NAME' ] ) : 0;
		$phone     = isset( $parsed[ $this->prefix . '_PHONE' ] ) ? sanitize_text_field( $parsed[ $this->prefix . '_PHONE' ] ) : 0;
		$is_update = isset( $parsed[ $this->prefix . '_UPDATE_IF_CONTACT_EXISTS' ] ) ? $parsed[ $this->prefix . '_UPDATE_IF_CONTACT_EXISTS' ] : 'false';
		$is_update = trim( wp_strip_all_tags( $is_update ) );

		$ac_helper = Automator()->helpers->recipe->active_campaign->options;

		$custom_fields = $ac_helper->get_registered_fields( $parsed, $this->prefix );

		$body = array(
			'action'         => 'add_contact',
			'email'          => $email,
			'firstName'      => $firstname,
			'lastName'       => $lastname,
			'phone'          => $phone,
			'updateIfExists' => $is_update, // String.,
			'fields'         => $custom_fields,
		);

		$body = $ac_helper->filter_add_contact_api_body(
			$body,
			array(
				'user_id'     => $user_id,
				'action_data' => $action_data,
				'parsed'      => $parsed,
				'args'        => $args,
				'recipe_id'   => $recipe_id,
			)
		);

		try {
			$response = $ac_helper->api_request( $body, $action_data );
			Automator()->complete->action( $user_id, $action_data, $recipe_id );
		} catch ( \Exception $e ) {
			$ac_helper->complete_with_errors( $user_id, $action_data, $recipe_id, $e->getMessage() );
		}
	}

	/**
	 * Get the fields.
	 */
	public function get_fields() {

		$custom_fields = get_transient( 'ua_ac_contact_fields_list' );

		$ac_helper = Automator()->helpers->recipe->active_campaign->options;

		if ( false === $custom_fields ) {
			$ac_helper->sync_contact_fields( false );
		}

		// Default ActiveCampaign fields.
		$fields = array(
			array(
				'option_code' => $this->get_action_meta(),
				/* translators: Email address */
				'label'       => esc_attr_x( 'Email address', 'ActiveCampaign', 'uncanny-automator' ),
				'input_type'  => 'email',
				'required'    => true,
			),
			array(
				'option_code' => $this->prefix . '_FIRST_NAME',
				/* translators: First name */
				'label'       => esc_attr_x( 'First name', 'ActiveCampaign', 'uncanny-automator' ),
				'input_type'  => 'text',
			),
			array(
				'option_code' => $this->prefix . '_LAST_NAME',
				/* translators: Last name */
				'label'       => esc_attr_x( 'Last name', 'ActiveCampaign', 'uncanny-automator' ),
				'input_type'  => 'text',
			),
			array(
				'option_code' => $this->prefix . '_PHONE',
				'label'       => esc_attr_x( 'Phone number', 'ActiveCampaign', 'uncanny-automator' ),
				'placeholder' => esc_attr_x( '(+00) 987 123 4567', 'ActiveCampaign', 'uncanny-automator' ),
				'input_type'  => 'text',
			),
		);

		// Add the custom fields options.
		$fields = array_merge( $fields, $ac_helper->get_custom_fields( $this->prefix ) );

		// Add the checkbox.
		$fields[] = array(
			'option_code' => $this->prefix . '_UPDATE_IF_CONTACT_EXISTS',
			'label'       => esc_attr_x( 'If the contact already exists, update their info.', 'ActiveCampaign', 'uncanny-automator' ),
			'input_type'  => 'checkbox',
			'description' => esc_html_x( 'To delete a value from a field, set its value to [delete], including the square brackets.', 'ActiveCampaign', 'uncanny-automator' ),
		);

		return $fields;
	}
}
