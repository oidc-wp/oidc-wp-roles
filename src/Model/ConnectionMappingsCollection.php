<?php

namespace OidcRoles\Model;

/**
 * Class ConnectionMappingsCollection.
 *
 * @package OidcRoles\Model
 */
class ConnectionMappingsCollection implements ConnectionMappingsCollectionInterface {

	/**
	 * Connection client.
	 *
	 * @var \OidcRoles\Model\ConnectionClientInterface
	 */
	private $client;

	/**
	 * Array of role mappings.
	 *
	 * @var \OidcRoles\Model\MappingTypeRoleInterface[]
	 */
	private $roleMappings = [];

	/**
	 * Array of filed mappings.
	 *
	 * @var \OidcRoles\Model\MappingTypeFieldInterface[]
	 */
	private $fieldMappings = [];

	/**
	 * ConnectionMappingsCollection constructor.
	 *
	 * @param \OidcRoles\Model\ConnectionClientInterface|null $client
	 *   Connection client.
	 */
	public function __construct( ConnectionClientInterface $client = NULL ) {
		if ( $client ) {
			$this->setClient( $client );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function setClient( ConnectionClientInterface $client ) {
		$this->client = $client;
	}

	/**
	 * @inheritDoc
	 */
	public function getClient() {
		return $this->client;
	}

	/**
	 * @inheritDoc
	 */
	public function addRoleMapping( MappingTypeRoleInterface $role_mapping ) {
		$this->roleMappings[] = $role_mapping;
	}

	/**
	 * @inheritDoc
	 */
	public function addFieldMapping( MappingTypeFieldInterface $field_mapping ) {
		$this->fieldMappings[] = $field_mapping;
	}

	/**
	 * @inheritDoc
	 */
	public function getRoleMappings() {
		return $this->roleMappings;
	}

	/**
	 * @inheritDoc
	 */
	public function getFieldMappings() {
		return $this->fieldMappings;
	}

}
