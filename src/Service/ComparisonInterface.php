<?php

namespace OidcRoles\Service;

/**
 * Interface ComparisonInterface.
 *
 * @package OidcRoles\Service
 */
interface ComparisonInterface {

	/**
	 * Array of comparison operators and their names.
	 *
	 * @return array
	 *   Keys are operators, values are descriptions of those operators.
	 */
	public static function comparisonOperatorOptions();

	/**
	 * Whether the provided operator meant for strict comparison.
	 *
	 * @param string $operator
	 *   Operator string.
	 *
	 * @return bool
	 *   True of the operator denotes a strict comparison, otherwise false.
	 */
	public function isStrictOperator( string $operator );

	/**
	 * Get the comparison value as a strict data type.
	 *
	 * @param string $comparison_value
	 *   Original comparison value.
	 *
	 * @return bool|float|int|string
	 *   Strict data type.
	 */
	public function getStrictComparisonValue( string $comparison_value );

	/**
	 * @param mixed $test_value
	 *   Value to test comparison against.
	 * @param string $operator
	 *   Comparison operator string.
	 * @param mixed $comparison_value
	 *   Value to compare against $test_value.
	 *
	 * @return bool
	 *   Comparison result.
	 */
	public function compare( $test_value, string $operator, $comparison_value );

	/**
	 * Get an array of the comparison values and result.
	 *
	 * @param mixed $test_value
	 *   Value to test comparison against.
	 * @param string $operator
	 *   Comparison operator string.
	 * @param mixed $comparison_value
	 *   Value to compare against $test_value.
	 *
	 * @return \OidcRoles\Model\MappingComparisonResultInterface
	 *   Instance of a comparison result interface.
	 */
	public function getComparisonResult( $test_value, string $operator, $comparison_value );

	/**
	 * Compare values with the provided operator.
	 *
	 * @param mixed $test_value
	 *   Value to test against $comparison_value.
	 * @param string $operator
	 *   Comparison operator string.
	 * @param mixed $comparison_value
	 *   Value to compare against $test_value.
	 *
	 * @return bool
	 *   Result of comparison.
	 */
	public static function compareValues( $test_value, string $operator, $comparison_value );

}
