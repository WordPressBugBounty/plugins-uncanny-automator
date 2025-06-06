<?php

namespace Uncanny_Automator;

/**
 * Class Automator_DB_Handler
 *
 * @since 3.0
 * @package Uncanny_Automator
 */
class Automator_DB_Handler {
	/**
	 * @var
	 */
	public static $instance;
	/**
	 * @var object
	 */
	public $tables;
	/**
	 * @var Automator_DB_Handler_Recipes
	 */
	public $recipe;
	/**
	 * @var Automator_DB_Handler_Triggers
	 */
	public $trigger;
	/**
	 * @var Automator_DB_Handler_Actions
	 */
	public $action;
	/**
	 * @var Automator_DB_Handler_Closures
	 */
	public $closure;
	/**
	 * @var Automator_DB_Handler_Tokens
	 */
	public $token;
	/**
	 * @var Automator_DB_Handler_Api
	 */
	public $api;

	/**
	 * Automator_DB_Handler constructor.
	 */
	public function __construct() {

		$this->tables  = DB_Tables::get_automator_tables();
		$this->recipe  = Automator_DB_Handler_Recipes::get_instance();
		$this->token   = Automator_DB_Handler_Tokens::get_instance();
		$this->trigger = Automator_DB_Handler_Triggers::get_instance();
		$this->action  = Automator_DB_Handler_Actions::get_instance();
		$this->closure = Automator_DB_Handler_Closures::get_instance();
		$this->api     = Automator_DB_Handler_Api::get_instance();
	}

	/**
	 * @return Automator_DB_Handler
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @param $table
	 * @param $update
	 * @param $where
	 * @param $where_format
	 * @param $update_format
	 *
	 * @return bool|int
	 */
	public function update( $table, $update, $where, $where_format, $update_format ) {
		global $wpdb;

		return $wpdb->update(
			$table,
			$update,
			$where,
			$where_format,
			$update_format
		);
	}
}
