<?php

namespace Uncanny_Automator;

/**
 * Class Bp_Tokens
 *
 * @package Uncanny_Automator
 */
class Bp_Tokens {


	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'BP';

	public function __construct() {
		add_filter( 'automator_maybe_trigger_bp_tokens', array( $this, 'bp_possible_tokens' ), 20, 2 );
		add_filter( 'automator_maybe_parse_token', array( $this, 'parse_bp_token' ), 20, 6 );
	}

	/**
	 * Only load this integration and its triggers and actions if the related
	 * plugin is active
	 *
	 * @param $status
	 * @param $code
	 *
	 * @return bool
	 */
	public function plugin_active( $status, $code ) {

		if ( self::$integration === $code ) {
			if ( function_exists( 'buddypress' ) && isset( buddypress()->buddyboss ) && buddypress()->buddyboss ) {
				$status = true;
			} else {
				$status = false;
			}
		}

		return $status;
	}

	/**
	 * BuddyPress possible tokens.
	 *
	 * @param array $tokens
	 * @param array $args
	 *
	 * @return array
	 */
	public function bp_possible_tokens( $tokens = array(), $args = array() ) {
		if ( ! automator_do_identify_tokens() ) {
			return $tokens;
		}
		$trigger_integration = $args['integration'];
		$trigger_meta        = $args['meta'];

		$fields = array(
			array(
				'tokenId'         => 'BPUSER',
				'tokenName'       => 'Avatar URL',
				'tokenType'       => 'text',
				'tokenIdentifier' => 'BPUSERAVATAR',
			),
		);
		// Get BP xprofile fields from DB.
		global $wpdb;
		$fields_table = $wpdb->base_prefix . 'bp_xprofile_fields';

		$xprofile_fields = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->base_prefix}bp_xprofile_fields WHERE parent_id = %d  ORDER BY group_id ASC",
				0
			)
		);

		if ( ! empty( $xprofile_fields ) ) {
			foreach ( $xprofile_fields as $field ) {
				if ( 'socialnetworks' === $field->type ) {
					$child_fields = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT * FROM {$wpdb->base_prefix}bp_xprofile_fields WHERE parent_id = %d ORDER BY group_id ASC",
							$field->id
						)
					);
					if ( ! empty( $child_fields ) ) {
						foreach ( $child_fields as $child_field ) {
							$fields[] = array(
								'tokenId'         => 'BPUSER',
								'tokenName'       => $field->name . ' - ' . $child_field->name,
								'tokenType'       => 'text',
								'tokenIdentifier' => 'BPXPROFILE:' . $field->id . '|' . $child_field->name,
							);
						}
					}
				} elseif ( 'membertypes' === $field->type ) {
					$fields[] = array(
						'tokenId'         => 'BPUSER',
						'tokenName'       => $field->name,
						'tokenType'       => 'text',
						'tokenIdentifier' => 'BPXPROFILE:' . $field->id . '|membertypes',
					);
				} else {
					$fields[] = array(
						'tokenId'         => 'BPUSER',
						'tokenName'       => $field->name,
						'tokenType'       => 'text',
						'tokenIdentifier' => 'BPXPROFILE:' . $field->id,
					);
				}//end if
			}//end foreach
		}//end if

		if ( isset( $args['triggers_meta']['code'] ) && 'BPACTIVITYSTRM' === $args['triggers_meta']['code'] ) {

			$fields[] = array(
				'tokenId'         => 'ACTIVITY_ID',
				'tokenName'       => esc_html__( 'Activity ID', 'uncanny-automator' ),
				'tokenType'       => 'int',
				'tokenIdentifier' => 'BPUSERACTIVITY',
			);
			$fields[] = array(
				'tokenId'         => 'ACTIVITY_URL',
				'tokenName'       => esc_html__( 'Activity URL', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'BPUSERACTIVITY',
			);
			$fields[] = array(
				'tokenId'         => 'ACTIVITY_STREAM_URL',
				'tokenName'       => esc_html__( 'Activity stream URL', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'BPUSERACTIVITY',
			);
			$fields[] = array(
				'tokenId'         => 'ACTIVITY_CONTENT',
				'tokenName'       => esc_html__( 'Activity content', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'BPUSERACTIVITY',
			);
		}//end if

		if ( isset( $args['triggers_meta']['code'] ) && ( 'BPUSERSENDSFRIENDREQUEST' === $args['triggers_meta']['code'] || 'BPUSERACCEPTFRIENDREQUEST' === $args['triggers_meta']['code'] ) ) {
			$trigger_code = $args['triggers_meta']['code'];
			$fields[]     = array(
				'tokenId'         => 'FRIEND_ID',
				'tokenName'       => esc_html__( 'Friend ID', 'uncanny-automator' ),
				'tokenType'       => 'int',
				'tokenIdentifier' => $trigger_code,
			);
			$fields[]     = array(
				'tokenId'         => 'FRIEND_FIRSTNAME',
				'tokenName'       => esc_html__( 'Friend first name', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			);
			$fields[]     = array(
				'tokenId'         => 'FRIEND_LASTNAME',
				'tokenName'       => esc_html__( 'Friend last name', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			);
			$fields[]     = array(
				'tokenId'         => 'FRIEND_EMAIL',
				'tokenName'       => esc_html__( 'Friend email', 'uncanny-automator' ),
				'tokenType'       => 'email',
				'tokenIdentifier' => $trigger_code,
			);
		}

		$tokens = array_merge( $tokens, $fields );

		return $tokens;
	}

	/**
	 * Parse BuddyPress Token.
	 *
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 *
	 * @return mixed
	 */
	public function parse_bp_token( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {
		if ( $pieces ) {
			if ( in_array( 'BPUSERS', $pieces ) ) {
				// Get user id from meta log
				if ( $trigger_data ) {
					foreach ( $trigger_data as $trigger ) {
						$token_value = $trigger['meta']['BPUSERS'];
						$value       = ( intval( $token_value ) == - 1 ) ? $user_id : $token_value;
					}
				}
			} elseif ( in_array( 'BPUSERAVATAR', $pieces ) ) {
				// Get Group id from meta log
				if ( function_exists( 'get_avatar_url' ) ) {
					$value = get_avatar_url( $user_id );
				}
			} elseif ( in_array( 'BPXPROFILE', $pieces ) ) {

				if ( isset( $pieces[2] ) && ! empty( $pieces[2] ) ) {

					// The function bp_get_profile_field_data() already formats the value.
					if ( function_exists( 'bp_get_profile_field_data' ) ) {
						$value = bp_get_profile_field_data(
							array(
								'field'   => absint( $pieces[2] ),
								'user_id' => $user_id,
							)
						);
					}
				}
			} elseif ( in_array( 'BPUSERACTIVITY', $pieces ) ) {

				if ( $trigger_data ) {
					foreach ( $trigger_data as $trigger ) {
						$trigger_id     = $trigger['ID'];
						$trigger_log_id = $replace_args['trigger_log_id'];
						$meta_key       = $pieces[2];
						$meta_value     = Automator()->helpers->recipe->get_form_data_from_trigger_meta( $meta_key, $trigger_id, $trigger_log_id, $user_id );
						if ( ! empty( $meta_value ) ) {
							$value = $meta_value;
						}
					}
				}
			} elseif ( in_array( 'BPUSERSENDSFRIENDREQUEST', $pieces ) || in_array( 'BPUSERACCEPTFRIENDREQUEST', $pieces ) ) {

				if ( $trigger_data ) {
					foreach ( $trigger_data as $trigger ) {
						$trigger_id     = $trigger['ID'];
						$trigger_log_id = $replace_args['trigger_log_id'];
						$meta_key       = $pieces[2];
						$meta_value     = Automator()->helpers->recipe->get_form_data_from_trigger_meta( $meta_key, $trigger_id, $trigger_log_id, $user_id );
						if ( ! empty( $meta_value ) ) {
							$value = maybe_unserialize( $meta_value );
						}
					}
				}
			}//end if
		}//end if

		return $value;
	}

	/**
	 * Get xprofile data.
	 *
	 * @param $user_id
	 * @param $field_id
	 *
	 * @return mixed|string
	 */
	public function get_xprofile_data( $user_id, $field_id ) {
		global $wpdb;
		if ( empty( $field_id ) ) {
			return '';
		}

		$field_token = explode( '|', $field_id );
		if ( count( $field_token ) > 0 ) {
			$field_id = $field_token[0];
		}

		$meta_value = $wpdb->get_var( $wpdb->prepare( "SELECT value FROM {$wpdb->prefix}bp_xprofile_data WHERE user_id = %d AND field_id = %s LIMIT 0,1", $user_id, $field_id ) );
		if ( ! empty( $meta_value ) ) {

			$meta_data = maybe_unserialize( $meta_value );
			if ( empty( $meta_data ) ) {
				return '';
			}
			if ( is_array( $meta_data ) ) {
				if ( isset( $field_token[1] ) ) {
					return isset( $meta_data[ $field_token[1] ] ) ? $meta_data[ $field_token[1] ] : '';
				}

				return implode( ', ', $meta_data );
			}

			if ( isset( $field_token[1] ) && 'membertypes' === $field_token[1] ) {
				return get_the_title( $meta_data );
			}

			return $meta_data;
		}

		return '';
	}
}
