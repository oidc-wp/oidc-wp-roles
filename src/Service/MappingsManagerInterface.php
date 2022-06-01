<?php

namespace OidcRoles\Service;

/**
 * Interface MappingsManagerInterface.
 *
 * @package OidcRoles\Service
 */
interface MappingsManagerInterface {

	/**
	 * Set the user for the mapping manager. This user will be passed into each
	 * mapping.
	 *
	 * @param \WP_User $user
	 */
	public function setUser( \WP_User $user );

	/**
	 * Get a collection of all role and field mappings grouped by their
	 * connection.
	 *
	 * @return \OidcRoles\Model\ConnectionMappingsCollectionInterface[]
	 */
	public function getConnectionClientMappingCollections();

	/**
	 * Get mapping result objects from the given data and array of mappings.
	 *
	 * @param array $data
	 *   Array of data from a connection client response.
	 * @param \OidcRoles\Model\MappingTypeInterface[] $mappings
	 *   Array of mappings to apply to the given data.
	 *
	 * @return \OidcRoles\Model\MappingResultInterface[]
	 *   Array of mapping result objects.
	 */
	public function getMappingsResults( array $data, array $mappings );

}
