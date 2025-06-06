<?php
/**
 * Step 2 template file.
 */
?>
<div class="automator-setup-wizard-step-2-wrap">
	<div class="center automator-setup-wizard__branding">
		<img width="380" src="<?php echo esc_url( Uncanny_Automator\Utilities::automator_get_asset( 'build/img/logo-horizontal.svg' ) ); ?>" alt="" />
	</div>
	<div class="automator-setup-wizard__steps">
		<div class="automator-setup-wizard__steps__inner-wrap">
			<ol>
				<?php foreach ( $this->get_steps() as $step ) : ?>
					<li class="<?php echo implode( ' ', $step['classes'] ); ?>"> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span>
							<?php // translators: The step ?>
							<?php echo sprintf( esc_html__( 'Step %s', 'uncanny-automator' ), esc_html( $step['label'] ) ); ?>
						</span>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</div>

	<?php if ( $this->is_user_connected() ) : ?>

		<div class="center row-1">
			<h2 class="title">
				<?php esc_html_e( 'Connected successfully!', 'uncanny-automator' ); ?>
			</h2>
			<p>
				<?php esc_html_e( 'You may now connect your recipes to any service supported by Uncanny Automator.', 'uncanny-automator' ); ?>
			</p>
		</div>

		<div class="row-2">

			<h4>
				<?php esc_html_e( 'Help us make Uncanny Automator even better!', 'uncanny-automator' ); ?>
			</h4>

			<p>
				<?php esc_html_e( 'Tracking of anonymous usage data helps us decide where to focus our development efforts.', 'uncanny-automator' ); ?>
			</p>

			<p style="margin-top: 20px;">
				<uo-button
					href="<?php echo esc_url( $this->get_dashboard_uri( 3 ) ); ?>"
					color="secondary"
					>
					<?php esc_html_e( 'Maybe later!', 'uncanny-automator' ); ?>
				</uo-button>

				<uo-button
					href="<?php echo esc_url( $this->get_dashboard_uri( 3 ) ); ?>"
					style="margin-left: 10px;"
					>
					<?php esc_html_e( 'Count me in!', 'uncanny-automator' ); ?>
				</uo-button>
			</p>

		</div>

	<?php else : ?>
		<?php // Not connected. ?>
		<div class="center row-1">
			<h2 class="title">
				<?php esc_html_e( 'Not connected', 'uncanny-automator' ); ?>
			</h2>
			<?php $error_message = get_transient( 'automator_setup_wizard_error' ); ?>
			<?php if ( ! empty( $error_message ) && ! isset( $_GET['skip'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<h3 style="color:#e94b35">
					<?php echo esc_html( get_transient( 'automator_setup_wizard_error' ) ); ?>
				</h3>
			<?php } ?>
			<p>
				<?php
					esc_html_e(
						'Your site is not connected to an Uncanny Automator account.
                    	You can still create recipes (automations) with any of our built-in integrations.
                    	To use app integrations (like Facebook, Slack, MailChimp and more), connect
                    	your site with a free Uncanny Automator account.',
						'uncanny-automator'
					);
				?>
			</p>
			<p>
				<uo-button
					href="<?php echo esc_url( $this->get_connect_button_uri() ); ?>"
					unsafe-force-target
					target="_self"
				>
					<?php esc_html_e( 'Connect your free account!', 'uncanny-automator' ); ?>
				</uo-button>
			</p>
		</div>

		<div class="row-2">

			<h3>
				<?php esc_html_e( 'Help us make Uncanny Automator even better!', 'uncanny-automator' ); ?>
			</h3>

			<p>
				<?php esc_html_e( 'Tracking of anonymous usage data helps us decide where to focus our development efforts.', 'uncanny-automator' ); ?>
			</p>

			<p style="margin-top: 20px;">

				<uo-button
					href="<?php echo esc_url( $this->get_dashboard_uri( 3 ) ); ?>"
					color="secondary"
					>
					<?php esc_html_e( 'Maybe later!', 'uncanny-automator' ); ?>
				</uo-button>

				<uo-button
					href="<?php echo esc_url( $this->get_dashboard_uri( 3 ) ); ?>"
					style="margin-left: 10px;"
					>
					<?php esc_html_e( 'Count me in!', 'uncanny-automator' ); ?>
				</uo-button>
			</p>

		</div>
	<?php endif; ?>

	<?php delete_transient( 'automator_setup_wizard_error' ); ?>

</div>
