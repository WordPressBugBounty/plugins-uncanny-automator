<?php


namespace Uncanny_Automator;

use wpdb;

/**
 * Class Automator_DB_Handler_Actions
 *
 * @package Uncanny_Automator
 */
class Automator_DB_Handler_Actions {

	/**
	 * @var
	 */
	public static $instance;

	/**
	 * @return Automator_DB_Handler_Actions
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Returns WordPress instance of wpdb.
	 *
	 * @return wpdb
	 */
	public function wpdb() {
		global $wpdb;
		return $wpdb;
	}

	/**
	 * Retrieves the action log id from action id and recipe log id.
	 *
	 * @param int $action_id
	 * @param int $recipe_log_id
	 *
	 * @return int
	 */
	public function get_action_log_id( int $action_id, int $recipe_log_id ) {

		global $wpdb;

		$action_log_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->prefix}uap_action_log
				WHERE automator_action_id = %d
				AND automator_recipe_log_id = %d
				ORDER BY ID DESC",
				$action_id,
				$recipe_log_id
			)
		);

		return ! empty( $action_log_id ) ? absint( $action_log_id ) : 0;
	}

	/**
	 * @param int $action_log_id
	 *
	 * @return int|null
	 */
	public function get_action_log_completion_status( $action_log_id ) {

		$action_log_id = $this->wpdb()->get_var(
			$this->wpdb()->prepare(
				"SELECT completed FROM {$this->wpdb()->prefix}uap_action_log
				WHERE ID = %d",
				$action_log_id
			)
		);

		return $action_log_id ?? null;

	}

	/**
	 * @param $args
	 *
	 * @return bool|int
	 */
	public function add( $args ) {
		$user_id       = absint( $args['user_id'] );
		$action_id     = absint( $args['action_id'] );
		$recipe_id     = absint( $args['recipe_id'] );
		$recipe_log_id = absint( $args['recipe_log_id'] );
		$completed     = esc_attr( $args['completed'] );
		$error_message = isset( $args['error_message'] ) ? $args['error_message'] : '';
		if ( ! empty( $error_message ) ) {
			$error_message = wp_kses(
				$error_message,
				array(
					'a' => array(
						'href'   => array(),
						'title'  => array(),
						'target' => array(),
					),
					'br',
					array(
						'data'  => array(),
						'http'  => array(),
						'https' => array(),
					),
				)
			);
		}
		$date_time = $args['date_time'];

		global $wpdb;
		$table_name = $wpdb->prefix . Automator()->db->tables->action;

		$date_time = null !== $date_time ? $date_time : current_time( 'mysql' );

		$wpdb->insert(
			$table_name,
			array(
				'date_time'               => $date_time,
				'user_id'                 => $user_id,
				'automator_action_id'     => $action_id,
				'automator_recipe_id'     => $recipe_id,
				'automator_recipe_log_id' => $recipe_log_id,
				'completed'               => $completed,
				'error_message'           => $error_message,
			),
			array(
				'%s',
				'%d',
				'%d',
				'%d',
				'%d',
				'%d',
				'%s',
			)
		);

		return $wpdb->insert_id;
	}

	/**
	 * @param $data
	 * @param $where
	 *
	 * @return bool|int
	 */
	public function update( $data, $where ) {
		global $wpdb;
		$table_name = $wpdb->prefix . Automator()->db->tables->action;

		return $wpdb->update( $table_name, $data, $where );
	}

	/**
	 * @param $user_id
	 * @param $action_log_id
	 * @param $action_id
	 * @param $meta_key
	 * @param string|mixed $meta_value
	 *
	 * @return bool|int
	 */
	public function add_meta( $user_id, $action_log_id, $action_id, $meta_key, $meta_value ) {

		global $wpdb;

		$table_name = $wpdb->prefix . Automator()->db->tables->action_meta;

		$inserted = $wpdb->insert(
			$table_name,
			array(
				'user_id'                 => $user_id,
				'automator_action_log_id' => $action_log_id,
				'automator_action_id'     => $action_id,
				'meta_key'                => $meta_key,
				'meta_value'              => $meta_value,
			),
			array(
				'%d',
				'%d',
				'%d',
				'%s',
				'%s',
			)
		);

		return $inserted;
	}

	/**
	 * @param int $action_log_id
	 * @param int $action_id
	 * @param string $meta_key
	 * @param string $meta_value
	 *
	 * @return int|false
	 */
	public function update_meta( $action_log_id, $action_id, $meta_key, $meta_value ) {

		return $this->wpdb()->update(
			$this->wpdb()->prefix . Automator()->db->tables->action_meta,
			array(
				'meta_value' => $meta_value,
			),
			array(
				'meta_key'                => $meta_key,
				'automator_action_id'     => $action_id,
				'automator_action_log_id' => $action_log_id,
			),
			array(
				'%s',
			),
			array(
				'%s',
				'%d',
				'%d',
			)
		);

	}

	/**
	 * @param int $user_id
	 * @param int $action_log_id
	 * @param int $action_id
	 * @param string $meta_key
	 *
	 * @return bool|int
	 */
	public function delete_meta( $user_id, $action_log_id, $action_id, $meta_key ) {

		global $wpdb;

		$table_name = $wpdb->prefix . Automator()->db->tables->action_meta;

		return $wpdb->delete(
			$table_name,
			array(
				'user_id'                 => $user_id,
				'automator_action_log_id' => $action_log_id,
				'automator_action_id'     => $action_id,
				'meta_key'                => $meta_key,
			)
		);

	}

	/**
	 * @param $action_log_id
	 * @param $meta_key
	 *
	 * @return string
	 */
	public function get_meta( $action_log_id, $meta_key ) {

		global $wpdb;

		$table_name = $wpdb->prefix . Automator()->db->tables->action_meta;
		$result     = $wpdb->get_row( $wpdb->prepare( "SELECT meta_value FROM $table_name WHERE meta_key =%s AND automator_action_log_id =%d", $meta_key, $action_log_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $result ) {
			return $result;
		}

		return $result->meta_value;

	}

	/**
	 * Find all metas having the same action log id and meta key.
	 *
	 * @param int    $action_log_id The action log ID.
	 * @param string $meta_key      The meta key.
	 *
	 * @return array The array of meta values.
	 */
	public function get_multiple_meta( $action_log_id, $meta_key ) {

		global $wpdb;

		$table_name = $wpdb->prefix . Automator()->db->tables->action_meta;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_value FROM $table_name WHERE meta_key = %s AND automator_action_log_id = %d",
				$meta_key,
				$action_log_id
			),
			ARRAY_A
		);

		return (array) $results;
	}


	/**
	 * @param $action_log_id
	 * @param $meta_key
	 *
	 * @return string
	 */
	public function get_meta_by_id( $meta_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . Automator()->db->tables->action_meta;
		$result     = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE ID =%s", $meta_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $result ) {
			return $result;
		}

		return $result->meta_value;
	}

	/**
	 * @param $action_id
	 * @param $recipe_log_id
	 * @param $completed
	 * @param $error_message
	 */
	public function mark_complete( $action_id, $recipe_log_id, $completed = 1, $error_message = '' ) {
		$data = array(
			'completed'     => $completed,
			'date_time'     => current_time( 'mysql' ),
			'error_message' => $error_message,
		);

		$where = array(
			'automator_action_id'     => $action_id,
			'automator_recipe_log_id' => $recipe_log_id,
		);

		Automator()->db->action->update( $data, $where );

	}

	/**
	 * @param $recipe_log_id
	 *
	 * @return array|object|void|null
	 */
	public function get_error_message( $recipe_log_id ) {
		global $wpdb;
		$tbl = Automator()->db->tables->action;

		return $wpdb->get_row( $wpdb->prepare( "SELECT error_message, completed FROM {$wpdb->prefix}{$tbl} WHERE error_message != '' AND automator_recipe_log_id =%d", $recipe_log_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * @param $action_id
	 */
	public function delete( $action_id ) {
		global $wpdb;

		// delete from uap_action_log
		$wpdb->delete(
			$wpdb->prefix . Automator()->db->tables->action,
			array( 'automator_action_id' => $action_id )
		);

		// delete from uap_action_log_meta
		$wpdb->delete(
			$wpdb->prefix . Automator()->db->tables->action_meta,
			array( 'automator_action_id' => $action_id )
		);
	}

	/**
	 * @param $recipe_id
	 * @param $automator_recipe_log_id
	 */
	public function delete_logs( $recipe_id, $automator_recipe_log_id ) {
		global $wpdb;
		$action_tbl      = $wpdb->prefix . Automator()->db->tables->action;
		$action_meta_tbl = $wpdb->prefix . Automator()->db->tables->action_meta;
		$actions         = $wpdb->get_col( $wpdb->prepare( "SELECT `ID` FROM $action_tbl WHERE automator_recipe_id=%d AND automator_recipe_log_id=%d", $recipe_id, $automator_recipe_log_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $actions ) {
			foreach ( $actions as $automator_action_log_id ) {
				// delete from uap_action_log_meta
				$wpdb->delete(
					$action_meta_tbl,
					array( 'automator_action_log_id' => $automator_action_log_id )
				);
			}
		}

		// delete from uap_action_log
		$wpdb->delete(
			$action_tbl,
			array(
				'automator_recipe_id'     => $recipe_id,
				'automator_recipe_log_id' => $automator_recipe_log_id,
			)
		);
	}

	/**
	 * update_menu_order
	 *
	 * @param int $action_id
	 * @param int $order
	 *
	 * @return void
	 */
	public function update_menu_order( $action_id, $order ) {
		wp_update_post(
			array(
				'ID'         => $action_id,
				'menu_order' => $order,
			)
		);
	}

	/**
	 * @param $recipe_id
	 *
	 * @return void
	 */
	public function delete_by_recipe_id( $recipe_id ) {
		global $wpdb;
		$action_tbl      = $wpdb->prefix . Automator()->db->tables->action;
		$action_meta_tbl = $wpdb->prefix . Automator()->db->tables->action_meta;
		$actions         = $wpdb->get_col( $wpdb->prepare( "SELECT `ID` FROM $action_tbl WHERE automator_recipe_id=%d", $recipe_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $actions ) {
			foreach ( $actions as $automator_action_log_id ) {
				// delete from uap_action_log_meta
				$wpdb->delete(
					$action_meta_tbl,
					array( 'automator_action_log_id' => $automator_action_log_id )
				);
			}
		}

		// delete from uap_action_log
		$wpdb->delete(
			$action_tbl,
			array(
				'automator_recipe_id' => $recipe_id,
			)
		);
	}

	/**
	 * Get specific action log entry from uap_action_log table.
	 *
	 * @param int $recipe_id
	 * @param int $recipe_log_id
	 * @param int $action_id
	 * @param int $user_id
	 *
	 * @return mixed[]
	 */
	public function get_log( $recipe_id, $recipe_log_id, $action_id, $user_id ) {

		global $wpdb;

		$action_log_result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT *
					FROM
					{$wpdb->prefix}uap_action_log
					WHERE automator_recipe_id = %d
					AND automator_recipe_log_id = %d
					AND automator_action_id = %d
					AND user_id = %d
				",
				$recipe_id,
				$recipe_log_id,
				$action_id,
				$user_id
			),
			ARRAY_A
		);

		if ( empty( $action_log_result ) ) {
			return array();
		}

		return $action_log_result;

	}

}
