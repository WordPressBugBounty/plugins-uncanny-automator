<?php

namespace Uncanny_Automator;

use Exception;

/**
 * Class SHEET_UPDATERECORD
 *
 * @package Uncanny_Automator
 */
class SHEET_UPDATERECORD {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'GOOGLESHEET';

	/**
	 * The action code.
	 *
	 * @var string
	 */
	private $action_code;

	/**
	 * The action meta.
	 *
	 * @var string
	 */
	private $action_meta;

	/**
	 * The Automator endpoint url.
	 *
	 * @var string
	 */
	public $endpoint_url;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		$this->action_code = 'GOOGLESHEETUPDATERECORD';
		$this->action_meta = 'GOOGLESHEETROW';
		$this->define_action();
		$this->endpoint_url = AUTOMATOR_API_URL;
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	public function define_action() {

		$action = array(
			'is_deprecated'         => true,
			'author'                => Automator()->get_author_name( $this->action_code ),
			'support_link'          => Automator()->get_author_support_link( $this->action_code, 'knowledge-base/google-sheets/' ),
			'is_pro'                => false,
			'integration'           => self::$integration,
			'code'                  => $this->action_code,
			'sentence'              => sprintf(
				/* translators: Action sentence */
				esc_html__( 'Update a row in a {{Google Sheet:%1$s}}', 'uncanny-automator' ),
				$this->action_meta
			),
			'select_option_name'    => esc_html__( 'Update a row in a {{Google Sheet}}', 'uncanny-automator' ),
			'priority'              => 10,
			'accepted_args'         => 1,
			'requires_user'         => false,
			'execution_function'    => array( $this, 'update_row_google_sheet' ),
			'options_callback'      => array( $this, 'load_options' ),
			'custom_html'           => $this->custom_html(),
			'buttons'               => array(
				array(
					'show_in'     => $this->action_meta,
					'text'        => esc_html__( 'Get columns', 'uncanny-automator' ),
					'css_classes' => 'uap-btn uap-btn--red',
					'on_click'    => $this->get_samples_js(),
					'modules'     => array( 'modal', 'markdown' ),
				),
			),
			'background_processing' => true,
		);

		Automator()->register->action( $action );
	}

	/**
	 * load_options
	 *
	 * @return void
	 */
	public function load_options() {
		$options = array(
			'options_group' => array(
				$this->action_meta => array(
					Automator()->helpers->recipe->google_sheet->options->get_google_drives(
						esc_html__( 'Drive', 'uncanny-automator' ),
						'GSDRIVE',
						array(
							'is_ajax'      => true,
							'target_field' => 'GSSPREADSHEET',
							'endpoint'     => 'select_gsspreadsheet_from_gsdrive',
						)
					),
					Automator()->helpers->recipe->google_sheet->get_google_spreadsheets(
						esc_html__( 'Spreadsheet', 'uncanny-automator' ),
						'GSSPREADSHEET',
						array(
							'is_ajax'      => true,
							'target_field' => 'GSWORKSHEET',
							'endpoint'     => 'select_gsworksheet_from_gsspreadsheet',
						)
					),
					Automator()->helpers->recipe->google_sheet->get_google_worksheets(
						esc_html__( 'Worksheet', 'uncanny-automator' ),
						'GSWORKSHEET',
						array(
							'is_ajax'      => true,
							'target_field' => 'GSWORKSHEETCOLUMN',
							'endpoint'     => 'select_gsworksheet_from_gsspreadsheet_columns',
						)
					),
					Automator()->helpers->recipe->google_sheet->get_google_sheet_columns(
						esc_html__( 'Column search', 'uncanny-automator' ),
						'GSWORKSHEETCOLUMN',
						array(
							'is_ajax' => true,
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'GSWORKSHEET_SOURCE_VALUE',
							'input_type'  => 'text',
							'label'       => esc_attr__( 'Match value', 'uncanny-automator' ),
						)
					),
					array(
						'option_code'       => 'WORKSHEET_FIELDS',
						'input_type'        => 'repeater',
						'relevant_tokens'   => array(),
						'label'             => esc_html__( 'Row', 'uncanny-automator' ),
						/* translators: 1. Button */
						'description'       => '',
						'required'          => true,
						'fields'            => array(
							array(
								'option_code' => 'GS_COLUMN_NAME',
								'label'       => esc_html__( 'Column', 'uncanny-automator' ),
								'input_type'  => 'text',
								'required'    => true,
								'read_only'   => true,
								'options'     => array(),
							),
							array(
								'option_code' => 'COLUMN_UPDATE',
								'label'       => esc_html__( 'Update?', 'uncanny-automator' ),
								'input_type'  => 'checkbox',
								'is_toggle'   => true,
							),

							Automator()->helpers->recipe->field->text_field( 'GS_COLUMN_VALUE', esc_html__( 'Value', 'uncanny-automator' ), true, 'text', '', false ),
						),
						'add_row_button'    => esc_html__( 'Add pair', 'uncanny-automator' ),
						'remove_row_button' => esc_html__( 'Remove pair', 'uncanny-automator' ),
						'hide_actions'      => true,
						'can_sort_rows'     => false,
					),
					array(
						'option_code' => 'UPDATE_MULTIPLE_ROWS',
						'input_type'  => 'checkbox',
						'label'       => esc_html__( 'If multiple matches are found, update all matching rows instead of the first matching row only.', 'uncanny-automator' ),
					),
				),
			),
		);

		return $options;
	}

