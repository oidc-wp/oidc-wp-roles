<?php

namespace OidcRoles\Service;

use OidcRoles\Exception\InvalidMappingException;
use OidcRoles\Model\ConnectionMappingsCollection;
use OidcRoles\Model\MappingTypeField;
use OidcRoles\Model\MappingTypeInterface;
use OidcRoles\Model\MappingTypeRole;
use Psr\Log\LoggerInterface;

/**
 * Class MappingsManager.
 *
 * @package OidcRoles\Service
 */
class MappingsManager implements MappingsManagerInterface {

	/**
	 * Connection settings.
	 *
	 * @var \OidcRoles\Service\SettingsInterface
	 */
	protected $connectionSettings;

	/**
	 * Role mapping settings.
	 *
	 * @var \OidcRoles\Service\SettingsInterface
	 */
	protected $roleMappingSettings;

	/**
	 * Field mapping settings.
	 *
	 * @var \OidcRoles\Service\SettingsInterface
	 */
	protected $fieldMappingSettings;

	/**
	 * Data finder service.
	 *
	 * @var \OidcRoles\Service\DataFinderInterface
	 */
	protected $dataFinder;

	/**
	 * Comparison service.
	 *
	 * @var \OidcRoles\Service\ComparisonInterface
	 */
	protected $comparison;

	/**
	 * Connection client factory.
	 *
	 * @var \OidcRoles\Service\ConnectionClientFactoryInterface
	 */
	protected $connectionClientFactory;

	/**
	 * Logger.
	 *
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $logger;

	/**
	 * WordPress user instance.
	 *
	 * @var \WP_User
	 */
	protected $user;

	/**
	 * MappingsManager constructor.
	 *
	 * @param \OidcRoles\Service\SettingsInterface $connection_settings
	 *   Connection settings.
	 * @param \OidcRoles\Service\SettingsInterface $role_mapping_settings
	 *   Role mapping settings.
	 * @param \OidcRoles\Service\SettingsInterface $field_mapping_settings
	 *   Field mapping settings.
	 * @param \OidcRoles\Service\DataFinderInterface $data_finder
	 *   Data finder service.
	 * @param \OidcRoles\Service\ComparisonInterface $comparison
	 *   Comparison service.
	 * @param \OidcRoles\Service\ConnectionClientFactoryInterface $connection_client_factory
	 *   Connection client factory.
	 * @param \Psr\Log\LoggerInterface $logger
	 *   Logger.
	 */
	public function __construct( SettingsInterface $connection_settings, SettingsInterface $role_mapping_settings, SettingsInterface $field_mapping_settings, DataFinderInterface $data_finder, ComparisonInterface $comparison, ConnectionClientFactoryInterface $connection_client_factory, LoggerInterface $logger ) {
		$this->connectionSettings = $connection_settings;
		$this->roleMappingSettings = $role_mapping_settings;
		$this->fieldMappingSettings = $field_mapping_settings;
		$this->dataFinder = $data_finder;
		$this->comparison = $comparison;
		$this->connectionClientFactory = $connection_client_factory;
		$this->logger = $logger;
	}

	/**
	 * @inheritDoc
	 */
	public function setUser( \WP_User $user ) {
		$this->user = $user;
	}

	/**
	 * @inheritDoc
	 */
	public function getConnectionClientMappingCollections() {
		$mappings = [];
		foreach ( $this->connectionSettings->get( 'connections', [] ) as $connection ) {
			$mappings[ $connection['name'] ] = new ConnectionMappingsCollection( $this->connectionClientFactory->createClientByName( $connection['name'] ) );
		}
		foreach ( $this->roleMappingSettings->get( 'role_mapping', [] ) as $mapping ) {
			if ( $mappings[ $mapping['connection'] ] ) {
				$mappings[ $mapping['connection'] ]->addRoleMapping( new MappingTypeRole( $mapping, $this->dataFinder, $this->comparison, $this->user ) );
			}
		}
		foreach ( $this->fieldMappingSettings->get( 'field_mapping', [] ) as $mapping ) {
			if ( $mappings[ $mapping['connection'] ] ) {
				$mappings[ $mapping['connection'] ]->addFieldMapping( new MappingTypeField( $mapping, $this->dataFinder, $this->user ) );
			}
		}
		return $mappings;
	}

	/**
	 * @inheritDoc
	 */
	public function getMappingsResults( array $data, array $mappings ) {
		$values = [];
		if ( ! reset( $mappings ) instanceof MappingTypeInterface ) {
			return $values;
		}

		foreach ( $mappings as $mapping ) {
			if ( ! $mapping->isValid() ) {
				throw new InvalidMappingException( \strtr( __( 'Mapping invalid: %class', 'oidc-wp-roles' ), [
					'%class' => \get_class( $mapping ),
				] ) );
			}

			$values[] = $mapping->getMappingResult( $data );
		}

		return $values;
	}

}
