<?php

namespace OidcRoles\Admin;

/**
 * Class SettingsTabBase.
 *
 * @package OidcRoles\Admin
 */
abstract class SettingsTabBase {

	/**
	 * Unique slug for the tab.
	 *
	 * @return string
	 */
	abstract function id();

	/**
	 * Tab title.
	 *
	 * @return string
	 */
	abstract function title();

	/**
	 * Get the user-specific option key where a message can be stored for the next pageload
	 * @return string
	 */
	protected function getMessageOptionName() {
		return sprintf(
			'%s_message_%d',
			$this->id(),
			get_current_user_id()
		);
	}

	/**
	 * Get the message to show the user
	 * @return array
	 *   - text
	 *   - type
	 */
	protected function getMessage() {
		return get_option( $this->getMessageOptionName(), [] );
	}

	/**
	 * Set the message to show the user
	 * @param array $message
	 *   - text
	 *   - type
	 */
	protected function setMessage( $message ) {
		update_option( $this->getMessageOptionName(), $message );
	}

	/**
	 * Delete the user message (e.g. after it's been rendered)
	 */
	protected function deleteMessage() {
		delete_option( $this->getMessageOptionName() );
	}

	/**
	 * Fills in meta box arguments that are the same for each group (tab) of settings
	 *
	 * @param $args
	 *   Required args:
	 *   - (string) id          The admin page ID (URL query arg)
	 *   - (string) menu_title  The title of the menu item for this settings group
	 *   - (string) tab_title   The title of the tab for this settings group
	 *
	 *   Optional args:
	 *   - (string) option_key  The name of the option to save in the DB for this tab; Default is the value of $id
	 *
	 *   Other items in $default_args can be overwritten as well
	 */
	protected function parseMetaBoxArgs( array $args ) {
		$default_args = [
			'title'        => __( 'OpenID Connect - WordPress Roles', 'oidc-wp-roles' ),
			'object_types' => [ 'options-page' ],
			// Don't add tabs to admin menu.
			'parent_slug'  => 'admin.php?page=oidc_wp_roles',
			'tab_group'    => 'oidc_wp_roles_settings',
			'display_cb'   => [ $this, 'displayAsTab' ],
		];

		if( empty( $args['option_key'] ) || ! empty( $args['id'] ) ) {
			$args['option_key'] = $args['id'];
		}

		return wp_parse_args( $args, $default_args );
	}

	/**
	 * Gets navigation tabs array for CMB2 options pages which share the given
	 * display_cb param.
	 *
	 * @param \CMB2_Options_Hookup $cmb_options The CMB2_Options_Hookup object.
	 *
	 * @return array Array of tab information.
	 */
	public function getTabs( $cmb_options ) {
		$tab_group = $cmb_options->cmb->prop( 'tab_group' );
		$tabs      = array();

		foreach ( \CMB2_Boxes::get_all() as $cmb_id => $cmb ) {
			if ( $tab_group === $cmb->prop( 'tab_group' ) ) {
				$tabs[ $cmb->options_page_keys()[0] ] = $cmb->prop( 'tab_title' )
					? $cmb->prop( 'tab_title' )
					: $cmb->prop( 'title' );
			}
		}

		return $tabs;
	}

	/**
	 * A CMB2 options-page display callback override which adds tab navigation among
	 * CMB2 options pages which share this same display callback.
	 *
	 * @param \CMB2_Options_Hookup $cmb_options The CMB2_Options_Hookup object.
	 *
	 */
	public function displayAsTab( $cmb_options, string $before_box = '' ) {
		$tabs = $this->getTabs( $cmb_options );
		?>
		<div class="wrap cmb2-options-page option-<?php echo $cmb_options->option_key; ?>">
			<?php if ( get_admin_page_title() ) : ?>
				<h2><?php echo wp_kses_post( get_admin_page_title() ); ?></h2>
			<?php endif; ?>
			<h2 class="nav-tab-wrapper">
				<?php foreach ( $tabs as $option_key => $tab_title ) : ?>
					<a class="nav-tab<?php if ( isset( $_GET['page'] ) && $option_key === $_GET['page'] ) : ?> nav-tab-active<?php endif; ?>" href="<?php menu_page_url( $option_key ); ?>"><?php echo wp_kses_post( $tab_title ); ?></a>
				<?php endforeach; ?>
			</h2>
			<form class="cmb-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST" id="<?php echo $cmb_options->cmb->cmb_id; ?>" enctype="multipart/form-data" encoding="multipart/form-data">
				<input type="hidden" name="action" value="<?php echo esc_attr( $cmb_options->option_key ); ?>">
				<?php if ( ! empty( $before_box ) ) { echo $before_box; } ?>
				<?php $cmb_options->options_page_metabox(); ?>
				<?php submit_button( esc_attr( $cmb_options->cmb->prop( 'save_button' ) ), 'primary', 'submit-cmb' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Callback to define the options-saved message.
	 *
	 * @param \CMB2  $cmb The CMB2 object.
	 * @param array $args {
	 *     An array of message arguments
	 *
	 *     @type bool   $is_options_page Whether current page is this options page.
	 *     @type bool   $should_notify   Whether options were saved and we should be notified.
	 *     @type bool   $is_updated      Whether options were updated with save (or stayed the same).
	 *     @type string $setting         For add_settings_error(), Slug title of the setting to which
	 *                                   this error applies.
	 *     @type string $code            For add_settings_error(), Slug-name to identify the error.
	 *                                   Used as part of 'id' attribute in HTML output.
	 *     @type string $message         For add_settings_error(), The formatted message text to display
	 *                                   to the user (will be shown inside styled `<div>` and `<p>` tags).
	 *                                   Will be 'Settings updated.' if $is_updated is true, else 'Nothing to update.'
	 *     @type string $type            For add_settings_error(), Message type, controls HTML class.
	 *                                   Accepts 'error', 'updated', '', 'notice-warning', etc.
	 *                                   Will be 'updated' if $is_updated is true, else 'notice-warning'.
	 * }
	 */
	public function filterCmbMessage( $cmb, $args ) {
		if ( ! empty( $args['should_notify'] ) ) {

			if ( $args['is_updated'] ) {
				$message = $this->getMessage();

				if( isset( $message['text'] ) ) {
					$args['message'] = $message['text'];
				}
				if( isset( $message['type'] ) ) {
					$args['type'] = $message['type'];
				}

				$this->deleteMessage();
			}

			add_settings_error( $args['setting'], $args['code'], $args['message'], $args['type'] );
		}
	}

}
