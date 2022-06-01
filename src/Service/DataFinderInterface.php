<?php

namespace OidcRoles\Service;

/**
 * Interface DataFinderInterface.
 *
 * @package OidcRoles\Service
 */
interface DataFinderInterface {

	/**
	 * Get the value within the data set at the given path.
	 *
	 * @param array $data
	 *   Data set to traverse.
	 * @param string $path
	 *   Path within data set to search for a value.
	 *
	 * @return mixed|null
	 *   Data found, or null if path was not traversed successfully.
	 */
	public function getPathValue( array $data, string $path );

	/**
	 * Get the row from an array of rows that should be used for comparison.
	 *
	 * @param array $rows
	 *   Array of data rows.
	 * @param string $row_key
	 *   Key in a row that should match $row_value.
	 * @param string $row_value
	 *   Value for the $row_key that determines which row should be found.
	 *
	 * @return array|null
	 *   Row found, or null if row wasn't found.
	 */
	public function getComparisonRow( array $rows, string $row_key, string $row_value );

}