	/**
	 * Injects JS to the page when the action is added
	 *
	 * @return String The string with the JS
	 */
	private function custom_html() {
		// Start output
		ob_start();

		// Add the <script> tag
		?>

		<style>

		/**
		 * Set width of columns
		 */

		.uap-item[data-id="{{item_id}}"] .form-repeater-fields[data-id="WORKSHEET_FIELDS"] .form-repeater-fields__table {
			table-layout: auto;
		}

		.uap-item[data-id="{{item_id}}"] .form-repeater-fields[data-id="WORKSHEET_FIELDS"] .form-repeater-fields__table-heading-cell[data-id="GS_COLUMN_NAME"],
		.uap-item[data-id="{{item_id}}"] .form-repeater-fields[data-id="WORKSHEET_FIELDS"] .form-repeater-fields__table-rows-cell[data-id="GS_COLUMN_NAME"],
		.uap-item[data-id="{{item_id}}"] .form-repeater-fields[data-id="WORKSHEET_FIELDS"] .form-repeater-fields__table-heading-cell[data-id="GS_COLUMN_VALUE"],
		.uap-item[data-id="{{item_id}}"] .form-repeater-fields[data-id="WORKSHEET_FIELDS"] .form-repeater-fields__table-rows-cell[data-id="GS_COLUMN_VALUE"] {
			width: 50%;
		}

		.uap-item[data-id="{{item_id}}"] .form-repeater-fields[data-id="WORKSHEET_FIELDS"] .form-repeater-fields__table-heading-cell[data-id="COLUMN_UPDATE"],
		.uap-item[data-id="{{item_id}}"] .form-repeater-fields[data-id="WORKSHEET_FIELDS"] .form-repeater-fields__table-rows-cell[data-id="COLUMN_UPDATE"] {
			width: 1px;
			white-space: nowrap;
		}

		/**
		 * Hide the "Actions" column
		 */

		.uap-item[data-id="{{item_id}}"] .form-repeater-fields[data-id="WORKSHEET_FIELDS"] .form-repeater-fields__table-heading-cell--actions,
		.uap-item[data-id="{{item_id}}"] .form-repeater-fields[data-id="WORKSHEET_FIELDS"] .form-repeater-fields__table-rows-cell--actions {
			display: none;
		}

		/**
		 * Hide the actions row of the repeater field
		 */

		.uap-item[data-id="{{item_id}}"] .form-repeater-fields[data-id="WORKSHEET_FIELDS"] .form-repeater-fields__actions {
			display: none;
		}

		.uap-item[data-id="{{item_id}}"] .form-repeater-fields[data-id="WORKSHEET_FIELDS"] .form-repeater-fields__table-rows tr:last-child td {
			border-bottom: 0 !important;
		}

		.uap-item[data-id="{{item_id}}"] .form-repeater-fields[data-id="WORKSHEET_FIELDS"] .form-repeater-fields__table-rows tr:last-child td:first-child {
			border-bottom-left-radius: var(--uap-border-radius);
		}

		.uap-item[data-id="{{item_id}}"] .form-repeater-fields[data-id="WORKSHEET_FIELDS"] .form-repeater-fields__table-rows tr:last-child td[data-id="GS_COLUMN_VALUE"] {
			border-bottom-right-radius: var(--uap-border-radius);
		}

		</style>

		<script>

		/**
		 * Hide the "Value" field when the "Update?" checkbox is unchecked
		 * 
		 * @return {undefined}
		 */
		window.hideValueFieldDynamically = () => {
			// Get all checkboxes
			// {{item_id}} will be replaced with a real integer
			const $checkboxes = document.querySelectorAll( '.uap-item[data-id="{{item_id}}"] input[name="COLUMN_UPDATE"]' );

			/**
			 * Check the checkbox value
			 * 
			 * @return {undefined}
			 */
			const checkCheckboxValue = ( $checkbox ) => {
				// Get the status
				const shouldUpdate = $checkbox.checked;

				// Get the row
				const $row = $checkbox.closest( 'tr' );

				// Get the value field in the row
				const $field = $row.querySelector( 'td[data-id="GS_COLUMN_VALUE"] .form-element' );

				// Set the visibility dynamically
				if ( shouldUpdate ) {
					$field.style.display = 'block';
				} else {
					$field.style.display = 'none';
				}
			}

			// Iterate checkboxes
			$checkboxes.forEach( ( $checkbox ) => {
				// Check checkbox value
				checkCheckboxValue( $checkbox );

				// Add event listener to check the value on change
				$checkbox.addEventListener( 'change', () => {
					// Check checkbox value
					checkCheckboxValue( $checkbox );
				} );
			} );
		}

		// Listen when the options are rendered
		document.addEventListener( 'automator/item/options/open', ( event ) => {
			// Check if it's the options of our item
			// {{item_id}} will be replaced with a real integer
			if ( event.detail.itemId == '{{item_id}}' ) {
				// Check if the libraries are ready
				if ( event.detail.librariesReady ) {
					// Load our custom function
					hideValueFieldDynamically();
				}
			}
		} );

		// Listen when the call of "Get columns" is completed
		// {{item_id}} will be replaced with a real integer
		document.addEventListener( 'automator/{{item_id}}/get-columns', ( event ) => {
			// Load our custom function
			hideValueFieldDynamically();
		} );

		</script>

		<?php

		// Get output
		$output = ob_get_clean();

		// Return output
		return $output;
	}

