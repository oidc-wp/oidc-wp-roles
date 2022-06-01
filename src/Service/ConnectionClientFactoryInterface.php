<?php

namespace OidcRoles\Service;

/**
 * Interface ConnectionClientFactoryInterface.
 *
 * @package OidcRoles\Service
 */
interface ConnectionClientFactoryInterface {

	/**
	 * Factory method to create an instance of a connection client by name.
	 *
	 * @param string $name
	 *   Name of the connection.
	 * @param array $client_settings_override
	 *   Connection settings values to override.
	 *
	 * @return \OidcRoles\Model\ConnectionClientInterface
	 *   Client instance.
	 */
	public function createClientByName( string $name, array $client_settings_override = [] );

	/**
	 * Create a connection client instance.
	 *
	 * @param array $client_settings
	 *   Client settings array.
	 *
	 * @return \OidcRoles\Model\ConnectionClientInterface
	 *   Client instance.
	 */
	public function createClient( array $client_settings );

}
