<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
namespace Uncanny_Automator;

/**
 * Class FACEBOOK_GROUP_PUBLISH_PHOTO
 *
 * @package Uncanny_Automator
 */
class FACEBOOK_GROUP_PUBLISH_PHOTO {

	use Recipe\Actions;

	use Recipe\Action_Tokens;

	const INTEGRATION = 'FACEBOOK_GROUPS';

	const CODE = 'FACEBOOK_GROUPS_PUBLISH_PHOTO';

	const META = 'FACEBOOK_GROUPS_PUBLISH_PHOTO_META';

	public function __construct() {

		$this->setup_action();

	}

	/**
	 * Setups our action.
	 *
	 * @return void
	 */
	public function setup_action() {

		$this->set_is_deprecated( true );

		$this->set_action_code( self::CODE );

		$this->set_action_meta( self::META );

		$this->set_integration( self::INTEGRATION );

		$this->set_is_pro( false );

		$this->set_support_link( Automator()->get_author_support_link( $this->get_action_code(), 'knowledge-base/facebook-groups' ) );

		$this->set_requires_user( false );

		$this->set_sentence(
			sprintf(
				/* translators:The action sentence */
				esc_attr__( 'Publish a post with an image to {{a Facebook group:%1$s}}', 'uncanny-automator' ),
				$this->get_action_meta()
			)
		);

		/* translators: Action - WordPress */
		$this->set_readable_sentence( esc_attr__( 'Publish a post with an image to {{a Facebook group}}', 'uncanny-automator' ) );

		$this->set_options_group( $this->get_options_group() );

		$this->set_buttons(
			Automator()->helpers->recipe->facebook_groups->options->buttons(
				$this->get_action_meta(),
				automator_utm_parameters( $this->get_support_link(), 'facebook-group_publish_post', 'help_button' )
			)
		);

		$this->set_background_processing( true );

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
	 * Returns the list of options for our action.
	 *
	 * @return array The fields.
	 */
	public function get_options_group() {

		$facebook_groups = Automator()->helpers->recipe->facebook_groups->options;

		return array(
			$this->get_action_meta() => array(
				Automator()->helpers->recipe->facebook_groups->options->get_groups_field( $this->get_action_meta() ),
				array(
					'option_code' => 'FACEBOOK_GROUPS_PUBLISH_PHOTO_IMAGE_URL',
					/* translators: Email field */
					'label'       => esc_attr__( 'Image URL', 'uncanny-automator' ),
					'placeholder' => esc_attr__( 'https://examplewebsite.com/path/to/image.jpg', 'uncanny-automator' ),
					'input_type'  => 'text',
					'required'    => true,
					'description' => esc_attr__( 'Enter the URL of the image you wish to share. The URL must be publicly accessible.', 'uncanny-automator' ),
				),
				array(
					'option_code' => 'FACEBOOK_GROUPS_PUBLISH_MESSAGE',
					/* translators: Email field */
					'label'       => esc_attr__( 'Message', 'uncanny-automator' ),
					'placeholder' => esc_attr__( 'The context of the image or description.', 'uncanny-automator' ),
					'input_type'  => 'textarea',
				),
			),
		);

	}

	/**
	 * Proccess our action.
	 *
	 * @return void.
	 */
	public function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$helper = Automator()->helpers->recipe->facebook_groups->options;
		// The group id.
		$group_id = isset( $parsed[ $this->get_action_meta() ] ) ? sanitize_text_field( $parsed[ $this->get_action_meta() ] ) : 0;
		// The caption.
		$caption = isset( $parsed['FACEBOOK_GROUPS_PUBLISH_MESSAGE'] ) ? sanitize_textarea_field( $parsed['FACEBOOK_GROUPS_PUBLISH_MESSAGE'] ) : '';
		// The image url.
		$media = isset( $parsed['FACEBOOK_GROUPS_PUBLISH_PHOTO_IMAGE_URL'] ) ? sanitize_text_field( $parsed['FACEBOOK_GROUPS_PUBLISH_PHOTO_IMAGE_URL'] ) : '';

		$body = array(
			'action'       => 'send_photo',
			'access_token' => $helper->get_user_access_token(),
			'caption'      => $caption,
			'group_id'     => $group_id,
			'url'          => $this->resolve_image_url( $media ),
		);

		try {

			$response = $helper->api_request( $body, $action_data );

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

	/**
	 * Returns the image url of the media if numeric is provided. Otherwise, the url.
	 *
	 * @param string $media
	 * @return string|false
	 */
	private function resolve_image_url( $media = '' ) {
		return is_numeric( $media ) ? wp_get_attachment_url( $media ) : $media;
	}

}