	/**
	 * Anonymous JS function invoked as callback when clicking
	 * the custom button "Send test". The JS function requires
	 * the JS module "modal". Make sure it's included in
	 * the "modules" array
	 *
	 * @return string The JS code, with or without the <script> tags
	 */
	private function get_samples_js() {
		// Start output
		ob_start();

		// It's optional to add the <script> tags
		// This must have only one anonymous function
		?>

		<script>

			// Do when the user clicks on send test
			function ($button, data, modules) {

				// Create a configuration object
				let config = {
					// In milliseconds, the time between each call
					timeBetweenCalls: 1 * 1000,
					// In milliseconds, the time we're going to check for samples
					checkingTime: 60 * 1000,
					// Links
					links: {
						noResultsSupport: 'https://automatorplugin.com/knowledge-base/google-sheets/'
					},
					// i18n
					i18n: {
						<?php /* translators: The time in seconds left */ ?>
						checkingHooks: "<?php printf( esc_html__( "We're checking for columns. We'll keep trying for %s seconds.", 'uncanny-automator' ), '{{time}}' ); ?>",
						noResultsTrouble: "<?php esc_html_e( 'We had trouble finding columns.', 'uncanny-automator' ); ?>",
						noResultsSupport: "<?php esc_html_e( 'See more details or get help', 'uncanny-automator' ); ?>",
						samplesModalTitle: "<?php esc_html_e( "Here is the data we've collected", 'uncanny-automator' ); ?>",
						samplesModalWarning: "<?php /* translators: 1. Button */ printf( esc_html__( 'Clicking on \"%1$s\" will remove your current fields and will use the ones on the table above instead.', 'uncanny-automator' ), '{{confirmButton}}' ); ?>",
						samplesTableValueType: "<?php esc_html_e( 'Value type', 'uncanny-automator' ); ?>",
						samplesTableReceivedData: "<?php esc_html_e( 'Received data', 'uncanny-automator' ); ?>",
						samplesModalButtonConfirm: "<?php /* translators: Non-personal infinitive verb */ esc_html_e( 'Use these fields', 'uncanny-automator' ); ?>",
						samplesModalButtonCancel: "<?php /* translators: Non-personal infinitive verb */ esc_html_e( 'Do nothing', 'uncanny-automator' ); ?>",
					}
				}

				// Create the variable we're going to use to know if we have to keep doing calls
				let foundResults = false;

				// Get the date when this function started
				let startDate = new Date();

				// Create array with the data we're going to send
				let dataToBeSent = {
					action: 'get_worksheet_ROWS_GOOGLESHEETS',
					nonce: UncannyAutomator._site.rest.nonce,
					recipe_id: UncannyAutomator._recipe.recipe_id,
					item_id: data.item.id,
					drive: data.values.GSDRIVE,
					sheet: data.values.GSSPREADSHEET,
					worksheet: data.values.GSWORKSHEET
				};

				// Add notice to the item
				// Create notice
				let $notice = jQuery('<div/>', {
					'class': 'item-options__notice item-options__notice--warning'
				});

				// Add notice message
				$notice.html(config.i18n.checkingHooks.replace('{{time}}', parseInt(config.checkingTime / 1000)));

				// Get the notices container
				let $noticesContainer = jQuery('.item[data-id="' + data.item.id + '"] .item-options__notices');

				// Add notice
				$noticesContainer.html($notice);

				// Create the function we're going to use recursively to
				// do check for the samples
				var getSamples = function () {
					// Do AJAX call
					jQuery.ajax({
						method: 'POST',
						dataType: 'json',
						url: ajaxurl,
						data: dataToBeSent,

						// Set the checking time as the timeout
						timeout: config.checkingTime,

						success: function (response) {
							// Get new date
							let currentDate = new Date();

							// Define the default value of foundResults
							let foundResults = false;

							// Check if the response was successful
							if (response.success) {
								// Check if we got the rows from a sample
								if (response.samples.length > 0) {
									// Update foundResults
									foundResults = true;
								}
							}

							// Check if we have to do another call
							let shouldDoAnotherCall = false;

							// First, check if we don't have results
							if (!foundResults) {
								// Check if we still have time left
								if ((currentDate.getTime() - startDate.getTime()) <= config.checkingTime) {
									// Update result
									shouldDoAnotherCall = true;
								}
							}

							if (shouldDoAnotherCall) {
								// Wait and do another call
								setTimeout(function () {
									// Invoke this function again
									getSamples();
								}, config.timeBetweenCalls);
							} else {
								// Add loading animation to the button
								$button.removeClass('uap-btn--loading uap-btn--disabled');
								// Iterate samples and create an array with the rows
								let rows = [];
								let keys = {}
								jQuery.each(response.samples, function (index, sample) {
									// Iterate keys
									jQuery.each(sample, function (index, row) {
										// Check if the we already added this key
										if (typeof keys[row.key] !== 'undefined') {
											// Then just append the value
											// rows[ keys[ row.key ] ].data = rows[ keys[ row.key ] ].data + ', ' + row.data;
										} else {
											// Add row and save the index
											keys[row.key] = rows.push(row);
										}
									});
								});

								// Get the field with the fields (WEBHOOK_DATA)
								let worksheetFields = data.item.options.GOOGLESHEETROW.fields[5];

								// Remove all the current fields
								worksheetFields.fieldRows = [];

								// Add new rows. Iterate rows from the sample
								jQuery.each(rows, function (index, row) {
									// Add row
									worksheetFields.addRow({
										GS_COLUMN_NAME: row.key,
										GS_COLUMN_VALUE: ''
									}, false );
								});

								// Render again
								worksheetFields.reRender();

								// Check if it has results
								if (foundResults) {
									// Remove notice
									$notice.remove();

								} else {
									// Change the notice type
									$notice.removeClass('item-options__notice--warning').addClass('item-options__notice--error');

									// Create a new notice message
									let noticeMessage = config.i18n.noResultsTrouble;

									// Change the notice message
									$notice.html(noticeMessage + ' ');

									// Add help link
									let $noticeHelpLink = jQuery('<a/>', {
										target: '_blank',
										href: config.links.noResultsSupport
									}).text(config.i18n.noResultsSupport);
									$notice.append($noticeHelpLink);
								}

								// Dispatch custom event when everything is rendered
								document.dispatchEvent(
									new CustomEvent(
										`automator/${ data.item.id }/get-columns`,
									)
								);
							}
						},

						statusCode: {
							403: function () {
								location.reload();
							}
						},

						fail: function (response) {
						}
					});
				}

				// Add loading animation to the button
				$button.addClass('uap-btn--loading uap-btn--disabled');

				// Try to get samples
				getSamples();
			}

		</script>

		<?php

		// Get output
		$output = ob_get_clean();

		// Return output
		return $output;
	}


