<?php
/**
 * Trait used to create the setting pages of the premium integrations
 *
 * @class   Settings
 * @since   3.7
 * @version 3.7
 * @author  Agustin B.
 * @package Uncanny_Automator
 */

namespace Uncanny_Automator\Settings;

use Exception;

/**
 * Trait to create settings pages of Premium Integrations
 *
 * @package Uncanny_Automator\Settings
 */
trait Premium_Integrations {
	/**
	 * The ID of the integration
	 * This will also be used as the tab ID
	 *
	 * @var String
	 */
	protected $id;

	/**
	 * The icon of the integration
	 * This expects a valid <uo-icon> ID.
	 * Check the Design Guidelines to see the list of valid IDs.
	 *
	 * @var String
	 */
	protected $icon;

	/**
	 * The name of the integration
	 *
	 * @var String
	 */
	protected $name;

	/**
	 * Whether the integration requires credits
	 *
	 * @var boolean
	 */
	public $requires_credits = true;

	/**
	 * Whether the integration is a third party integration
	 *
	 * @var boolean
	 */
	public $is_third_party = false;

	/**
	 * The status of the integration
	 * This expects a valid <uo-tab> status
	 * Check the Design Guidelines to see the list of valid statuses
	 *
	 * @var String
	 */
	protected $status;

	/**
	 * The preload setting of the integration
	 * This defines whether the content should be loaded even if the tab
	 * is not selected
	 *
	 * @var String
	 */
	protected $preload = false;

	/**
	 * Defines the IDs of the options used in this premium integration
	 *
	 * @var Array
	 */
	protected $options = array();

	/**
	 * Relative path of the JS file that loads only in the settings page
	 * of this premium integration
	 *
	 * @var String
	 */
	protected $js = '';

	/**
	 * Relative path of the CSS file that loads only in the settings page
	 * of this premium integration
	 *
	 * @var String
	 */
	protected $css = '';

	/**
	 * An array of alerts to display on the current settings page
	 *
	 * @var array
	 */
	protected $alerts = array();

	/**
	 * Make this method mandatory
	 * Sets up the properties of the settings page
	 */
	abstract protected function set_properties();

	/**
	 * Placeholder function to call two main functions
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function setup_settings() {
		// Set up the properties
		$this->set_properties();

		// Register the settings page
		// Invoke this method last
		$this->register_settings();

		if ( $this->is_current_page_settings() ) {
			$this->maybe_settings_updated();
		}
	}

	/**
	 * Sets the ID of the integration
	 * This will also be used as the tab ID
	 *
	 * @param String $id The ID
	 */
	public function set_id( $id ) {
		// Check if the ID is defined
		if ( empty( $id ) ) {
			throw new Exception( "Premium Integration: The ID can't be empty" );
		}

		$this->id = $id;
	}

	/**
	 * Returns the integration ID
	 *
	 * @return String The integration ID
	 */
	public function get_id() {
		// Check if the ID is defined
		if ( empty( $this->id ) ) {
			throw new Exception( "Premium Integration: The ID can't be empty" );
		}

		return $this->id;
	}

	/**
	 * Sets the icon of the integration
	 * This expects a valid <uo-icon> ID.
	 * Check the Design Guidelines to see the list of valid IDs.
	 *
	 * @param String $icon The icon
	 */
	public function set_icon( $icon ) {
		$this->icon = $icon;
	}

	/**
	 * Returns the integration icon
	 *
	 * @return String The integration icon
	 */
	public function get_icon() {
		// As the property is optional, we will return a default value if it's not defined
		return ! empty( $this->icon ) ? $this->icon : 'bolt';
	}

	/**
	 * Sets the name of the integration
	 *
	 * @param String $name The name
	 */
	public function set_name( $name ) {
		// Check if the name is defined
		if ( empty( $name ) ) {
			throw new Exception( "Premium Integration: The name can't be empty" );
		}

		$this->name = $name;
	}

	/**
	 * Returns the integration name
	 *
	 * @return String The integration name
	 */
	public function get_name() {
		// Check if the name is defined
		if ( empty( $this->name ) ) {
			throw new Exception( "Premium Integration: The name can't be empty" );
		}

		return $this->name;
	}

	/**
	 * Returns whether the integration requires credits
	 *
	 * @return boolean TRUE if the integration requires credits
	 */
	public function get_requires_credits() {
		return $this->requires_credits;
	}

	/**
	 * Sets whether the integration requires credits
	 *
	 * @param boolean $requires_credits TRUE if the integration requires credits
	 */
	public function set_requires_credits( $requires_credits = true ) {
		$this->requires_credits = $requires_credits;
	}

	/**
	 * Returns whether the integration is a third party integration
	 *
	 * @return boolean TRUE if the integration is a third party integration
	 */
	public function get_is_third_party() {
		return $this->is_third_party;
	}

