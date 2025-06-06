<?php

namespace Uncanny_Automator;

use FrmEntry;
use FrmField;
use FrmForm;

/**
 * Class Fi_Tokens
 *
 * @package Uncanny_Automator
 */
class Fi_Tokens {

	public function __construct() {
		add_filter( 'automator_maybe_trigger_fi_fiform_tokens', array( $this, 'fi_possible_tokens' ), 20, 2 );
		add_filter( 'automator_maybe_trigger_fi_anonfiform_tokens', array( $this, 'fi_possible_tokens' ), 20, 2 );
		add_filter( 'automator_maybe_parse_token', array( $this, 'fi_token' ), 40, 6 );
		// Entry tokens
		add_filter( 'automator_maybe_trigger_fi_tokens', array( $this, 'fi_entry_possible_tokens' ), 20, 2 );
		add_filter( 'automator_maybe_parse_token', array( $this, 'fi_entry_tokens' ), 40, 6 );
	}

	/**
	 * Prepare tokens.
	 *
	 * @param array $tokens .
	 * @param array $args .
	 *
	 * @return array
	 */
	public function fi_possible_tokens( $tokens = array(), $args = array() ) {

		if ( ! automator_do_identify_tokens() ) {
			return $tokens;
		}

		$form_id             = $args['value'];
		$trigger_integration = $args['integration'];
		$trigger_meta        = $args['meta'];

		$form_ids            = array();
		$repeating_field     = 0;
		$disable_field_types = array(
			'end_divider',
			'captcha',
			'break',
			'html',
			'form',
			'summary',
			\FrmSubmitHelper::FIELD_TYPE,
		);

		if ( ! empty( $form_id ) && 0 !== $form_id && is_numeric( $form_id ) ) {

			$form = FrmForm::getOne( $form_id );

			if ( $form ) {
				$form_ids[] = $form->id;
			}
		}

		if ( empty( $form_ids ) ) {
			return $tokens;
		}

		if ( ! empty( $form_ids ) ) {
			foreach ( $form_ids as $form_id ) {
				$fields = array();
				$meta   = FrmField::get_all_for_form( $form_id );
				if ( is_array( $meta ) ) {
					foreach ( $meta as $field ) {
						if ( ! in_array( $field->type, $disable_field_types, true ) ) {
							$input_id    = $field->id;
							$input_title = $field->name . ( '' !== $field->description ? ' (' . $field->description . ') ' : '' );
							$token_id    = "$form_id|$input_id";
							// Splits the token into three parts: First, middle, and last.
							if ( 'name' === $field->type ) {
								foreach ( array( 'first', 'middle', 'last' ) as $name_part ) {
									if ( ! empty( $field->field_options[ $name_part . '_desc' ] ) ) {
										$fields[] = array(
											'tokenId'         => "$form_id|$input_id-$name_part", // phpcs:ignore WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
											'tokenName'       => $field->field_options[ $name_part . '_desc' ], // phpcs:ignore WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
											'tokenType'       => 'text', // phpcs:ignore WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
											'tokenIdentifier' => $trigger_meta,
										);
									}
								}
							} elseif ( FrmField::is_repeating_field( $field ) ) {
								$repeating_field = $field->id;
								$fields[]        = array(
									'tokenId'         => $token_id,
									'tokenName'       => $input_title,
									'tokenType'       => $field->type,
									'tokenIdentifier' => $trigger_meta,
								);
							} elseif ( 0 !== $repeating_field ) {
								$fields[] = array(
									'tokenId'         => "$form_id|$repeating_field-repeater-$input_id",
									'tokenName'       => $input_title,
									'tokenType'       => $field->type,
									'tokenIdentifier' => $trigger_meta,
								);
							} else {
								$fields[] = array(
									'tokenId'         => $token_id,
									'tokenName'       => $input_title,
									'tokenType'       => $field->type,
									'tokenIdentifier' => $trigger_meta,
								);
							}
						}
					}
				}
			}
			$tokens = array_merge( $tokens, $fields );
		}

		return $tokens;
	}

