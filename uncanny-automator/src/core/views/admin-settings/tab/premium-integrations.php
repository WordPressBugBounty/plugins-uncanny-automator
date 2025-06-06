<?php

/**
 * App integrations
 * Settings > App integrations
 *
 * @since   3.7
 * @version 3.7
 * @package Uncanny_Automator
 * @author  Daniela R. & Agustin B.
 *
 * Variables:
 * $user_can_use_premium_integrations  TRUE if the user can use the premium integrations
 * $integrations_tabs                  Array with the list of tabs
 * $current_integration                The ID of the current integration
 * $current_integration_tab            The current integration tab object
 * $upgrade_to_pro_url                 URL to upgrade to Automator Pro
 * $credits_article_url                URL to an article with information about the credits
 * $connect_site_url                   URL to connect the site to automatorplugin.com
 * $layout_version  The UI version, either "default" or "focus"
 */

namespace Uncanny_Automator;

?>

<uo-tabs
	direction="column"
	parameter="integration"
	order="title"
>

	<?php

	// Add navigation (tabs)
	// But don't add them in the "Focus" mode
	if ( 'focus' !== $layout_version ) {

		// Create tabs
		foreach ( $integrations_tabs as $tab_key => $integration_tab ) {

			$integration_tab = (object) $integration_tab;

			if ( empty( $integration_tab->preload ) ) {
				$integration_tab->preload = false;
			}

			?>

			<uo-tab
				id="<?php echo esc_attr( $tab_key ); ?>"

				<?php

				// IF the tab is selected, then add the "active" attribute
				echo $integration_tab->is_selected ? 'active' : '';

				?>

				<?php

				// IF the current tab is NOT selected,
				// and if the content DOESN'T have to be preloaded
				// THEN add the href attribute, which will redirect the user to another page
				if ( ! $integration_tab->is_selected && ! $integration_tab->preload ) {
					?>

					href="<?php echo esc_url( Admin_Settings_Premium_Integrations::utility_get_premium_integrations_page_link( $tab_key ) ); ?>"

					<?php
				}

				?>

				<?php

				// Check if there is an status
				if ( ! empty( $integration_tab->status ) ) {

					?>

					status="<?php echo esc_attr( $integration_tab->status ); ?>"

					<?php

				}

				?>
			>
				<div class="uo-tab-content">
					<div class="uo-tab-main">
						<?php
						// Check if it has an icon
						if ( isset( $integration_tab->icon ) ) {
							?>
							<uo-icon
								integration="<?php echo esc_attr( $integration_tab->icon ); ?>"
							></uo-icon>
							<?php
						}
						?>
						<?php echo esc_html( $integration_tab->name ); ?>
					</div>
					<?php
					// Add a badge if the tab is a third party integration
					if ( $integration_tab->is_third_party ) :
						?>
						<uo-chip size="small" filled>
							<?php echo esc_html_x( '3rd-party', 'Integration settings', 'uncanny-automator' ); ?>
						</uo-chip>
					<?php endif; ?>
				</div>
			</uo-tab>

			<?php

		}
	}

	// Add tab panels
	foreach ( $integrations_tabs as $tab_key => $integration_tab ) {

		$integration_tab = (object) $integration_tab;

		// Check if we have to load the content
		// The content will load if one of these conditions are meet
		// 1. If the tab is selected
		// 2. If the content should be preloaded
		if ( $integration_tab->is_selected || $integration_tab->preload ) {

			?>

			<uo-tab-panel
				id="<?php echo esc_attr( $tab_key ); ?>"

				<?php

				// IF the tab is selected, then add the "active" attribute
				echo $integration_tab->is_selected ? 'active' : '';

				?>
			>

				<?php do_action( 'automator_settings_premium_integrations_' . $tab_key . '_tab' ); ?>

			</uo-tab-panel>

			<?php

		}
	}

	// Current integration is empty so we're on the landing page.
	if ( empty( $current_integration_tab ) || ! is_object( $current_integration_tab ) ) {
		// Load "None selected" panel
		include Utilities::automator_get_view( 'admin-settings/tab/premium-integrations/none-selected.php' );
	} else {
		// We are on a specific integration page.

		// Show message if not connected and integration requires credits.
		if ( ! $user_can_use_premium_integrations && $current_integration_tab->requires_credits ) {
			include Utilities::automator_get_view( 'admin-settings/tab/premium-integrations/not-connected.php' );
		}
	}
	?>

</uo-tabs>