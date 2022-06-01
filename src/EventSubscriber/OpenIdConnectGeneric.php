<?php

namespace OidcRoles\EventSubscriber;

use OidcRoles\Service\SettingsInterface;

/**
 * Class OpenIdConnectGeneric.
 *
 * @package OidcRoles\EventSubscriber
 */
class OpenIdConnectGeneric {

	/**
	 * General settings.
	 *
	 * @var \OidcRoles\Service\SettingsInterface
	 */
	private $generalSettings;

	/**
	 * OpenIdConnectGeneric constructor.
	 *
	 * @param \OidcRoles\Service\SettingsInterface $general_settings
	 *   General settings.
	 */
	private function __construct( SettingsInterface $general_settings ) {
		$this->generalSettings = $general_settings;
	}

	/**
	 * Register event subscribers.
	 *
	 * @param \OidcRoles\Service\SettingsInterface $general_settings
	 *   General settings.
	 */
	public static function register( SettingsInterface $general_settings ) {
		$self = new self( $general_settings );

		if ( ! empty( trim( $general_settings->get( 'login_button_text' ) ) ) ) {
			add_filter( 'openid-connect-generic-login-button-text', [ $self, 'loginbuttonText' ] );
		}
	}

	/**
	 * Change the login button text.
	 *
	 * @param string $text
	 *   Current button text value.
	 *
	 * @return string
	 *   New button text value.
	 */
	public function loginbuttonText( string $text ) {
		return $this->generalSettings->get( 'login_button_text' );
	}

}
