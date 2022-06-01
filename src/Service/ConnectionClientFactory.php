<?php

namespace OidcRoles\Service;

use OidcRoles\Model\ConnectionClient;

/**
 * Class ConnectionClientFactory.
 *
 * @package OidcRoles\Service
 */
class ConnectionClientFactory implements ConnectionClientFactoryInterface {

	/**
	 * Connection settings.
	 *
	 * @var \OidcRoles\Service\SettingsInterface
	 */
	private $connectionSettings;

	/**
	 * ConnectionClientFactory constructor.
	 *
	 * @param \OidcRoles\Service\SettingsInterface $connection_settings
	 *   Connection settings.
	 */
	public function __construct( SettingsInterface $connection_settings ) {
		$this->connectionSettings = $connection_settings;
	}

	/**
	 * {@inheritDoc}
	 */
	public function createClientByName( string $name, array $client_settings_override = [] ) {
		$connections = $this->connectionSettings->get( 'connections', [] );
		$client_settings = [];
		foreach ( $connections as $connection ) {
			if ( $connection['name'] == $name ) {
				$client_settings = $connection;
				break;
			}
		}

		if ( empty ( $client_settings ) ) {
			throw new \RuntimeException( __( "oidc_wp_roles connection by the name of {$name} does not exist.", 'oidc-wp-roles' ) );
		}

		$client_settings = \array_replace( $client_settings, $client_settings_override );
		return new ConnectionClient( $client_settings );
	}

	/**
	 * {@inheritDoc}
	 */
	public function createClient( array $client_settings ) {
		return new ConnectionClient( $client_settings );
	}

}
