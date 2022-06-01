<?php

namespace OidcRoles\Admin;

use DI\Container;

/**
 * Class SettingsTabGeneral.
 *
 * @package OidcRoles\Admin
 */
class SettingsTabGeneral extends SettingsTabBase {

	/**
	 * {@inheritdoc}
	 */
	public function id() {
		return 'oidc_wp_roles_general_settings';
	}

	/**
	 * {@inheritdoc}
	 */
	public function title() {
		return __( 'General', 'oidc-wp-roles' );
	}

	/**
	 * Registers General options tab and main admin menu item
	 */
	public function register( Container $container ) {
		$args = $this->parseMetaBoxArgs( [
			'id' => $this->id(),
			'tab_title' => $this->title(),
			// General tab is the parent.
			'parent_slug'  => null,
		] );

		$meta_box = new_cmb2_box( $args );

		$meta_box->add_field( [
			'name' => __( 'Log Level', 'oidc-wp-roles' ),
			'desc' => __( 'Frequency of logging.', 'oidc-wp-roles' ),
			'id'   => 'log_level',
			'type' => 'select',
			'default' => 'info',
			'options' => [
				'disabled' => __( 'Disabled', 'oidc-wp-roles' ),
				'error' => __( 'Errors only', 'oidc-wp-roles' ),
				'info' => __( 'Info (Default)', 'oidc-wp-roles' ),
				'debug' => __( 'Debug (Verbose)', 'oidc-wp-roles' ),
			],
			'attributes' => [
				'required' => 'required',
			],
		] );
		$log_limits = [ 1000, 5000, 10000, 25000 ];
		$meta_box->add_field( [
			'name' => __( 'Log Limit', 'oidc-wp-roles' ),
			'desc' => __( 'Number of logs to keep. Once the limit is reached, older logs will be deleted.', 'oidc-wp-roles' ),
			'id'   => 'log_limit',
			'type' => 'select',
			'default' => 10000,
			'options' => array_combine( $log_limits, $log_limits ),
			'attributes' => [
				'required' => 'required',
			],
		] );
		$meta_box->add_field( [
			'name' => __( 'Login Button Text', 'oidc-wp-roles' ),
			'desc' => __( 'Change the text for the openid-connect-generic login button that appears on the WordPress login screen. Leave blank if you do not want to change the text.', 'oidc-wp-roles' ),
			'id'   => 'login_button_text',
			'type' => 'text',
			'sanitization_cb' => [ $this, 'sanitizeText' ],
		] );
		$meta_box->add_field( [
			'name' => __( 'Cleanup', 'oidc-wp-roles' ),
			'desc' => __( 'Remove roles and settings when deleting plugin.', 'oidc-wp-roles' ),
			'id'   => 'cleanup',
			'type' => 'checkbox',
		] );
	}

	/**
	 * Sanitize text field values before saving.
	 *
	 * @param string $value
	 *   Unsanitized value.
	 *
	 * @return string
	 *   Sanitized value.
	 */
	public function sanitizeText( string $value ) {
		return sanitize_text_field( trim( $value ) );
	}

}