	/**
	 * Sets whether the integration is a third party integration
	 *
	 * @param boolean $is_third_party TRUE if the integration is a third party integration
	 */
	public function set_is_third_party( $is_third_party = false ) {
		$this->is_third_party = $is_third_party;
	}

	/**
	 * Sets the status of the integration
	 * This expects a valid <uo-tab> status
	 * Check the Design Guidelines to see the list of valid statuses
	 *
	 * @param String $status The status
	 */
	public function set_status( $status = '' ) {
		$this->status = $status;
	}

	/**
	 * Returns the integration status
	 *
	 * @return String The integration status
	 */
	public function get_status() {
		return ! empty( $this->status ) ? $this->status : '';
	}

	/**
	 * Sets the preload setting
	 * This defines whether the content should be loaded even if the tab
	 * is not selected
	 *
	 * @param boolean $preload TRUE if Automator should load the content even if the tab not selected
	 */
	public function set_preload( $preload = false ) {
		$this->preload = $preload;
	}

	/**
	 * Returns the preload setting
	 *
	 * @return boolean TRUE if Automator should load the content even if the tab is not selected
	 */
	public function get_preload() {
		return ! empty( $this->preload ) ? $this->preload : false;
	}

	/**
	 * Registers an option
	 *
	 * @param String The WordPress option name
	 */
	public function register_option( $option_name ) {
		// Check if this setting wasn't added already
		if ( ! in_array( $option_name, $this->get_options(), true ) ) {
			$this->options[] = $option_name;
		}
	}

	/**
	 * Sets the registered options
	 */
	public function set_options( $options ) {
		$this->options = $options;
	}

	/**
	 * Returns the registered options
	 *
	 * @return Array The options
	 */
	public function get_options() {
		return $this->options;
	}

	/**
	 * Outputs the content of the settings page of this integration
	 */
	protected function output() {
		// Return a placeholder
		// Each Premium Integration will have its own output method
		// Don't translate the string, it's just for internal use
		printf( '%1$s settings', esc_html( $this->get_name() ) );
	}

	/**
	 * Returns the URL to the Settings page of this integration
	 *
	 * @return String The URL
	 */
	public function get_settings_page_url() {
		// Return the URL
		return automator_get_premium_integrations_settings_url( $this->get_id() );
	}

	/**
	 * Sets a JS file that loads only on the settings page of this
	 * premium integration
	 *
	 * @param String $js The path of the JS file.
	 */
	public function set_js( $js ) {
		$this->js = $js;
	}

	/**
	 * Returns the path of the JS file of this settings page
	 *
	 * @return String The JS file path
	 */
	public function get_js() {
		return $this->js;
	}

	/**
	 * Sets a CSS file that loads only on the settings page of this
	 * premium integration
	 *
	 * @param String $css The path of the CSS file.
	 */
	public function set_css( $css ) {
		$this->css = $css;
	}

	/**
	 * Returns the path of the CSS file of this settings page
	 *
	 * @return String The CSS file path
	 */
	public function get_css() {
		return $this->css;
	}

	/**
	 * Determines whether the user is currently in the Settings page of the integration
	 *
	 * @return boolean TRUE if it is
	 */
	public function is_current_page_settings() {
		return automator_filter_input( 'page' ) === 'uncanny-automator-config'
			&& automator_filter_input( 'tab' ) === 'premium-integrations'
			&& automator_filter_input( 'integration' ) === $this->get_id();
	}

