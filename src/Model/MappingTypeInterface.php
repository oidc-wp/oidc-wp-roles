<?php

namespace OidcRoles\Model;

use OidcRoles\Service\DataFinderInterface;

/**
 * Interface MappingTypeInterface.
 *
 * @package OidcRoles\Model
 */
interface MappingTypeInterface {

	/**
	 * Set the instance of the service that finds data in connection responses.
	 *
	 * @param \OidcRoles\Service\DataFinderInterface $data_finder
	 *   Data finder service.
	 */
	public function setDataFinder( DataFinderInterface $data_finder );

	/**
	 * Get the user this mapping targets.
	 *
	 * @return \WP_User
	 *   WordPress user this mapping targets.
	 */
	public function getUser();

	/**
	 * Set the user that will be affected by the mapping.
	 *
	 * @param \WP_User $user
	 *   WordPress user to map data to.
	 */
	public function setUser( \WP_User $user );

	/**
	 * Whether or not this mapping has all required properties.
	 *
	 * @return bool
	 *   True if valid and ready, false otherwise.
	 */
	public function isValid();

	/**
	 * Whether or not this mapping as a value for the given name.
	 *
	 * @param string $name
	 *   Mapping setting name.
	 *
	 * @return bool
	 *   True if the mapping has a value of the given name, otherwise false.
	 */
	public function has( string $name );

	/**
	 * Get the value of a mapping value.
	 *
	 * @param string $name
	 *   Mapping value name.
	 * @param null $default
	 *   Default value if none found.
	 *
	 * @return mixed
	 *   Named value if found, otherwise the $default value.
	 */
	public function get( string $name, $default = NULL );

	/**
	 * Get the mapping's value from the provided data set.
	 *
	 * @param array $data
	 *   Data to find value within.
	 *
	 * @return mixed|null
	 *   Value found, null if not found.
	 */
	public function getDataValue( array $data );

	/**
	 * Get the results of the comparison.
	 *
	 * @param array $data
	 *   Data to find test value within.
	 *
	 * @return \OidcRoles\Model\MappingResultInterface
	 *   Instance of a comparison result object.
	 */
	public function getMappingResult( array $data );

	/**
	 * Perform the mapping.
	 *
	 * @param array $data
	 *   Data to find test value within.
	 * @param \OidcRoles\Model\MappingResultInterface[] $results_so_far
	 *   Results of other mappings that have been performed.
	 *
	 * @return bool
	 *   Success or not.
	 */
	public function performMapping( array $data, array $results_so_far = [] );

}
