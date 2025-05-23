<?php
/**
 * Facebook Settings
 * Settings > Premium Integrations > Facebook
 *
 * @since   3.7
 * @version 3.7
 * @package Uncanny_Automator
 *
 * $is_user_connected   Boolean. True if user is connected to Facebook. Otherwise, false.
 * $error_status        URL query parameter for handling error status (e.g. user has cancelled the OAuth dialog).
 * $login_dialog_uri    The Facebook login dialog uri from Automator API.
 * $facebook_user       The Facebook user object from Facebook Graph.
 * $disconnect_uri      The URI for disconnecting the currect Facebook user.
 * $connection          Returns 'new' if user just came back from successful OAuth dialog. Otherwise, null.
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

				<uo-icon integration="FACEBOOK"></uo-icon>

				<?php esc_html_e( 'Facebook Pages', 'uncanny-automator' ); ?>

			</div>

			<div class="uap-settings-panel-content">

				<?php if ( ! $is_user_connected ) { ?>

					<div class="uap-settings-panel-content-subtitle">
						<?php esc_html_e( 'Connect Uncanny Automator to Facebook Pages', 'uncanny-automator' ); ?>
					</div>

					<div class="uap-settings-panel-content-paragraph uap-settings-panel-content-paragraph--subtle">
						<?php esc_html_e( "Use Uncanny Automator to automatically share updates, news and blog posts from your WordPress site to your organization's Facebook Page(s) in the form of posts, images and links.", 'uncanny-automator' ); ?>
					</div>

					<p>
						<strong><?php esc_html_e( 'Activating this integration will enable the following for use in your recipes:', 'uncanny-automator' ); ?></strong>
					</p>

					<ul>
						<li>
							<uo-icon id="bolt"></uo-icon> <strong><?php esc_html_e( 'Action:', 'uncanny-automator' ); ?></strong> <?php esc_html_e( 'Publish a post with an image to a Facebook page', 'uncanny-automator' ); ?>
						</li>
						<li>
							<uo-icon id="bolt"></uo-icon> <strong><?php esc_html_e( 'Action:', 'uncanny-automator' ); ?></strong> <?php esc_html_e( 'Publish a post to a Facebook page', 'uncanny-automator' ); ?>
						</li>
						<li>
							<uo-icon id="bolt"></uo-icon> <strong><?php esc_html_e( 'Action:', 'uncanny-automator' ); ?></strong> <?php esc_html_e( 'Share a link with a message to a Facebook page', 'uncanny-automator' ); ?>
						</li>
					</ul>

				<?php } ?>

				<?php if ( 'new' === $connection ) { ?>
					<uo-alert type="success" heading="<?php esc_attr_e( 'Your Facebook pages have been connected successfully.', 'uncanny-automator' ); ?>"></uo-alert>
				<?php } ?>

				<?php if ( 'error' === $error_status ) { ?>

					<uo-alert type="error" heading="<?php esc_attr_e( 'An error has occured', 'uncanny-automator' ); ?>">
						<?php esc_html_e( 'Permission was denied, or an unexpected error was encountered while authenticating. Please try again later.', 'uncanny-automator' ); ?>
					</uo-alert>

				<?php } ?>

				<?php if ( $is_user_connected ) { ?>

					<div class="uap-settings-panel-content-subtitle uap-spacing-top">
						<?php esc_html_e( 'Linked pages', 'uncanny-automator' ); ?>
					</div>

					<div id="facebook-pages-list"></div>

					<uo-alert type="error" id="facebook-pages-errors" class="uap-spacing-top"></uo-alert>

					<uo-button id="facebook-pages-update-button" class="uap-spacing-top uap-spacing-top--big" href="<?php echo esc_url( $login_dialog_uri ); ?>" color="secondary">
						<?php esc_html_e( 'Update linked pages', 'uncanny-automator' ); ?>
					</uo-button>


				<?php } ?>

			</div>

		</div>

		<div class="uap-settings-panel-bottom">

			<?php if ( ! $is_user_connected ) { ?>

				<uo-button href="<?php echo esc_url( $login_dialog_uri ); ?>" target="_self" unsafe-force-target>

					<?php esc_html_e( 'Connect Facebook account', 'uncanny-automator' ); ?>

				</uo-button>

			<?php } else { ?>

				<div class="uap-settings-panel-bottom-left">

					<div class="uap-settings-panel-user">

						<div class="uap-settings-panel-user__avatar">
							<?php if ( ! empty( $user_info['picture'] ) && ! empty( $user_info['name'] ) ) { ?>
								<img src="<?php echo esc_url( $user_info['picture'] ); ?>" alt="<?php echo esc_attr( $user_info['name'] ); ?>" />
							<?php } ?>

						</div>

						<div class="uap-settings-panel-user-info">

							<div class="uap-settings-panel-user-info__main">

								<?php if ( $user_info['name'] ) { ?>
									<?php echo esc_html( $user_info['name'] ); ?>
									<uo-icon integration="FACEBOOK"></uo-icon>
								<?php } ?>

							</div>

							<div class="uap-settings-panel-user-info__additional">
								<?php
								if ( ! empty( $user_info['user_id'] ) ) {
									echo esc_html(
										sprintf(
											/* translators: 1. ID */
											esc_html__( 'ID: %1$d', 'uncanny-automator' ),
											absint( $user_info['user_id'] )
										)
									);
								}
								?>
							</div>

						</div>

					</div>

				</div>

				<div class="uap-settings-panel-bottom-right">

					<uo-button color="danger" href="<?php echo esc_url( $disconnect_uri ); ?>">

						<uo-icon id="right-from-bracket"></uo-icon>

						<?php esc_html_e( 'Disconnect', 'uncanny-automator' ); ?>

					</uo-button>

				</div>

			<?php } ?>

		</div>

	</div>

</form>
