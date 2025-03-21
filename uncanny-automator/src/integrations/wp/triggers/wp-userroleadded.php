<?php

namespace Uncanny_Automator;

/**
 * Class WP_USERROLEADDED
 *
 * @package Uncanny_Automator
 */
class WP_USERROLEADDED {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'WP';

	/**
	 * @var string
	 */
	private $trigger_code;
	/**
	 * @var string
	 */
	private $trigger_meta;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		$this->trigger_code = 'USERROLEADDED';
		$this->trigger_meta = 'WPROLE';

		add_action(
			'admin_init',
			function () {
				if ( 'yes' === automator_get_option( 'USERROLEADDED_migrated', 'no' ) ) {
					return;
				}
				global $wpdb;
				$results = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s", 'code', $this->trigger_code ) );
				if ( ! empty( $results ) ) {
					foreach ( $results as $post_id ) {
						$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->postmeta SET meta_value = %s WHERE post_id = %d AND meta_key LIKE %s", 'add_user_role', $post_id, 'add_action' ) );
					}
				}
				automator_update_option( 'USERROLEADDED_migrated', 'yes' );
			},
			99
		);

		$this->define_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function define_trigger() {

		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/wordpress-core/' ),
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			'meta'                => $this->trigger_meta,
			/* translators: Logged-in trigger - WordPress Core */
			'sentence'            => sprintf( esc_html__( '{{A specific:%1$s}} role is added to the user', 'uncanny-automator' ), $this->trigger_meta ),
			/* translators: Logged-in trigger - WordPress Core */
			'select_option_name'  => esc_html__( '{{A specific}} role is added to the user', 'uncanny-automator' ),
			'action'              => 'add_user_role',
			'priority'            => 90,
			'accepted_args'       => 2,
			'validation_function' => array( $this, 'add_user_role' ),
			'options_callback'    => array( $this, 'load_options' ),
		);

		Automator()->register->trigger( $trigger );
	}

	/**
	 * load_options
	 *
	 * @return void
	 */
	public function load_options() {

		Automator()->helpers->recipe->wp->options->load_options = true;

		$options = Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					Automator()->helpers->recipe->wp->options->wp_user_roles(),
				),
			)
		);

		return $options;
	}

	/**
	 * @param $user_id
	 * @param $role
	 * @param $old_roles
	 */
	public function add_user_role( $user_id, $role ) {

		$recipes            = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		$required_user_role = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );

		if ( ! $recipes ) {
			return;
		}

		if ( ! $required_user_role ) {
			return;
		}

		$matched_recipe_ids = array();

		$user_obj = get_user_by( 'ID', (int) $user_id );

		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$trigger_id = $trigger['ID'];
				//Add where option is set to Any post type
				if ( intval( '-1' ) === intval( $required_user_role[ $recipe_id ][ $trigger_id ] ) ) {
					$matched_recipe_ids[] = array(
						'recipe_id'  => $recipe_id,
						'trigger_id' => $trigger_id,
					);
				}

				if ( user_can( $user_obj, $required_user_role[ $recipe_id ][ $trigger_id ] ) && (string) $role === (string) $required_user_role[ $recipe_id ][ $trigger_id ] ) {
					$matched_recipe_ids[] = array(
						'recipe_id'  => $recipe_id,
						'trigger_id' => $trigger_id,
					);
				}
			}
		}

		if ( ! empty( $matched_recipe_ids ) ) {
			foreach ( $matched_recipe_ids as $matched_recipe_id ) {
				$pass_args = array(
					'code'             => $this->trigger_code,
					'meta'             => $this->trigger_meta,
					'user_id'          => $user_obj->ID,
					'recipe_to_match'  => $matched_recipe_id['recipe_id'],
					'trigger_to_match' => $matched_recipe_id['trigger_id'],
					'ignore_post_id'   => true,
				);

				$results = Automator()->maybe_add_trigger_entry( $pass_args, false );
				if ( $results ) {
					foreach ( $results as $result ) {
						if ( true === $result['result'] ) {

							$trigger_meta = array(
								'user_id'        => $user_id,
								'trigger_id'     => $result['args']['trigger_id'],
								'trigger_log_id' => $result['args']['get_trigger_id'],
								'run_number'     => $result['args']['run_number'],
							);
							$roles        = array();
							foreach ( wp_roles()->roles as $role_name => $role_info ) {
								$roles[ $role_name ] = $role_info['name'];
							}
							$role_label = isset( $roles[ $role ] ) ? $roles[ $role ] : '';
							// Post Title Token
							$trigger_meta['meta_key']   = $result['args']['trigger_id'] . ':' . $this->trigger_code . ':WPROLE';
							$trigger_meta['meta_value'] = maybe_serialize( $role_label );
							Automator()->insert_trigger_meta( $trigger_meta );

							Automator()->maybe_trigger_complete( $result['args'] );
						}
					}
				}
			}
		}
	}

}
