<?php
namespace Uncanny_Automator\Rest\Endpoint\Log_Endpoint\Utils;

use Exception;
use Uncanny_Automator\Automator_Status;

class Formatters_Utils {

	/**
	 * Helper method to handle a falsy variable.
	 *
	 * @param mixed $mixed The variable to handle.
	 * @param string $fallback The fallback value when $mixed is falsy.
	 *
	 * @return mixed
	 */
	public static function handle_var( $mixed = null, $fallback = '' ) {

		return empty( $mixed ) ? $fallback : $mixed;

	}

	/**
	 * Flattens the array from post meta.
	 *
	 * @param mixed[] $meta The result from get_post_meta with only key given as parameter.
	 *
	 * @return array<string>
	 */
	public static function flatten_post_meta( $meta = array() ) {
		return array_map(
			function( $item ) {
				if ( is_array( $item ) && isset( $item[0] ) ) {
					return (string) $item[0];
				}
				return '';
			},
			$meta
		);
	}

	/**
	 * Flattens the array from the action log meta.
	 *
	 * @param mixed[] $log_meta The 'metas' from the table uap_action_log_meta.
	 *
	 * @return array<string>
	 */
	public function flatten_action_log_meta( $log_meta = array() ) {
		$meta_items = array();
		foreach ( (array) $log_meta as $meta ) {
			$meta_items[ $meta->meta_key ] = $meta->meta_value;
		}
		return $meta_items;
	}