	/**
	 * Parse the token.
	 *
	 * @param string $value .
	 * @param array $pieces .
	 * @param string $recipe_id .
	 *
	 * @return null|string
	 */
	public function fi_token( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {

		if ( $pieces ) {

			if ( in_array( 'FIFORM', $pieces, true ) || in_array( 'ANONFIFORM', $pieces, true ) || in_array( 'ANONFISUBMITFORM', $pieces, true ) ) {

				if ( 'FIFORM' === $pieces[2] ) {

					// Sends readable FIFORM value backs.
					if ( isset( $trigger_data[0]['meta']['FIFORM_readable'] ) ) {
						$value = $trigger_data[0]['meta']['FIFORM_readable'];
					}
				} elseif ( 'ANONFIFORM' === $pieces[2] ) {

					// Sends readable ANONFIFORM value backs.
					if ( isset( $trigger_data[0]['meta']['ANONFIFORM_readable'] ) ) {
						$value = $trigger_data[0]['meta']['ANONFIFORM_readable'];
					}
				} elseif ( 'ANONFIFORM_ID' === $pieces[2] ) {

					// Sends readable ANONFIFORM ID value back.
					if ( isset( $trigger_data[0]['meta']['ANONFIFORM'] ) ) {
						$value = $trigger_data[0]['meta']['ANONFIFORM'];
					}
				} else {

					// Processes entry token fields.
					$token_info = explode( '|', $pieces[2] );

					$form_id  = $token_info[0];
					$meta_key = $token_info[1];
					$fields   = FrmField::get_all_for_form( $form_id );

					$entry_id = absint( Automator()->db->token->get( 'FIENTRYID', $replace_args ) );

					$entries = array();

					/**
					 * Pushes entry into the entry_id index. Supports legacy handling of entry.
					 *
					 * Avoids unreliable fetching.
					 *
					 * @ticket 2100575229
					 */
					$entries[ $entry_id ] = FrmEntry::getOne( $entry_id, true );

					// Collects all file field types.
					$file_fields = array();
					// Name fields.
					$name_fields = array();

					foreach ( $fields as $field ) {
						if ( isset( $field->type ) && 'file' === $field->type ) {
							$file_fields[] = $field->id;
						}
						if ( isset( $field->type ) && 'name' === $field->type ) {
							$name_fields[] = $field->id;
						}
					}

					if ( ! empty( $entries ) ) {

						foreach ( $entries as $entry ) {

							// Try splitting the names.
							$name_split_meta_key = explode( '-', $meta_key );

							// It means its a split name if it has two parts.
							if ( ! empty( $name_split_meta_key ) && 2 === count( $name_split_meta_key ) ) {

								list( $field_id, $name_part ) = $name_split_meta_key;

								return ! empty( $entry->metas[ $field_id ][ $name_part ] ) ? $entry->metas[ $field_id ][ $name_part ] : null;

							}
							// Try splitting the repeating field.
							$repeating_field_split_meta_key = explode( '-', $meta_key );
							if ( ! empty( $repeating_field_split_meta_key ) && 3 === count( $repeating_field_split_meta_key ) ) {
								$item_meta = \FrmAppHelper::get_post_param( 'item_meta', array() );
								$values    = $this->get_repeater_field_value( $item_meta, $repeating_field_split_meta_key[0], $repeating_field_split_meta_key[2] );

								return join( ', ', $values );
							}

							if ( isset( $entry->metas ) && isset( $entry->metas[ $meta_key ] ) ) {

								if ( is_array( $entry->metas[ $meta_key ] ) ) {

									// Automatically converts array values to single string (comma separated).
									$value = implode( ', ', $entry->metas[ $meta_key ] );

									/**
									 * The code above introduced a side-effect with names.
									 *
									 * @ticket 2088307178
									 */
									if ( in_array( $meta_key, $name_fields, true ) ) {
										// Separate by space if the meta_key (field id) belongs to the name fields.
										$value = implode( ' ', $entry->metas[ $meta_key ] );
									}
								} elseif ( in_array( $meta_key, $file_fields, true ) ) {

									$media_id = $entry->metas[ $meta_key ];

									$attachment = get_post( $media_id );

									if ( ! $attachment ) {
										$value = $entry->metas[ $meta_key ];
									}

									$image = wp_get_attachment_image( $media_id, 'thumbnail', true );

									// Check if its image. Then just return the url.
									if ( $image ) {
										$value = esc_url( wp_get_attachment_url( $media_id ) );
									}
								} else {

									$value = $entry->metas[ $meta_key ];

								}

								break;

							}
						}
					}
				}
			}
		}

		return $value;
	}

	/**
	 * Entry possible tokens.
	 *
	 * @param $tokens
	 * @param $args
	 *
	 * @return array|\string[][]
	 */
	public function fi_entry_possible_tokens( $tokens = array(), $args = array() ) {
		$fields = array(
			array(
				'tokenId'         => 'FIENTRYID',
				'tokenName'       => esc_html_x( 'Entry ID', 'Formidable', 'uncanny-automator' ),
				'tokenType'       => 'int',
				'tokenIdentifier' => 'FIENTRYTOKENS',
			),
			array(
				'tokenId'         => 'FIUSERIP',
				'tokenName'       => esc_html_x( 'User IP', 'Formidable', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'FIENTRYTOKENS',
			),
			array(
				'tokenId'         => 'FIENTRYDATE',
				'tokenName'       => esc_html_x( 'Entry submission date', 'Formidable', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'FIENTRYTOKENS',
			),
			array(
				'tokenId'         => 'FIENTRYSOURCEURL',
				'tokenName'       => esc_html_x( 'Entry source URL', 'Formidable', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'FIENTRYTOKENS',
			),
		);

		$tokens = array_merge( $tokens, $fields );

		return $tokens;
	}

	/**
	 * Entry tokens.
	 *
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return mixed|strings
	 */
	public function fi_entry_tokens( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {
		if ( in_array( 'FIENTRYTOKENS', $pieces, true ) ) {
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
		}

		return $value;
	}

	/**
	 * Get repeater field value.
	 *
	 * @param mixed $data The data.
	 * @param mixed $section_id The ID.
	 * @param mixed $field_id The ID.
	 *
	 * @return mixed
	 */
	private function get_repeater_field_value( $data, $section_id, $field_id ) {
		if ( ! isset( $data[ $section_id ] ) || ! is_array( $data[ $section_id ] ) ) {
			return array(); // Return empty array if section_id is not found or not an array
		}

		$repeater_data = $data[ $section_id ];
		$values        = array();

		if ( isset( $repeater_data['row_ids'] ) && is_array( $repeater_data['row_ids'] ) ) {
			foreach ( $repeater_data['row_ids'] as $row_id ) {
				if ( isset( $repeater_data[ $row_id ][ $field_id ] ) ) {
					$values[] = $repeater_data[ $row_id ][ $field_id ]; // Store found value
				}
			}
		}

		return $values;
	}
}
