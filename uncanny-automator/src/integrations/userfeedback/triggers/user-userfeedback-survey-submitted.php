<?php

namespace Uncanny_Automator;

/**
 * Class Automator_UserFeedback_Trigger
 */
class USER_USERFEEDBACK_SURVEY_SUBMITTED {
	use Recipe\Triggers;

	/**
	 * Automator_UserFeedback_Trigger constructor.
	 */
	public function __construct() {
		$this->setup_trigger();
	}

	/**
	 * Setup integration
	 */
	protected function setup_trigger() {
		$this->set_integration( 'USERFEEDBACK' ); // Or UOA if you want to show it under Automator integration
		$this->set_trigger_code( 'USER_USERFEEDBACK_SURVEY_SUBMITTED' );
		$this->set_trigger_meta( 'UFSURVEY' );
		$this->set_is_login_required( false );
		// translators: 1: Survey
		$this->set_sentence( sprintf( esc_attr__( 'A user submits {{a survey:%1$s}}', 'uncanny-automator' ), $this->trigger_meta ) );
		$this->set_readable_sentence( esc_attr__( 'A user submits {{a survey}}', 'uncanny-automator' ) );
		// The action hook to attach this trigger into.
		$this->set_action_hook( 'userfeedback_survey_response' );
		$this->set_action_args_count( 3 );
		$this->set_trigger_tokens(
			array(
				array(
					'tokenId'         => 'USERFEEDBACK_SURVEY_ID',
					'tokenIdentifier' => $this->get_trigger_code(),
					'tokenName'       => esc_html__( 'Survey ID', 'uncanny-automator' ),
					'tokenType'       => 'text',
				),
				array(
					'tokenId'         => 'USERFEEDBACK_SURVEY_TITLE',
					'tokenIdentifier' => $this->get_trigger_code(),
					'tokenName'       => esc_html__( 'Survey title', 'uncanny-automator' ),
					'tokenType'       => 'text',
				),
				array(
					'tokenId'         => 'USERFEEDBACK_SURVEY_RESPONSE',
					'tokenIdentifier' => $this->get_trigger_code(),
					'tokenName'       => esc_html__( 'Survey response', 'uncanny-automator' ),
					'tokenType'       => 'text',
				),
				array(
					'tokenId'         => 'USERFEEDBACK_SURVEY_RESPONSE_JSON',
					'tokenIdentifier' => $this->get_trigger_code(),
					'tokenName'       => esc_html__( 'Survey response (JSON)', 'uncanny-automator' ),
					'tokenType'       => 'text',
				),
				array(
					'tokenId'         => 'USERFEEDBACK_SURVEY_USER_IP',
					'tokenIdentifier' => $this->get_trigger_code(),
					'tokenName'       => esc_html__( 'User IP address', 'uncanny-automator' ),
					'tokenType'       => 'text',
				),
				array(
					'tokenId'         => 'USERFEEDBACK_SURVEY_USER_BROWSER',
					'tokenIdentifier' => $this->get_trigger_code(),
					'tokenName'       => esc_html__( 'User browser', 'uncanny-automator' ),
					'tokenType'       => 'text',
				),
				array(
					'tokenId'         => 'USERFEEDBACK_SURVEY_USER_OS',
					'tokenIdentifier' => $this->get_trigger_code(),
					'tokenName'       => esc_html__( 'User OS', 'uncanny-automator' ),
					'tokenType'       => 'text',
				),
				array(
					'tokenId'         => 'USERFEEDBACK_SURVEY_USER_DEVICE',
					'tokenIdentifier' => $this->get_trigger_code(),
					'tokenName'       => esc_html__( 'User device', 'uncanny-automator' ),
					'tokenType'       => 'text',
				),
			)
		);
		$this->set_token_parser( array( $this, 'hydrate_tokens' ) );
		$this->set_options_callback( array( $this, 'load_options' ) ); // only load in Recipe UI instead of each page
		$this->register_trigger();
	}


