<?php

namespace Uncanny_Automator;

/**
 * Class ANON_WP_VIEWPOSTTYPE
 *
 * @package Uncanny_Automator
 */
class ANON_WP_VIEWPOSTTYPE {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'WP';

	private $trigger_code;
	private $trigger_meta;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		$this->trigger_code = 'WPVIEWPOSTTYPE';
		$this->trigger_meta = 'WPPOSTTYPES';
		if ( Automator()->helpers->recipe->is_edit_page() ) {
			add_action(
				'wp_loaded',
				function () {
					$this->define_trigger();
				},
				99
			);

			return;
		}
		$this->define_trigger();
	}

	/**
	 *
	 */
	public function plugins_loaded() {
		$this->define_trigger();
	}


	/**
	 * @throws \Exception
	 */
	public function define_trigger() {
		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/wordpress-core/' ),
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			/* translators: Logged-in trigger - WordPress */
			'sentence'            => sprintf( esc_attr__( 'A {{specific type of post:%1$s}} is viewed', 'uncanny-automator' ), $this->trigger_meta ),
			/* translators: Logged-in trigger - WordPress */
			'select_option_name'  => esc_attr__( 'A {{specific type of post}} is viewed', 'uncanny-automator' ),
			'action'              => 'template_redirect',
			'priority'            => 90,
			'accepted_args'       => 1,
			'type'                => 'anonymous',
			'validation_function' => array( $this, 'view_post_type' ),
			'options_callback'    => array( $this, 'load_options' ),
			// very last call in WP, we need to make sure they viewed the post and didn't skip before is was fully viewable
		);

		Automator()->register->trigger( $trigger );

		return;
	}

	/**
	 * load_options
	 *
	 * @return void
	 */
	public function load_options() {

		Automator()->helpers->recipe->wp->options->load_options = true;

		$options = array(
			'options' => array(
				Automator()->helpers->recipe->wp->options->all_wp_post_types(
					null,
					$this->trigger_meta,
					array( 'relevant_tokens' => array() )
				),
			),
		);

		$options = Automator()->utilities->keep_order_of_options( $options );

		return $options;
	}

	/**
	 *
	 */
	public function view_post_type() {
		global $post;
		if ( ! $post instanceof \WP_Post ) {
			return;
		}
		if ( ! is_singular( $post->post_type ) && ! is_post_type_viewable( $post->post_type ) ) {
			return;
		}
		$user_id            = get_current_user_id();
		$recipes            = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		$required_post_type = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );
		$matched_recipe_ids = array();

		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$trigger_id = $trigger['ID'];
				if ( ! isset( $required_post_type[ $recipe_id ] ) ) {
					continue;
				}
				if ( ! isset( $required_post_type[ $recipe_id ][ $trigger_id ] ) ) {
					continue;
				}

				//Add where option is set to Any post type
				if ( - 1 === intval( $required_post_type[ $recipe_id ][ $trigger_id ] )
					 || $required_post_type[ $recipe_id ][ $trigger_id ] === $post->post_type ) {
					$matched_recipe_ids[] = array(
						'recipe_id'  => $recipe_id,
						'trigger_id' => $trigger_id,
					);
					break;
				}
			}
		}

		if ( ! empty( $matched_recipe_ids ) ) {
			foreach ( $matched_recipe_ids as $matched_recipe_id ) {
				$pass_args = array(
					'code'             => $this->trigger_code,
					'meta'             => $this->trigger_meta,
					'user_id'          => $user_id,
					'recipe_to_match'  => $matched_recipe_id['recipe_id'],
					'trigger_to_match' => $matched_recipe_id['trigger_id'],
					'ignore_post_id'   => true,
				);

				$args = Automator()->process->user->maybe_add_trigger_entry( $pass_args, false );
				if ( $args ) {
					foreach ( $args as $result ) {
						if ( true === $result['result'] ) {
							$trigger_meta = array(
								'user_id'        => (int) $user_id,
								'trigger_id'     => $result['args']['trigger_id'],
								'trigger_log_id' => $result['args']['get_trigger_id'],
								'run_number'     => $result['args']['run_number'],
							);
							// post_id Token
							Automator()->db->token->save( 'post_id', $post->ID, $trigger_meta );

							do_action( 'automator_loopable_token_hydrate', $result['args'], array( $post->ID ) );

							Automator()->process->user->maybe_trigger_complete( $result['args'] );
						}
					}
				}
			}
		}
	}
}
