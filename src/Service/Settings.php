<?php

namespace OidcRoles\Service;

/**
 * Settings for field mappings.
 *
 * @package OidcRoles\Service
 */
class Settings implements SettingsInterface {

	/**
	 * Settings name in option table.
	 *
	 * @var string
	 */
	protected $optionName;

	/**
	 * Settings values.
	 *
	 * @var array
	 */
	protected $values = [];

	/**
	 * Settings constructor.
	 */
	public function __construct( string $option_name ) {
		$this->optionName = $option_name;
		$this->values = \get_option( $option_name, [] );
	}

	/**
	 * {@inheritDoc}
	 */
	public function optionName() {
		return $this->optionName;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getValues() {
		return $this->values;
	}

	/**
	 * {@inheritDoc}
	 */
	public function has( string $name ) {
		return isset( $this->values[ $name ] );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get( string $name, $default = null ) {
		if ( $this->has( $name ) ) {
			return $this->values[ $name ];
		}

		return $default;
	}

}
