<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
namespace Uncanny_Automator;

/**
 * Class FACEBOOK_PAGE_PUBLISH_POST
 *
 * @package Uncanny_Automator
 */
class FACEBOOK_PAGE_PUBLISH_POST {

	use Recipe\Actions;
	use Recipe\Action_Tokens;

	const AJAX_ENDPOINT = 'fb_pages_wp_ajax_endpoint_post_page';

	const ACTION_CODE = 'FACEBOOK_PAGE_PUBLISH_POST';

	const ACTION_META = 'FACEBOOK_PAGE_PUBLISH_POST_META';

	public function __construct() {

		add_action( 'wp_ajax_' . self::AJAX_ENDPOINT, array( $this, self::AJAX_ENDPOINT ) );

		$this->setup_action();

	}

	public function fb_pages_wp_ajax_endpoint_post_page() {

		$pages = Automator()->helpers->recipe->facebook->options->get_user_pages_from_options_table();

		wp_send_json( $pages );

	}

	/**
	 * Setup action.
	 *
	 * @return void.
	 */
	protected function setup_action() {

		$this->set_integration( 'FACEBOOK' );

		$this->set_action_code( self::ACTION_CODE );

		$this->set_action_meta( self::ACTION_META );

		$this->set_is_pro( false );

		$this->set_support_link( Automator()->get_author_support_link( $this->get_action_code(), 'knowledge-base/facebook/' ) );

		$this->set_requires_user( false );

		/* translators: Action - WordPress */
		$this->set_sentence( sprintf( esc_attr__( 'Publish a post to {{a Facebook page:%1$s}}', 'uncanny-automator' ), $this->get_action_meta() ) );

		/* translators: Action - WordPress */
		$this->set_readable_sentence( esc_attr__( 'Publish a post to {{a Facebook page}}', 'uncanny-automator' ) );

		$options = array(
			$this->get_action_meta() => array(
				array(
					'option_code'           => $this->get_action_meta(),
					'label'                 => esc_attr__( 'Facebook Page', 'uncanny-automator' ),
					'input_type'            => 'select',
					'is_ajax'               => true,
					'endpoint'              => self::AJAX_ENDPOINT,
					'supports_custom_value' => false,
					'required'              => true,
				),
				array(
					'option_code' => 'FACEBOOK_PAGE_MESSAGE',
					'input_type'  => 'textarea',
					'label'       => esc_attr__( 'Message', 'uncanny-automator' ),
					'description' => esc_attr__( 'Enter the message that you want to post on Facebook. Please take note that this action might fail when posting the same messages within short intervals.', 'uncanny-automator' ),
					'required'    => true,
				),
			),

		);

		$this->set_options_group( $options );

		$this->set_background_processing( true );

		// Disables wpautop.
		$this->set_wpautop( false );

		$this->set_action_tokens(
			array(
				'POST_LINK' => array(
					'name' => esc_html__( 'Link to Facebook post', 'uncanny-automator' ),
					'type' => 'url',
				),
			),
			$this->get_action_code()
		);

		$this->register_action();

	}


	/**
	 * Process the action.
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 * @param $parsed
	 *
	 * @return void.
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$facebook = Automator()->helpers->recipe->facebook->options;

		$page_id = sanitize_text_field( $parsed[ self::ACTION_META ] );

		// Post content editor adds BR tag if shift+enter. Enter key adds paragraph. Support both.
		$message = sanitize_textarea_field( $parsed['FACEBOOK_PAGE_MESSAGE'] );

		$body = array(
			'action'  => 'post-to-page',
			'message' => $message,
			'page_id' => $page_id,
		);

		try {

			$response = $facebook->api_request( $page_id, $body, $action_data );

			$post_id = isset( $response['data']['id'] ) ? $response['data']['id'] : 0;

			if ( 0 !== $post_id ) {
				$this->hydrate_tokens( array( 'POST_LINK' => 'https://www.facebook.com/' . $post_id ) );
			}

			Automator()->complete->action( $user_id, $action_data, $recipe_id );

		} catch ( \Exception $e ) {

			$action_data['complete_with_errors'] = true;

			// Log error if there are any error messages.
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $e->getMessage() );

		}
	}
}