	/**
	 *
	 * @param mixed $worksheet_fields
	 * @param mixed $recipe_id
	 * @param mixed $user_id
	 * @param mixed $args
	 * @return string|false
	 */
	public static function parse_field_values( $worksheet_fields, $recipe_id, $user_id, $args ) {

		$key_values = array();

		$fields = (array) json_decode( $worksheet_fields, true );

		$fields_count = count( $fields );

		for ( $i = 0; $i < $fields_count; $i ++ ) {
			$key                = $fields[ $i ]['GS_COLUMN_NAME'];
			$value              = Automator()->parse->text( $fields[ $i ]['GS_COLUMN_VALUE'], $recipe_id, $user_id, $args );
			$key_values[ $key ] = $value;
		}

		return wp_json_encode( $key_values );

	}

	/**
	 * Validation function when the action is hit
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 */
	public function update_row_google_sheet( $user_id, $action_data, $recipe_id, $args ) {

		$spreadsheet_id       = $action_data['meta']['GSSPREADSHEET'];
		$gs_worksheet         = $action_data['meta']['GSWORKSHEET'];
		$lookup_field         = $action_data['meta']['GSWORKSHEETCOLUMN'];
		$update_multiple_rows = $action_data['meta']['UPDATE_MULTIPLE_ROWS'];
		$lookup_field_value   = Automator()->parse->text( $action_data['meta']['GSWORKSHEET_SOURCE_VALUE'], $recipe_id, $user_id, $args );

		// Fields repeater.
		$row_repeater = $action_data['meta']['WORKSHEET_FIELDS'];
		$fields       = json_decode( $row_repeater, true );

		$hashed   = sha1( Google_Sheet_Helpers::$hash_string );
		$sheet_id = substr( $hashed, 0, 9 );

		if ( (string) $gs_worksheet === (string) $sheet_id || intval( '-1' ) === intval( $gs_worksheet ) ) {
			$gs_worksheet = 0;
		}

		// Process the update.
		$spreadsheet_name = $action_data['meta']['GSWORKSHEET_readable'];

		// Construct the look up column search.
		$lookup_field_parts    = explode( '-', $lookup_field );
		$selected_column_range = $spreadsheet_name . '!' . $lookup_field_parts[1];

		$helper = Automator()->helpers->recipe->google_sheet;

		try {

			$row_values = self::get_activated_row_values( $fields, $recipe_id, $user_id, $args );

			$body = array(
				'action'                      => 'match_and_update',
				'range'                       => $selected_column_range,
				'spreadsheet_id'              => $spreadsheet_id,
				'spreadsheet_name'            => $spreadsheet_name,
				'should_update_multiple_rows' => $update_multiple_rows,
				'lookup_value'                => $lookup_field_value,
				'values'                      => $row_values,
			);

			$helper->api_call( $body, $action_data );

			Automator()->complete->action( $user_id, $action_data, $recipe_id );

		} catch ( Exception $e ) {

			$action_data['complete_with_errors'] = true;

			Automator()->complete->action( $user_id, $action_data, $recipe_id, $e->getMessage() );
		}

		return;

	}

