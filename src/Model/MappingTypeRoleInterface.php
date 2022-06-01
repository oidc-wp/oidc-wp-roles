<?php

namespace OidcRoles\Model;

use OidcRoles\Service\ComparisonInterface;

/**
 * Interface MappingInterface.
 *
 * @package OidcRoles\Model
 */
interface MappingTypeRoleInterface extends MappingTypeInterface {

	/**
	 * Set the instance of the service that compares values.
	 *
	 * @param \OidcRoles\Service\ComparisonInterface $comparison
	 *   Comparison service.
	 */
	public function setComparison( ComparisonInterface $comparison );

}
