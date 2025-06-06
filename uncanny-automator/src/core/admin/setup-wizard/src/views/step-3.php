<?php
/**
 * Step 3 Template file.
 */
?>
<div class="center row-1">
	<div class="automator-setup-wizard__branding">
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

	<h2 class="title">
		<?php esc_html_e( 'Setup complete!', 'uncanny-automator' ); ?>
	</h2>
	<h3>
		<?php esc_html_e( 'Watch a quick intro video', 'uncanny-automator' ); ?>
	</h3>

	<p>
		<iframe width="490" height="250" src="https://www.youtube.com/embed/LMR5YIPu2Kk" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
	</p>

	<p>
		<uo-button
			href="<?php echo esc_url( admin_url( 'post-new.php' ) . '?post_type=uo-recipe' ); ?>"
		>
			<?php esc_html_e( 'Create my first recipe', 'uncanny-automator' ); ?>
		</uo-button>

		<uo-button
			href="<?php echo esc_url( admin_url( 'edit.php' ) . '?post_type=uo-recipe&page=uncanny-automator-dashboard' ); ?>"
			color="secondary"
			style="margin-left: 10px;"
		>
			<?php esc_html_e( 'Return to dashboard', 'uncanny-automator' ); ?>
		</uo-button>
	</p>

</div>
