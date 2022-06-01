<?php

namespace OidcRoles\Service;

use OidcRoles\Model\MappingComparisonResult;

/**
 * Class Comparison.
 *
 * @package OidcRoles\Service
 */
class Comparison implements ComparisonInterface {

	/**
	 * {@inheritdoc}
	 */
	public static function comparisonOperatorOptions() {
		return [
			'=='  => \__( 'Equals (==) - Case Sensitive', 'oidc-wp-roles' ),
			'==insensitive'  => \__( 'Equals (==) - Case Insensitive', 'oidc-wp-roles' ),
			'===' => \__( 'Equals - strict (===)', 'oidc-wp-roles' ),
			'!='  => \__( 'Not equal (!=)', 'oidc-wp-roles' ),
			'!==' => \__( 'Not equal - strict (!==)', 'oidc-wp-roles' ),
			'>'   => \__( 'Greater than (>)', 'oidc-wp-roles' ),
			'>='  => \__( 'Greater than or equal to (>=)', 'oidc-wp-roles' ),
			'<'   => \__( 'Less than (<)', 'oidc-wp-roles' ),
			'<='  => \__( 'Less than or equal to (<=)', 'oidc-wp-roles' ),
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function isStrictOperator( string $operator ) {
		return ! \in_array( $operator, [
			'==',
			'!=',
		] );
	}

	/**
	 * {@inheritdoc}
	 */
	public function getStrictComparisonValue( string $comparison_value ) {
		if ( \is_numeric( $comparison_value ) ) {
			if ( \strpos( $comparison_value, '.' ) !== FALSE ) {
				return (float) $comparison_value;
			}

			return (int) $comparison_value;
		}

		if ( \strtolower( $comparison_value ) == 'true' ) {
			return TRUE;
		}
		else if ( \strtolower( $comparison_value ) == 'false' ) {
			return FALSE;
		}

		return $comparison_value;
	}

	/**
	 * {@inheritdoc}
	 */
	public function compare( $test_value, string $operator, $comparison_value ) {
		if ( $this->isStrictOperator( $operator ) ) {
			$comparison_value = $this->getStrictComparisonValue( $comparison_value );
		}

		return $this::compareValues( $test_value, $operator, $comparison_value );
	}

	/**
	 * {@inheritdoc}
	 */
	public function getComparisonResult( $test_value, string $operator, $comparison_value ) {
		$value = $this->isStrictOperator( $operator ) ? $this->getStrictComparisonValue( $comparison_value ) : $comparison_value;
		return new MappingComparisonResult( $test_value, $operator, $value, $this->compare( $test_value, $operator, $comparison_value ) );
	}

	/**
	 * {@inheritdoc}
	 */
	public static function compareValues( $test_value, string $operator, $comparison_value ) {
		switch ( $operator ) {
			case '===':
				return $test_value === $comparison_value;
			case '!==':
				return $test_value !== $comparison_value;
			case '>':
				return $test_value > $comparison_value;
			case '>=':
				return $test_value >= $comparison_value;
			case '<':
				return $test_value < $comparison_value;
			case '<=':
				return $test_value <= $comparison_value;
			case '!=':
				return $test_value != $comparison_value;
			case '==insensitive':
				if (is_string($test_value) && is_string($comparison_value)) {
					$test_value = strtolower($test_value);
					$comparison_value = strtolower($comparison_value);
				}
				return $test_value == $comparison_value;
			case '==':
			default:
				return $test_value == $comparison_value;
		}
	}

}