	/**
	 * Registers the Settings page
	 */
	public function register_settings() {
		// Check if the required data is defined
		if ( empty( $this->get_id() || empty( $this->get_name() ) ) ) {
			throw new Exception( 'Premium Integration: Define the ID and name of the integration' );
		}

		// Add the tab using the filter
		add_filter( 'automator_settings_premium_integrations_tabs', array( $this, 'add_tab' ) );

		// Register the options/settings
		//$this->add_wordpress_settings();
		add_filter( 'admin_init', array( $this, 'add_wordpress_settings' ) );

		// Enqueue the assets
		//$this->enqueue_assets();
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Adds the tab and the function that outputs the content to the Settings page
	 */
	public function add_tab( $tabs ) {
		// Check if the ID is defined
		// Create the tab
		$tabs[ $this->get_id() ] = (object) array(
			'name'             => $this->get_name(),
			'icon'             => $this->get_icon(),
			'status'           => $this->get_status(),
			'preload'          => $this->get_preload(),
			'requires_credits' => $this->get_requires_credits(),
			'is_third_party'   => $this->get_is_third_party(),
			'function'         => array( $this, 'output' ),
		);

		return $tabs;
	}

	/**
	 * Registers the options
	 */
	public function add_wordpress_settings() {
		// Check if it has options
		if ( empty( $this->get_options() ) ) {
			return;
		}

		foreach ( $this->get_options() as $option_name ) {
			register_setting( $this->get_settings_id(), $option_name ); // phpcs:ignore PluginCheck.CodeAnalysis.SettingSanitization.register_settingMissing
		}
	}

	/**
	 * Returns the option group user in settings_fields()
	 *
	 * @return String The option group
	 */
	public function get_settings_id() {
		return 'uncanny_automator_' . $this->get_id();
	}

	/**
	 * Returns the nonce action
	 *
	 * @return String The nonce action
	 */
	public function get_nonce_action() {
		return $this->get_settings_id() . '-options';
	}

	/**
	 * Enqueue the assets
	 */
	public function enqueue_assets() {
		// Check if there are assets defined
		if ( ! $this->get_css() && ! $this->get_js() ) {
			return;
		}

		// Only enqueue the assets of this integration on its own settings page
		if ( ! $this->is_current_page_settings() ) {
			return;
		}

		// Enqueue the CSS
		if ( $this->get_css() ) {
			wp_enqueue_style(
				'uap-premium-integration-' . $this->get_id(),
				plugins_url( '/src/integrations/' . $this->get_css(), AUTOMATOR_BASE_FILE ),
				array( 'uap-admin' ),
				AUTOMATOR_PLUGIN_VERSION
			);
		}

		// Enqueue the JS
		if ( $this->get_js() ) {
			wp_enqueue_script(
				'uap-premium-integration-' . $this->get_id(),
				plugins_url( '/src/integrations/' . $this->get_js(), AUTOMATOR_BASE_FILE ),
				array( 'uap-admin' ),
				AUTOMATOR_PLUGIN_VERSION,
				true
			);
		}
	}

	/**
	 * Determines whether the current page settings were updated
	 *
	 * @return void
	 */
	public function maybe_settings_updated() {
		if ( 'true' === automator_filter_input( 'settings-updated' ) ) {
			$this->settings_updated();
		}
	}

	/**
	 * Override this method to run code when the settings were updated
	 *
	 * @return void
	 */
	public function settings_updated() {
	}

	/**
	 * add_alert
	 *
	 * Add an alert to show on the settings page
	 *
	 * @param array $alert
	 *
	 * @return void
	 */
	public function add_alert( $alert ) {
		$this->alerts[] = $alert;
	}

	/**
	 * get_alerts
	 *
	 * Get all alerts
	 *
	 * @return array
	 */
	public function get_alerts() {
		return $this->alerts;
	}

	/**
	 * This method will output all the queued alerts HTML.
	 *
	 * @return void
	 */
	public function display_alerts() {

		if ( ! empty( $this->get_alerts() ) ) {
			foreach ( $this->get_alerts() as $alert ) {
				$this->alert_html( $alert );
			}
		}
	}

	/**
	 * alert_html
	 *
	 * Output the uo-alert HTML
	 *
	 * @param array $alert
	 *
	 * @return void
	 */
	public function alert_html( $alert ) {

		$default = array(
			'type'    => '',
			'heading' => '',
			'content' => '',
		);

		$alert = wp_parse_args( $alert, $default );

		$allowed_html = array(
			'a'       => array(
				'href'   => array(),
				'target' => array(),
			),
			'uo-icon' => array(
				'id' => array(),
			),
		);

		?>

		<uo-alert
			type="<?php echo esc_attr( $alert['type'] ); ?>"
			heading="<?php echo esc_attr( $alert['heading'] ); ?>"
			class="uap-spacing-bottom uap-spacing-top"
		><?php echo( wp_kses( $alert['content'], $allowed_html ) ); ?></uo-alert>

		<?php
	}

	/**
	 * text_input_html
	 *
	 * Output the uo-text-input HTML
	 *
	 * @param array $input
	 *
	 * @return void
	 */
	public function text_input_html( $input ) {

		$default = array(
			'id'       => '',
			'value'    => '',
			'label'    => '',
			'required' => '',
			'class'    => '',
			'hidden'   => '',
			'disabled' => '',
		);

		$input = wp_parse_args( $input, $default );
		?>

		<uo-text-field
			id="<?php echo esc_attr( $input['id'] ); ?>"
			value="<?php echo esc_attr( $input['value'] ); ?>"
			label="<?php echo esc_attr( $input['label'] ); ?>"
			class="<?php echo esc_attr( $input['class'] ); ?>"
			<?php
			if ( ! empty( $input['required'] ) ) {
				echo 'required ';
			}
			if ( ! empty( $input['hidden'] ) ) {
				echo 'hidden ';
			}
			if ( ! empty( $input['disabled'] ) ) {
				echo 'disabled ';
			}
			?>
		></uo-text-field>
		<?php
	}
}
