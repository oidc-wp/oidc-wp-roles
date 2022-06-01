<?php

namespace OidcRoles\Service;

/**
 * Interface SettingsInterface.
 *
 * @package OidcRoles\Service
 */
interface SettingsInterface {

	/**
	 * Where values are saved in the options table.
	 *
	 * @return string
	 */
	public function optionName();

	/**
	 * Get all settings values.
	 *
	 * @return array
	 */
	public function getValues();

	/**
	 * Whether or not a setting value exist.
	 *
	 * @param string $name
	 *   Setting name (key in options array).
	 * @return bool
	 */
	public function has( string $name );

	/**
	 * Get a single setting's value.
	 *
	 * @param string $name
	 *   Setting name (key in options array).
	 * @param null $default
	 *   Default to return if value is not found.
	 *
	 * @return mixed
	 */
	public function get( string $name, $default = NULL );

}
