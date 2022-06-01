<?php

namespace OidcRoles\Model;

/**
 * Interface ComparisonResultInterface.
 *
 * @package OidcRoles\Model
 */
interface MappingComparisonResultInterface extends MappingResultInterface {

	/**
	 * Value to test against the comparisonValue().
	 *
	 * @return mixed|null
	 *   Value found, null if not found.
	 */
	public function testValue();

	/**
	 * Operator to use for the comparison.
	 *
	 * @return string
	 */
	public function operator();

	/**
	 * Value to compare to the testValue().
	 *
	 * @return mixed
	 */
	public function comparisonValue();

}