	/**
	 * Get all the rows ranges that needs to be updated.
	 *
	 * @param mixed $fields
	 * @return string|false
	 */
	public static function get_activated_row_values( $row_repeater, $recipe_id, $user_id, $args ) {

		$row_values = array();

		foreach ( (array) $row_repeater as $field ) {

			$cell_value = null; // Pass null to avoid overwriting the cell value.

			if ( true === $field['COLUMN_UPDATE'] ) {
				$cell_value = Automator()->parse->text( $field['GS_COLUMN_VALUE'], $recipe_id, $user_id, $args );
			}

			// Add the value to our request body.
			$row_values[] = $cell_value;

		}

		return wp_json_encode( array( $row_values ) );  // Wrapping as array for API Backwards compatibility.
	}

	/**
	 * Wrapper method function Automator complete with errors method.
	 *
	 * @return boolean True.
	 */
	protected function complete_with_errors( $user_id, $action_data, $recipe_id, $error_message ) {

		$action_data['complete_with_errors'] = true;

		Automator()->complete_action( $user_id, $action_data, $recipe_id, $error_message );

		return true;

	}

	/**
	 * Get the response status from Google API response.
	 *
	 * @return array The status.
	 */
	public function get_response_status( $response ) {

		$response_status = json_decode( $response );

		// Google returned a valid response but there was a valid error.
		if ( isset( $response_status->error ) && ! empty( $response_status->error ) ) {
			$error_message = sprintf(
				/* translators: The error code, type, and description */
				esc_html__( 'Error: %1$s | %2$s | %3$s', 'uncanny-automator' ),
				$response_status->statusCode, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$response_status->error->type,
				$response_status->error->description
			);
			return array(
				'error'   => true,
				'message' => $error_message,
			);
		}

		return array(
			'error'   => false,
			'message' => null,
		);

	}

}