	/**
	 * Convert a specific datetime string into its timestamp format, with respect to time zone.
	 *
	 * @param  string $date_string
	 * @return int|false The timestamp. False if date object is not a valid object.
	 */
	public static function strtotime( $date_string = '' ) {

		$ts_string = Automator()->get_timezone_string();

		if ( empty( $ts_string ) ) {
			return false;
		}

		try {
			if ( empty( $date_string ) ) {
				throw new Exception( 'Invalid date string', 500 );
			}
			$date = new \DateTime( $date_string, new \DateTimeZone( Automator()->get_timezone_string() ) );
			if ( false !== $date ) {
				return intval( $date->format( 'U' ) );
			}
			/** @phpstan-ignore-next-line */
			return false;
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Formats the given date string into a valid format.
	 *
	 * @param string $date_string Any valid date.
	 * @param string $format Any valid date format.
	 *
	 * @return false|null|string
	 */
	public static function date_time_format( $date_string = '', $format = 'Y-m-d @ H:i:s' ) {

		// Handle default mysql date.
		if ( strtotime( '0000-00-00 00:00:00' ) === strtotime( $date_string ) ) {
			return null;
		}

		$wp_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

		$dt = \DateTime::createFromFormat( 'Y-m-d H:i:s', trim( $date_string ), new \DateTimeZone( Automator()->get_timezone_string() ) );

		if ( false === $dt ) {
			return false;
		}

		return $dt->format( $wp_format );

	}

	/**
	 * Wrapper function for WordPress' human_time_diff function.
	 *
	 * @param string $from - A valid datetime string from which the difference begins.
	 * @param string $to -  A valid datetime string to end the time difference.
	 *
	 * @return string|false - Human time difference. Otherwise, false.
	 */
	public static function human_time_diff( $start, $to ) {

		if ( empty( $start ) || empty( $to ) ) {
			return '';
		}

		return human_time_diff( strtotime( $start ), strtotime( $to ) );

	}

	/**
	 * Returns a 'Y-m-d H:i:s' formatted timestamp.
	 *
	 * @param $timestamp int The timestamp
	 *
	 * @return string
	 */
	public static function unix_timestamp_to_date( $timestamp ) {

		$datetime_format = 'Y-m-d H:i:s';

		$date = new \DateTime( 'now', new \DateTimeZone( Automator()->get_timezone_string() ) );
		$date->setTimestamp( $timestamp );

		$ds = $date->format( $datetime_format );

		return $ds;

	}

	/**
	 * Accepts status code and return its corresponding class name.
	 *
	 * @param Automator_Status $automator_status The class for handling common logs status.
	 * @param int $status_code
	 *
	 * @return string The status class name.
	 */
	public static function status_class_name( Automator_Status $automator_status, $status_code = 0 ) {

		$class_name = $automator_status::get_class_name( $status_code );

		// Not completed status in Triggers are shown as in-progress.
		if ( $automator_status::get_class_name( $automator_status::NOT_COMPLETED ) === $class_name ) {
			$class_name = $automator_status::get_class_name( $automator_status::IN_PROGRESS );
		}

		return (string) $class_name;

	}

	/**
	 * Given the integer time. Return its time units value.
	 *
	 * @param int $time
	 *
	 * @return string[]
	 */
	public static function time_units( $time = 0 ) {
		return array(
			/* translators: Units of time (seconds) */
			'seconds' => _n( '%1$s second', '%1$s seconds', $time, 'uncanny-automator' ),
			/* translators: Units of time (minutes)*/
			'minutes' => _n( '%1$s minute', '%1$s minutes', $time, 'uncanny-automator' ),
			/* translators: Units of time (hours)*/
			'hours'   => _n( '%1$s hour', '%1$s hours', $time, 'uncanny-automator' ),
			/* translators: Units of time (days)*/
			'days'    => _n( '%1$s day', '%1$s days', $time, 'uncanny-automator' ),
			/* translators: Units of time (years)*/
			'years'   => _n( '%1$s year', '%1$s years', $time, 'uncanny-automator' ),
		);
	}

	/**
	 * @param mixed[] $fields
	 *
	 * @return bool
	 */
	public static function fields_has_combination_of_options_and_options_group( $fields = array() ) {

		$fields = (object) $fields;

		/** @phpstan-ignore-next-line */
		foreach ( $fields as $field ) {
			foreach ( $field as $key => $value ) {
				if ( is_numeric( $key ) ) {
					return true;
				}
			}
		};

		return false;

	}

	/**
	 * Calculates the date elapsed.
	 *
	 * @param string $start_date
	 * @param string $end_date
	 *
	 * @return mixed[]
	 */
	public static function get_date_elapsed( $start_date, $end_date ) {

		$start_date_ts = self::strtotime( $start_date );
		$end_date_ts   = self::strtotime( $end_date );

		return array(
			'elapsed_in_seconds' => $end_date_ts - $start_date_ts,
			'human_time_diff'    => self::human_time_diff( $start_date, $end_date ),
		);

	}

	/**
	 * Converts a string with multiple placeholders into a formatted HTML structure with custom attributes.
	 *
	 * @param string $input The input string with placeholders enclosed in double curly braces.
	 * @return string The formatted HTML output.
	 */
	public static function format_sentence_with_placeholders( $input ) {
		// Validate the input.
		if ( empty( $input ) || ! is_string( $input ) ) {
			return '<div><span class="item-title__normal fallback-no-title-saved">Invalid input</span></div>';
		}

		// Define the pattern to match placeholders enclosed in double curly braces.
		$pattern = '/\{\{(.*?)\}\}/';

		// Check if there are any placeholders. If not, return the input string as plain text.
		if ( ! preg_match( $pattern, $input ) ) {
			return esc_html( $input );
		}

		// Replace placeholders with the desired HTML span structure.
		$formatted = preg_replace_callback(
			$pattern,
			function ( $matches ) {
				// Extract and sanitize the placeholder content.
				$token_content = trim( $matches[1] );
				$token_id      = strtoupper( str_replace( ' ', '_', $token_content ) ); // Convert to uppercase with underscores.
				$token_id      = preg_replace( '/[^A-Z0-9_]/', '', $token_id ); // Ensure token ID contains only valid characters.

				// Build the span for the token.
				return sprintf(
					'<span class="item-title__token" data-token-id="%s" data-options-id="%s">%s</span>',
					esc_attr( $token_id ),
					esc_attr( $token_id ),
					esc_html( $token_content )
				);
			},
			esc_html( $input )
		);

		// Wrap plain text (before the first placeholder) with a normal class span.
		$formatted = preg_replace( '/<span class="item-title__token">/', '<span class="item-title__normal fallback-no-title-saved">', $formatted, 1 );

		// Wrap the entire sentence in a div container.
		return sprintf( '<div>%s</div>', $formatted );

	}


}
