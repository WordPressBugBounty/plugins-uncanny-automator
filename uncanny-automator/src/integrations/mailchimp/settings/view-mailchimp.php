<?php
/**
 * MailChimp Settings
 * Settings > Premium Integrations > Facebook
 *
 * @since   3.7
 * @version 3.7
 * @package Uncanny_Automator
 *
 * $client   The Mailchimp Client.
 * $auth_uri The URI of Mailchimp OAuth Dialog.
 * $disconnect_uri The disconnect url.
 * $connect_code Holds an integer value which is used to identify if connection is successful or not.
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}
?>

<form method="POST" action="options.php">

	<?php settings_fields( $this->get_settings_id() ); ?>

	<div class="uap-settings-panel">

		<div class="uap-settings-panel-top">

			<div class="uap-settings-panel-title">

				<uo-icon integration="MAILCHIMP"></uo-icon>

				<?php echo esc_html_x( 'Mailchimp', 'MailChimp', 'uncanny-automator' ); ?>

			</div>

			<div class="uap-settings-panel-content">

				<?php if ( 1 === $connect_code && $this->is_connected ) { ?>
					<?php /* translators: Success message */ ?>
					<uo-alert class="uap-spacing-bottom" type="success" heading="<?php echo esc_attr( sprintf( esc_html_x( 'Your account "%s" has been connected successfully!', 'Mailchimp', 'uncanny-automator' ), $this->client->login->login_name ) ); ?>"></uo-alert>
				<?php } ?>

				<?php if ( 2 === $connect_code ) { ?>

					<uo-alert type="error" class="uap-spacing-bottom">
						<?php echo esc_html_x( 'Something went wrong while connecting to application. Please try again.', 'MailChimp', 'uncanny-automator' ); ?>
					</uo-alert>

				<?php } ?>

				<?php if ( $this->is_connected ) { ?>

					<uo-alert
						heading="<?php echo esc_html_x( 'Uncanny Automator only supports connecting to one Mailchimp account at a time.', 'MailChimp', 'uncanny-automator' ); ?>"
					></uo-alert>

					<div class="uap-settings-panel-content-separator"></div>

					<uo-switch id="uap_mailchimp_enable_webhook" <?php echo esc_attr( $enable_triggers ); ?> label="<?php echo esc_attr_x( 'Enable triggers', 'Mailchimp', 'uncanny-automator' ); ?>"></uo-switch>

					<div id="uap-mailchimp-webhook" style="display:none;">
						<uo-alert
							heading="<?php esc_attr_x( 'Setup instructions', 'MailChimp', 'uncanny-automator' ); ?>"
							class="uap-spacing-top"
						>

							<p>
								<?php
									echo sprintf(
										esc_html_x(
											"Enabling Mailchimp triggers requires setting up a webhook in your Mailchimp account using the URL below. A few steps and you'll be up and running in no time. Visit our %1\$s for simple instructions.",
											'MailChimp',
											'uncanny-automator'
										),
										'<a href="' . esc_url( $kb_link ) . '" target="_blank">' . esc_html_x( 'Knowledge Base article', 'MailChimp', 'uncanny-automator' ) . ' <uo-icon id="external-link"></uo-icon></a>'
									);
								?>
							</p>

							<uo-text-field
								value="<?php echo esc_url( $webhook_url ); ?>"
								label="<?php echo esc_attr_x( 'Webhook URL', 'Mailchimp', 'uncanny-automator' ); ?>"
								helper="
								<?php
									echo sprintf(
										/* translators: %1$s Settings field description */
										esc_attr_x(
											'Use this URL to create a webhook in %1$s of the audiences that you want to trigger recipes.',
											'MailChimp',
											'uncanny-automator'
										),
										'<strong>' . esc_html_x( 'each', 'MailChimp', 'uncanny-automator' ) . '</strong>'
									);
								?>
								"
								disabled
							></uo-text-field>

							<uo-button
								onclick="return confirm('<?php echo esc_html( $regenerate_alert ); ?>');"
								href="<?php echo esc_url( $regenerate_key_url ); ?>"
								size="small"
								color="secondary"
								class="uap-spacing-top"
							>
								<uo-icon id="rotate"></uo-icon> 
								<?php echo esc_attr_x( 'Regenerate webhook URL', 'Mailchimp', 'uncanny-automator' ); ?>
							</uo-button>

						</uo-alert>
					</div>
				<?php } ?>

				<?php if ( ! $this->is_connected ) { ?>

					<div class="uap-settings-panel-content-subtitle">
						<?php echo esc_html_x( 'Connect Uncanny Automator to Mailchimp', 'Mailchimp', 'uncanny-automator' ); ?>
					</div>

					<div class="uap-settings-panel-content-paragraph uap-settings-panel-content-paragraph--subtle">
						<?php echo esc_html_x( 'Connect Uncanny Automator to Mailchimp to better segment and engage with your customers, or automatically send an email to subscribers when a new blog post is published. Add users to audiences and manage user tags based on activity on your WordPress site.', 'Mailchimp', 'uncanny-automator' ); ?>
					</div>

					<p>
						<strong><?php echo esc_html_x( 'Activating this integration will enable the following for use in your recipes:', 'Mailchimp', 'uncanny-automator' ); ?></strong>
					</p>

					<ul>
						<li>
							<uo-icon id="bolt"></uo-icon> <strong><?php echo esc_html_x( 'Trigger:', 'Mailchimp', 'uncanny-automator' ); ?></strong> <?php echo esc_html_x( 'A contact email is changed', 'Mailchimp', 'uncanny-automator' ); ?>
						</li>
						<li>
							<uo-icon id="bolt"></uo-icon> <strong><?php echo esc_html_x( 'Trigger:', 'Mailchimp', 'uncanny-automator' ); ?></strong> <?php echo esc_html_x( 'A contact is added to an audience', 'Mailchimp', 'uncanny-automator' ); ?>
						</li>
						<li>
							<uo-icon id="bolt"></uo-icon> <strong><?php echo esc_html_x( 'Trigger:', 'Mailchimp', 'uncanny-automator' ); ?></strong> <?php echo esc_html_x( 'A contact is unsubscribed from an audience', 'Mailchimp', 'uncanny-automator' ); ?>
						</li>
						<li>
							<uo-icon id="bolt"></uo-icon> <strong><?php echo esc_html_x( 'Action:', 'Mailchimp', 'uncanny-automator' ); ?></strong> <?php echo esc_html_x( 'Add a note to the user', 'Mailchimp', 'uncanny-automator' ); ?>
						</li>
						<li>
							<uo-icon id="bolt"></uo-icon> <strong><?php echo esc_html_x( 'Action:', 'Mailchimp', 'uncanny-automator' ); ?></strong> <?php echo esc_html_x( 'Add a tag to the user', 'Mailchimp', 'uncanny-automator' ); ?>
						</li>
						<li>
							<uo-icon id="bolt"></uo-icon> <strong><?php echo esc_html_x( 'Action:', 'Mailchimp', 'uncanny-automator' ); ?></strong> <?php echo esc_html_x( 'Add the user to an audience', 'Mailchimp', 'uncanny-automator' ); ?>
						</li>
						<li>
							<uo-icon id="bolt"></uo-icon> <strong><?php echo esc_html_x( 'Action:', 'Mailchimp', 'uncanny-automator' ); ?></strong> <?php echo esc_html_x( 'Create and send a campaign', 'Mailchimp', 'uncanny-automator' ); ?>
						</li>
						<li>
							<uo-icon id="bolt"></uo-icon> <strong><?php echo esc_html_x( 'Action:', 'Mailchimp', 'uncanny-automator' ); ?></strong> <?php echo esc_html_x( 'Remove a tag from the user', 'Mailchimp', 'uncanny-automator' ); ?>
						</li>
						<li>
							<uo-icon id="bolt"></uo-icon> <strong><?php echo esc_html_x( 'Action:', 'Mailchimp', 'uncanny-automator' ); ?></strong> <?php echo esc_html_x( 'Unsubscribe the user from an audience', 'Mailchimp', 'uncanny-automator' ); ?>
						</li>
					</ul>

				<?php } ?>

			</div>

		</div>

		<div class="uap-settings-panel-bottom">

				<?php if ( ! $this->is_connected ) { ?>

					<uo-button href="<?php echo esc_url( $connect_uri ); ?>" target="_self" unsafe-force-target>
						<?php echo esc_html_x( 'Connect Mailchimp account', 'Mailchimp', 'uncanny-automator' ); ?>
					</uo-button>

				<?php } else { ?>

					<div class="uap-settings-panel-bottom-left">

						<div class="uap-settings-panel-user">

							<div class="uap-settings-panel-user__avatar">

								<?php if ( isset( $this->client->login->avatar ) ) { ?>

									<img src="<?php echo esc_url( $this->client->login->avatar ); ?>" alt="<?php echo esc_url( $this->client->login->login_name[0] ); ?>" />
							   
								<?php } else { ?>

									<?php echo esc_html( strtoupper( $this->client->login->login_name[0] ) ); ?>

								<?php } ?>

							</div>

							<div class="uap-settings-panel-user-info">

								<div class="uap-settings-panel-user-info__main">
									<?php echo esc_html( $this->client->login->login_name ); ?>

									<uo-icon integration="MAILCHIMP"></uo-icon>

								</div>

								<div class="uap-settings-panel-user-info__additional">
									<?php echo esc_html( $this->client->login->email ); ?>
								</div>

							</div>

						</div>

					</div>

					<div class="uap-settings-panel-bottom-right">

						<uo-button color="danger" href="<?php echo esc_url( $disconnect_uri ); ?>">

							<uo-icon id="right-from-bracket"></uo-icon>

							<?php echo esc_html_x( 'Disconnect', 'Mailchimp', 'uncanny-automator' ); ?>

						</uo-button>

						<uo-button type="submit">

							<?php echo esc_html_x( 'Save settings', 'Mailchimp', 'uncanny-automator' ); ?>

						</uo-button>

					</div>

				<?php } ?>

		</div>

	</div>

</form>
