<?php

namespace Uncanny_Automator;

/**
 * Class WPJM_SUBMITJOB
 *
 * @package Uncanny_Automator
 */
class WPJM_SUBMITJOB {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'WPJM';

	private $trigger_code;
	private $trigger_meta;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		$this->trigger_code = 'WPJMSUBMITJOB';
		$this->trigger_meta = 'WPJMJOBTYPE';
		$this->define_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function define_trigger() {

		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/wp-job-manager/' ),
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			/* translators: Logged-in trigger - WP Job Manager */
			'sentence'            => sprintf( esc_html_x( 'A user submits a {{specific type of:%1$s}} job', 'WP Job Manager', 'uncanny-automator' ), $this->trigger_meta ),
			/* translators: Logged-in trigger - WP Job Manager */
			'select_option_name'  => esc_html_x( 'A user submits a {{specific type of}} job', 'WP Job Manager', 'uncanny-automator' ),
			'action'              => 'transition_post_status',
			'priority'            => 20,
			'accepted_args'       => 3,
			'validation_function' => array( $this, 'job_manager_job_submitted' ),
			'options_callback'    => array( $this, 'load_options' ),
		);

		Automator()->register->trigger( $trigger );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					Automator()->helpers->recipe->wp_job_manager->options->list_wpjm_job_types(),
				),
			)
		);
	}

	/**
	 *
	 */
	public function plugins_loaded() {
		$this->define_trigger();
	}

	/**
	 * @param $job_id
	 * @param $post
	 * @param $update
	 */
	public function job_manager_job_submitted( $new_status, $old_status, $post ) {

		if ( $new_status === $old_status ) {
			return;
		}

		if ( empty( $post ) ) {
			return;
		}

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$job_id = $post->ID;

		if ( 'job_listing' !== $post->post_type ) {
			return;
		}

		if ( ! $this->is_valid_job_status( $post->post_status ) ) {
			return;
		}

		$job_terms = wpjm_get_the_job_types( $job_id );

		$recipes    = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		$conditions = $this->match_condition( $job_terms, $recipes, $this->trigger_meta, $this->trigger_code );

		if ( empty( $conditions ) ) {
			return;
		}

		$user_id = $post->post_author;

		foreach ( $conditions['recipe_ids'] as $recipe_id ) {
			if ( ! Automator()->is_recipe_completed( $recipe_id, $user_id ) ) {
				$trigger_args = array(
					'code'            => $this->trigger_code,
					'meta'            => $this->trigger_meta,
					'recipe_to_match' => $recipe_id,
					'ignore_post_id'  => true,
					'user_id'         => $user_id,
				);

				$args = Automator()->maybe_add_trigger_entry( $trigger_args, false );

				if ( $args ) {
					foreach ( $args as $result ) {
						if ( true === $result['result'] ) {
							$trigger_meta = array(
								'user_id'        => $user_id,
								'trigger_id'     => $result['args']['trigger_id'],
								'trigger_log_id' => $result['args']['get_trigger_id'],
								'run_number'     => $result['args']['run_number'],
							);

							$trigger_meta['meta_key']   = $this->trigger_code;
							$trigger_meta['meta_value'] = $job_id;
							Automator()->insert_trigger_meta( $trigger_meta );
							Automator()->maybe_trigger_complete( $result['args'] );
							break;
						}
					}
				}
			}
		}
	}

	/**
	 * @param      $terms
	 * @param null $recipes
	 * @param null $trigger_meta
	 * @param null $trigger_code
	 *
	 * @return array|bool
	 */
	public function match_condition( $terms, $recipes = null, $trigger_meta = null, $trigger_code = null ) {

		if ( null === $recipes ) {
			return false;
		}

		$recipe_ids     = array();
		$entry_to_match = array();
		if ( empty( $terms ) ) {
			return false;
		}
		foreach ( $terms as $term ) {
			$entry_to_match[] = $term->term_id;
		}

		foreach ( $recipes as $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				if ( key_exists( $trigger_meta, $trigger['meta'] ) && ( in_array( (int) $trigger['meta'][ $trigger_meta ], $entry_to_match, true ) || intval( '-1' ) === intval( $trigger['meta'][ $trigger_meta ] ) ) ) {
					$recipe_ids[ $recipe['ID'] ] = $recipe['ID'];
					break;
				}
			}
		}

		if ( ! empty( $recipe_ids ) ) {
			return array(
				'recipe_ids' => $recipe_ids,
				'result'     => true,
			);
		}

		return false;
	}

	/**
	 * Validates if the job status is appropriate based on approval requirements.
	 *
	 * @param string $post_status The post status to validate.
	 * @return bool True if the status is valid for the current approval setting.
	 */
	private function is_valid_job_status( $post_status ) {
		$requires_approval = get_option( 'job_manager_submission_requires_approval', false );
		
		if ( $requires_approval ) {
			return 'pending' === $post_status;
		}
		
		return 'publish' === $post_status;
	}
}
