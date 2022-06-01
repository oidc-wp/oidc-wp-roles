<?php

namespace OidcRoles\Model;

/**
 * Interface ConnectionMappingsCollectionInterface.
 *
 * @package OidcRoles\Model
 */
interface ConnectionMappingsCollectionInterface {

	/**
	 * Set the connection client instance on this collection.
	 *
	 * @param \OidcRoles\Model\ConnectionClientInterface $client
	 *   Client instance.
	 */
	public function setClient( ConnectionClientInterface $client );

	/**
	 * Get the connection client instance.
	 *
	 * @return \OidcRoles\Model\ConnectionClientInterface
	 */
	public function getClient();

	/**
	 * Add a role mapping to the collection.
	 *
	 * @param \OidcRoles\Model\MappingTypeRoleInterface $role_mapping
	 */
	public function addRoleMapping( MappingTypeRoleInterface $role_mapping );

	/**
	 * Get all role mappings in the collection.
	 *
	 * @return \OidcRoles\Model\MappingTypeRoleInterface[]
	 */
	public function getRoleMappings();

	/**
	 * Add a field mapping to the collection.
	 *
	 * @param \OidcRoles\Model\MappingTypeFieldInterface $field_mapping
	 */
	public function addFieldMapping( MappingTypeFieldInterface $field_mapping );

	/**
	 * Get all field mappings in the collection.
	 *
	 * @return \OidcRoles\Model\MappingTypeFieldInterface[]
	 */
	public function getFieldMappings();

}
