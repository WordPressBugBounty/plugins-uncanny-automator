<?php

namespace Uncanny_Automator;

/**
 * Class Hubspot_Helpers
 *
 * @package Uncanny_Automator
 */
class Hubspot_Helpers {

	/**
	 * The API endpoint address.
	 *
	 * @var API_ENDPOINT The endpoint adress.
	 */
	const API_ENDPOINT = 'v2/hubspot';

	/**
	 * @var Hubspot_Helpers
	 */
	public $options;

	/**
	 * @var Hubspot_Helpers
	 */
	public $setting_tab;

	/**
	 * @var bool
	 */
	public $load_options = true;

	/**
	 * @var string
	 */
	public $tab_url;

	/**
	 * @var string
	 */
	public $automator_api;

	/**
	 * Hubspot_Helpers constructor.
	 */
	public function __construct() {

		$this->automator_api = AUTOMATOR_API_URL . 'v2/hubspot';

		add_action( 'init', array( $this, 'capture_oauth_tokens' ), AUTOMATOR_APP_INTEGRATIONS_PRIORITY, 3 );
		add_action( 'init', array( $this, 'disconnect' ), AUTOMATOR_APP_INTEGRATIONS_PRIORITY, 3 );

		$this->load_settings();
	}
	/**
	 * Load settings.
	 */
	public function load_settings() {
		$this->setting_tab = 'hubspot-api';
		$this->tab_url     = admin_url( 'edit.php' ) . '?post_type=uo-recipe&page=uncanny-automator-config&tab=premium-integrations&integration=' . $this->setting_tab;
		include_once __DIR__ . '/../settings/settings-hubspot.php';
		new Hubspot_Settings( $this );
	}

	/**
	 * @param Hubspot_Helpers $options
	 */
	public function setOptions( Hubspot_Helpers $options ) { // phpcs:ignore
		$this->options = $options;
	}

	/**
	 *
	 * @return array $tokens
	 */
	public function get_client() {

		$tokens = automator_get_option( '_automator_hubspot_settings', array() );

		if ( empty( $tokens['access_token'] ) || empty( $tokens['refresh_token'] ) ) {
			throw new \Exception( esc_html_x( 'HubSpot is not connected', 'Hubspot', 'uncanny-automator' ) );
		}

		$tokens = $this->maybe_refresh_token( $tokens );

		return $tokens;
	}

	/**
	 * store_client
	 *
	 * @param  mixed $tokens
	 * @return void
	 */
	public function store_client( $tokens ) {

		$tokens['stored_at'] = time();

		automator_update_option( '_automator_hubspot_settings', $tokens );

		delete_transient( '_automator_hubspot_token_info' );

		return $tokens;
	}

