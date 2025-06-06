<?php

/**
 * App integrations
 * Settings > App integrations > Not connected
 *
 * Tab panel displayed when the user doesn't have an
 * automatorplugin.com account connected
 *
 * @since   3.8
 * @version 3.8
 * @package Uncanny_Automator
 * @author  Daniela R. & Agustin B.
 *
 * Variables:
 * $upgrade_to_pro_url      URL to upgrade to Automator Pro
 * $credits_article_url     URL to an article with information about the credits
 * $connect_site_url        URL to connect the site to automatorplugin.com
 * $current_integration_tab The current integration tab object
 */

namespace Uncanny_Automator;

?>

<uo-tab-panel active>

	<div class="uap-settings-panel">
		<div class="uap-settings-panel-placeholder">

			<uo-icon id="rotate"></uo-icon>

			<div class="uap-settings-panel-title">
				<?php
				printf(
					// translators: %s Integration name.
					esc_html__( 'Get app credits to connect to %s', 'uncanny-automator' ),
					esc_html( $current_integration_tab->name )
				);
				?>
			</div>

			<div class="uap-settings-panel-content">

				<div class="uap-settings-panel-content-paragraph">

					<?php

						printf(
							/* translators: 1. Highlighted text */
							esc_html__( 'Connect your site and start using app integrations! The free version of Uncanny Automator includes %1$s to use with our app integrations.', 'uncanny-automator' ),
							/* translators: 1. Integer. Number of credits */
							'<strong>' . sprintf( esc_html__( '%1$s free app credits', 'uncanny-automator' ), '250' ) . '</strong>'
						);

						?>

					<a
						href="<?php echo esc_url( $upgrade_to_pro_url ); ?>"
						target="_blank"
					>
						<?php esc_html_e( 'Buy Pro to get unlimited app credits!', 'uncanny-automator' ); ?> <uo-icon id="external-link"></uo-icon>
					</a>

				</div>

				<div class="uap-settings-panel-content-buttons">

					<uo-button
						href="<?php echo esc_url( $connect_site_url ); ?>"
					>
						<?php esc_html_e( 'Connect your site', 'uncanny-automator' ); ?>
					</uo-button>

					<uo-button
						href="<?php echo esc_url( $credits_article_url ); ?>"
						target="_blank"
						color="secondary"
					>
						<?php esc_html_e( 'Learn more', 'uncanny-automator' ); ?>
					</uo-button>

				</div>

			</div>
		</div>

	</div>

</uo-tab-panel>