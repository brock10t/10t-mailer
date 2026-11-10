<?php
/**
 * Plugin administration page.
 *
 * @package TenT_Mailer
 */

namespace TenT\Mailer\Admin;

/**
 * Registers and renders the plugin page beneath the Settings menu.
 */
final class SettingsPage {

	/**
	 * Registers WordPress hooks for the settings page.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
	}

	/**
	 * Adds the page to the Settings menu.
	 *
	 * @return void
	 */
	public function add_page(): void {
		add_options_page(
			esc_html__( '10T Mailer Settings', '10t-mailer' ),
			esc_html__( '10T Mailer', '10t-mailer' ),
			'manage_options',
			'10t-mailer',
			array( $this, 'render' )
		);
	}

	/**
	 * Renders the settings page placeholder.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have permission to manage 10T Mailer.', '10t-mailer' ),
				esc_html__( 'Access denied', '10t-mailer' ),
				array( 'response' => 403 )
			);
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p>
				<?php esc_html_e( '10T Mailer is installed. Configuration options will be added as development continues.', '10t-mailer' ); ?>
			</p>
		</div>
		<?php
	}
}
