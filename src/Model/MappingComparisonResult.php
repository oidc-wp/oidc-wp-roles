<?php


namespace OidcRoles\Model;

/**
 * Class ComparisonResult.
 *
 * @package OidcRoles\Model
 */
class MappingComparisonResult implements MappingComparisonResultInterface {

	/**
	 * Value to compare against $comparisonValue.
	 *
	 * @var mixed
	 */
	protected $testValue;

	/**
	 * Comparison operator.
	 *
	 * @var string
	 */
	protected $operator;

	/**
	 * Value to compare against $testValue.
	 *
	 * @var mixed
	 */
	protected $comparisonValue;


	/**
	 * Whether the comparison was successful.
	 *
	 * @var bool
	 */
	protected $success = false;

	/**
	 * Description of the comparison result.
	 *
	 * @var string
	 */
	protected $description = '';

	/**
	 * ComparisonResult constructor.
	 *
	 * @param mixed $test_value
	 *   Value to compare against $comparisonValue.
	 * @param string $operator
	 *   Comparison operator.
	 * @param mixed $comparison_value
	 *   Value to compare against $testValue.
	 * @param bool $success
	 *   Whether the comparison was successful.
	 * @param string $description
	 *   Description of the comparison result.
	 */
	public function __construct( $test_value, string $operator, $comparison_value, bool $success = false, string $description = '' ) {
		$this->testValue = $test_value;
		$this->operator = $operator;
		$this->comparisonValue = $comparison_value;
		$this->setSuccess( $success );
		$this->setDescription( $description );
	}

	/**
	 * @inheritDoc
	 */
	public function comparisonValue() {
		return $this->comparisonValue;
	}

	/**
	 * @inheritDoc
	 */
	public function operator() {
		return $this->operator;
	}

	/**
	 * @inheritDoc
	 */
	public function testValue() {
		return $this->testValue;
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
