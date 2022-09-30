<?php

namespace OidcRoles\Service;

/**
 * Class DataFinder.
 *
 * @package OidcRoles\Service
 */
class DataFinder implements DataFinderInterface {

	/**
	 * {@inheritdoc}
	 */
	public function getPathValue( array $data, string $path ) {
		$parts = array_filter( explode( '/', $path ), function( $part ) {
			return $part !== '';
		} );

		$value = $data;
		while ( !empty( $parts ) ) {
			$part = array_shift( $parts );
			if ( isset( $value[ $part ] ) ) {
				$value = $value[ $part ];
				continue;
			}

			// If we found something that isn't an array, then that's as close as
			// we can get to the value.
			break;
		}

		// If the $parts array is empty, then we found the path value.
		if ( empty( $parts ) ) {
			return $value;
		}

		return NULL;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getComparisonRow( array $rows, string $row_key, string $row_value ) {
		foreach ( $rows as $row ) {
			if ( isset( $row[ $row_key ] ) && $row[ $row_key ] == $row_value ) {
				return $row;
			}
		}

		return NULL;
	}

}