	/**
	 * Capture tokens returned by Automator API.
	 *
	 * @return mixed
	 */
	public function capture_oauth_tokens() {

		if ( automator_filter_input( 'integration' ) !== $this->setting_tab ) {
			return;
		}

		$automator_message = automator_filter_input( 'automator_api_message' );

		if ( empty( $automator_message ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tokens = (array) Automator_Helpers_Recipe::automator_api_decode_message( $automator_message, wp_create_nonce( 'automator_hubspot_api_authentication' ) );

		$redirect_url = $this->tab_url;

		if ( $tokens ) {
			$this->store_client( $tokens );
			$redirect_url .= '&connect=1';
		} else {
			$redirect_url .= '&connect=2';
		}

		wp_safe_redirect( $redirect_url );

		die;
	}

	/**
	 * disconnect
	 *
	 * @return void
	 */
	public function disconnect() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( automator_filter_input( 'integration' ) !== $this->setting_tab ) {
			return;
		}

		if ( ! automator_filter_has_var( 'disconnect' ) ) {
			return;
		}

		delete_transient( '_automator_hubspot_token_info' );
		automator_delete_option( '_automator_hubspot_settings' );

		$redirect_url = $this->tab_url;

		wp_safe_redirect( $redirect_url );

		die;
	}

	/**
	 * maybe_refresh_token
	 *
	 * @param  mixed $tokens
	 * @return void
	 */
	public function maybe_refresh_token( $tokens ) {

		$expiration_timestamp = $tokens['stored_at'] + $tokens['expires_in'];

		// Check if token will expire in the next minute
		if ( time() > $expiration_timestamp - MINUTE_IN_SECONDS ) {
			// Token is expired or will expire soon, refresh it
			return $this->api_refresh_token( $tokens );
		}

		return $tokens;
	}

	/**
	 * api_refresh_token
	 *
	 * @param  mixed $tokens
	 * @return void
	 */
	public function api_refresh_token( $tokens ) {

		$params = array(
			'endpoint' => self::API_ENDPOINT,
			'body'     => array(
				'action' => 'refresh_token',
				'client' => wp_json_encode( $tokens ),
			),
		);

		$last_call = automator_get_option( '_automator_hubspot_last_refresh_token_call', 0 );

		// Rate limit token refresh calls if they fail
		if ( time() - $last_call < 60 ) {
			throw new \Exception( esc_html_x( 'HubSpot token refresh timeout, please try to reconnect HubSpot from settings', 'Hubspot', 'uncanny-automator' ) );
		}

		$response = Api_Server::api_call( $params );

		if ( empty( $response['data']['access_token'] ) ) {

			$failed_attempt = automator_get_option( '_automator_hubspot_refresh_token_failed_attempts', 0 ) + 1;

			if ( $failed_attempt > 10 ) {
				// Something is wrong with the token. Disconnect HubSpot after 10 attempts.
				automator_delete_option( '_automator_hubspot_settings' );
				automator_delete_option( '_automator_hubspot_refresh_token_attempts' );
				automator_delete_option( '_automator_hubspot_last_refresh_token_call' );
				throw new \Exception( esc_html_x( 'HubSpot token refresh failed, please reconnect HubSpot from settings', 'Hubspot', 'uncanny-automator' ) );
			}

			automator_update_option( '_automator_hubspot_refresh_token_failed_attempts', $failed_attempt );
			automator_update_option( '_automator_hubspot_last_refresh_token_call', time() );

			$error_msg = esc_html_x( 'Could not refresh HubSpot token.', 'Hubspot', 'uncanny-automator' );

			if ( ! empty( $response['data']['message'] ) ) {
				$error_msg = $response['data']['message'];
			}

			throw new \Exception( esc_html( $error_msg ), absint( $response['statusCode'] ) );
		}

		automator_delete_option( '_automator_hubspot_refresh_token_failed_attempts' );

		$tokens = $this->store_client( $response['data'] );

		return $tokens;
	}

	/**
	 * api_token_info
	 *
	 * @return void
	 */
	public function api_token_info() {

		$token_info = get_transient( '_automator_hubspot_token_info' );

		if ( ! $token_info ) {

			$params = array(
				'action' => 'access_token_info',
			);

			try {

				$response = $this->api_request( $params );

				$token_info = $response['data'];

				set_transient( '_automator_hubspot_token_info', $token_info, DAY_IN_SECONDS );

			} catch ( \Exception $e ) {
				$token_info = false;
			}
		}

		return $token_info;
	}

	/**
	 * create_contact
	 *
	 * @param  mixed $email
	 * @return void
	 */
	public function create_contact( $properties, $update = true, $action_data = null ) {

		$action = 'create_contact';

		if ( $update ) {
			$action = 'create_or_update_contact';
		}

		$params = array(
			'action'     => $action,
			'properties' => wp_json_encode( $properties ),
		);

		$response = $this->api_request( $params, $action_data );

		return $response;
	}

	/**
	 * Method extract_error
	 *
	 * @param $response
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 *
	 * @return void
	 */
	public function check_for_errors( $response ) {

		if ( isset( $response['data']['status'] ) && 'error' === $response['data']['status'] ) {

			$message = $this->extract_error_message( $response );

			throw new \Exception( esc_html( $message ) );
		}

		if ( 200 !== intval( $response['statusCode'] ) ) {
			throw new \Exception(
				sprintf(
					/* translators: %s: API status code */
					esc_html_x( 'API returned an error: %s', 'HubSpot', 'uncanny-automator' ),
					absint( $response['statusCode'] )
				),
				absint( $response['statusCode'] )
			);
		}
	}

	/**
	 * extract_error_message
	 *
	 * @param  array $response
	 * @return string
	 */
	public function extract_error_message( $response ) {

		$message = esc_html_x( 'API returned an error:', 'Hubspot', 'uncanny-automator' ) . $response['statusCode'];

		if ( ! empty( $response['data']['message'] ) ) {
			$message = $response['data']['message'] . '<br>';
		}

		if ( ! empty( $response['data']['validationResults'] ) ) {

			foreach ( $response['data']['validationResults'] as $result ) {

				if ( ! empty( $result['error'] ) ) {
					$message .= '(<strong>' . $result['error'] . '</strong>)';
				}

				if ( ! empty( $result['name'] ) ) {
					$message .= ' Field: ' . $result['name'];
				}

				if ( ! empty( $result['message'] ) ) {
					$message .= '<br>' . $result['message'] . ')';
				}
			}
		}

		return $message;
	}

	/**
	 * Method log_action_error
	 *
	 * @param $response
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 *
	 * @return void
	 */
	public function log_action_error( $error, $user_id, $action_data, $recipe_id ) {
		$action_data['complete_with_errors'] = true;
		Automator()->complete_action( $user_id, $action_data, $recipe_id, $error );
	}

	/**
	 * Method api_request
	 *
	 * @param $params
	 *
	 * @return void
	 */
	public function api_request( $body, $action_data = null, $timeout = null ) {

		$body = apply_filters( 'automator_hubspot_api_request_params', $body );

		$client = $this->get_client();

		$body['client'] = $client;

		$params = array(
			'endpoint' => self::API_ENDPOINT,
			'body'     => $body,
			'action'   => $action_data,
		);

		if ( null !== $timeout ) {
			$params['timeout'] = $timeout;
		}

		$response = Api_Server::api_call( $params );

		$response = apply_filters( 'automator_hubspot_api_response', $response );

		$this->check_for_errors( $response );

		return $response;
	}

	/**
	 * get_fields
	 *
	 * @return void
	 */
	public function get_fields( $exclude = array() ) {

		$fields = array();

		$request_params = array(
			'action' => 'get_fields',
		);

		try {
			$response = $this->api_request( $request_params );

			$fields[] = array(
				'value' => '',
				'text'  => esc_html_x( 'Select a field', 'Hubspot', 'uncanny-automator' ),
			);

			foreach ( $response['data'] as $field ) {

				if ( in_array( $field['name'], $exclude, true ) ) {
					continue;
				}

				if ( $field['readOnlyValue'] ) {
					continue;
				}

				$fields[] = array(
					'value' => $field['name'],
					'text'  => $field['label'],
				);
			}
		} catch ( \Exception $e ) {
			$fields[] = array(
				'value' => '',
				'text'  => $e->getMessage(),
			);
		}

		return $fields;
	}

	/**
	 * get_lists
	 *
	 * @return void
	 */
	public function get_lists() {

		$options = array();

		$params = array(
			'action' => 'get_lists',
		);

		try {
			$response = $this->api_request( $params );

			$options[] = array(
				'value' => '',
				'text'  => esc_html_x( 'Select a list', 'Hubspot', 'uncanny-automator' ),
			);

			foreach ( $response['data']['lists'] as $list ) {

				if ( 'STATIC' !== $list['listType'] ) {
					continue;
				}

				$options[] = array(
					'value' => $list['listId'],
					'text'  => $list['name'],
				);
			}
		} catch ( \Exception $e ) {
			$options[] = array(
				'value' => '',
				'text'  => $e->getMessage(),
			);
		}

		return apply_filters( 'automator_hubspot_options_get_lists', $options );
	}

	/**
	 * add_contact_to_list
	 *
	 * @param  mixed $list_id
	 * @param  mixed $email
	 * @return void
	 */
	public function add_contact_to_list( $list_id, $email, $action_data ) {

		if ( empty( $email ) ) {
			throw new \Exception( esc_html_x( 'Email is missing', 'Hubspot', 'uncanny-automator' ) );
		}

		if ( empty( $list_id ) ) {
			throw new \Exception( esc_html_x( 'List is missing', 'Hubspot', 'uncanny-automator' ) );
		}

		$params = array(
			'action' => 'add_contact_to_list',
			'email'  => $email,
			'list'   => $list_id,
		);

		$response = $this->api_request( $params, $action_data );

		// If the email was already in the list
		if ( ! empty( $response['data']['discarded'] ) ) {
			throw new \Exception( esc_html_x( 'Contact with such email address was already in the list', 'Hubspot', 'uncanny-automator' ) );
		}

		// If the email was not found in contacts
		if ( ! empty( $response['data']['invalidEmails'] ) ) {
			throw new \Exception( esc_html_x( 'Contact with such email address was not found', 'Hubspot', 'uncanny-automator' ) );
		}

		return $response;
	}

	/**
	 * remove_contact_from_list
	 *
	 * @param  mixed $list_id
	 * @param  mixed $email
	 * @return void
	 */
	public function remove_contact_from_list( $list_id, $email, $action_data ) {

		if ( empty( $email ) ) {
			throw new \Exception( esc_html_x( 'Email is missing', 'Hubspot', 'uncanny-automator' ) );
		}

		if ( empty( $list_id ) ) {
			throw new \Exception( esc_html_x( 'List is missing', 'Hubspot', 'uncanny-automator' ) );
		}

		$params = array(
			'action' => 'remove_contact_from_list',
			'email'  => $email,
			'list'   => $list_id,
		);

		$response = $this->api_request( $params, $action_data );

		// If the email was not found in contacts
		if ( ! empty( $response['data']['discarded'] ) ) {
			throw new \Exception( esc_html_x( 'Contact with such email address was not found in the list', 'Hubspot', 'uncanny-automator' ) );
		}

		return $response;
	}
	/**
	 * Disconnect url.
	 *
	 * @return mixed
	 */
	public function disconnect_url() {
		return $this->tab_url . '&disconnect=1';
	}
	/**
	 * Connect url.
	 *
	 * @return mixed
	 */
	public function connect_url() {

		$nonce      = wp_create_nonce( 'automator_hubspot_api_authentication' );
		$plugin_ver = AUTOMATOR_PLUGIN_VERSION;
		$api_ver    = '1.0';

		$action       = 'authorization_request';
		$redirect_url = rawurlencode( $this->tab_url );
		$url          = $this->automator_api . "?action={$action}&redirect_url={$redirect_url}&nonce={$nonce}&api_ver={$api_ver}&plugin_ver={$plugin_ver}";

		return $url;
	}
}
