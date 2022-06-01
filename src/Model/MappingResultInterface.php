<?php

namespace OidcRoles\Model;

/**
 * Interface MappingResultInterface.
 *
 * @package OidcRoles\Model
 */
interface MappingResultInterface {

	/**
	 * Result of the comparison.
	 *
	 * @return bool
	 */
	public function success();

	/**
	 * Set the result of the comparison.
	 *
	 * @param bool $result
	 *   True if the comparison was successful, otherwise false.
	 *
	 * @return \OidcRoles\Model\MappingResultInterface
	 */
	public function setSuccess( bool $result );

	/**
	 * Description of the comparison result.
	 *
	 * @return string
	 *   Human readable explanation of the result or errors.
	 */
	public function description();

	/**
	 * Set the description.
	 *
	 * @param string $description
	 *   String that explains the result, or error messages.
	 *
	 * @return \OidcRoles\Model\MappingResultInterface
	 */
	public function setDescription( string $description );

}
