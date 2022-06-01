<?php

namespace OidcRoles\Model;

/**
 * Class MappingResult.
 *
 * @package OidcRoles\Model
 */
class MappingResult implements MappingResultInterface {

	/**
	 * Whether the mapping was successful or not.
	 *
	 * @var bool
	 */
	protected $success = false;

	/**
	 * Description of the mapping result.
	 *
	 * @var string
	 */
	protected $description = '';

	/**
	 * MappingResult constructor.
	 *
	 * @param bool $success
	 *   Whether the mapping was successful or not.
	 * @param string $description
	 *   Description of the mapping result.
	 */
	public function __construct( bool $success = false, string $description = '' ) {
		$this->setSuccess( $success );
		$this->setDescription( $description );
	}

	/**
	 * @inheritDoc
	 */
	public function success() {
		return $this->success;
	}

	/**
	 * @inheritDoc
	 */
	public function setSuccess( bool $result ) {
		$this->success = $result;
		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function description() {
		return $this->description;
	}

	/**
	 * @inheritDoc
	 */
	public function setDescription( string $description ) {
		$this->description = $description;
		return $this;
	}

}