	/**
	 * Populate the dropdown for the trigger options.
	 *
	 * @return array
	 */
	public function load_options() {
		global $wpdb;
		$surveys = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}userfeedback_surveys WHERE status = %s", 'publish' ) );
		$options = array( '-1' => esc_html( esc_html__( 'Any survey', 'uncanny-automator' ) ) );
		foreach ( $surveys as $survey ) {
			$options[ $survey->id ] = $survey->title;
		}

		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					Automator()->helpers->recipe->field->select(
						array(
							'input_type'            => 'select',
							'option_code'           => $this->trigger_meta,
							/* translators: HTTP request method */
							'label'                 => esc_html( esc_html__( 'Survey', 'uncanny-automator' ) ),
							'required'              => true,
							'supports_custom_value' => false,
							'options'               => $options,
						)
					),
				),
			)
		);
	}

	/**
	 * Save the token data for the trigger.
	 *
	 * @param $args
	 * @param $args
	 */
	public function save_token_data( $args, $trigger ) {
		global $wpdb;
		if ( isset( $args['trigger_args'] ) ) {
			$trigger_log_entry = $args['trigger_entry'];

			$survey_id       = $args['trigger_args'][0];
			$response_id     = $args['trigger_args'][1];
			$survey          = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}userfeedback_surveys WHERE id = %d", $survey_id ) );
			$survey_response = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}userfeedback_survey_responses WHERE id = %d", $response_id ) );

			// Build $survey_questions as an associative array of question ID => title
			$survey_questions = array();
			foreach ( json_decode( $survey->questions ) as $question ) {
				$survey_questions[ $question->id ] = $question->title;
			}

			// Map over the answers and build $response_answers
			$response_answers = array_map(
				function ( $answer ) use ( $survey_questions ) {
					$question = $survey_questions[ $answer->question_id ];
					$answer   = $answer->value;

					if ( is_array( $answer ) ) {
						$answer = join( ', ', $answer );
					}

					return array(
						'string' => "{$question}: {$answer}",
						'data'   => array( $question => $answer ),
					);
				},
				json_decode( $survey_response->answers )
			);

			$data   = array();
			$string = array();
			foreach ( $response_answers as $response_answer ) {
				$string[] = $response_answer['string'];
				$data[]   = json_encode( $response_answer['data'] );
			}

			$user_ip      = $survey_response->user_ip;
			$user_browser = $survey_response->user_browser;
			$user_os      = $survey_response->user_os;
			$user_device  = $survey_response->user_device;

			$token_values = array(
				'UFSURVEY'                          => isset( $survey->title ) ? $survey->title : '',
				'USERFEEDBACK_SURVEY_ID'            => $survey_id,
				'USERFEEDBACK_SURVEY_TITLE'         => $survey->title,
				'USERFEEDBACK_SURVEY_RESPONSE'      => join( ', ', $string ),
				'USERFEEDBACK_SURVEY_RESPONSE_JSON' => join( ', ', $data ),
				'USERFEEDBACK_SURVEY_USER_IP'       => $user_ip,
				'USERFEEDBACK_SURVEY_USER_BROWSER'  => $user_browser,
				'USERFEEDBACK_SURVEY_USER_OS'       => $user_os,
				'USERFEEDBACK_SURVEY_USER_DEVICE'   => $user_device,
			);

			foreach ( $token_values as $key => $value ) {
				Automator()->db->token->save( $key, $value, $trigger_log_entry );
			}
		}
	}


	/**
	 * Return saved token data for the trigger.
	 *
	 * @param $args
	 * @param $trigger
	 *
	 * @return string
	 */
	public function hydrate_tokens( $args, $trigger ) {
		if ( isset( $trigger['replace_args']['pieces'][2] ) ) {
			return Automator()->db->token->get( $trigger['replace_args']['pieces'][2], $trigger['args'] );
		}

		return '';
	}

	/**
	 * Run validation functions on the trigger.
	 *
	 * @return bool
	 */
	public function validate_trigger( ...$args ) {
		return true;
	}

	/**
	 * Prepare the trigger to run.
	 *
	 * @param mixed ...$args
	 */
	protected function prepare_to_run( ...$args ) {
		$this->set_conditional_trigger( true );
	}

	/**
	 * Validate trigger conditions.
	 *
	 * @param ...$args
	 *
	 * @return array
	 */
	public function validate_conditions( ...$args ) {
		list( $userfeedback_survey_id ) = $args[0];

		return $this->find_all( $this->trigger_recipes() )
					->where( array( $this->get_trigger_meta() ) )
					->match( array( $userfeedback_survey_id ) )
					->format( array( 'intval' ) )
					->get();
	}
}
