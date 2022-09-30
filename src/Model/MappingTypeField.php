<?php

namespace OidcRoles\Model;

use OidcRoles\Service\DataFinderInterface;

/**
 * Class FieldMapping.
 *
 * @package OidcRoles\Model
 */
class MappingTypeField implements MappingTypeFieldInterface {

	/**
	 * Field mapping settings values.
	 *
	 * @var array
	 */
	protected $values = [];

	/**
	 * Data finder service.
	 *
	 * @var \OidcRoles\Service\DataFinderInterface|null
	 */
	private $dataFinder;

	/**
	 * WordPress user instance.
	 *
	 * @var \WP_User
	 */
	protected $user;

	/**
	 * FieldMapping constructor.
	 *
	 * @param array $values
	 *   Field mapping settings values.
	 * @param \OidcRoles\Service\DataFinderInterface|null $data_finder
	 *   Data finder service.
	 * @param \WP_User|null $user
	 *   WordPress user instance.
	 */
	public function __construct( array $values, DataFinderInterface $data_finder = NULL, \WP_User $user = NULL ) {
		$this->values = $values;
		if ( $data_finder ) {
			$this->setDataFinder( $data_finder );
		}
		if ( $user ) {
			$this->setUser( $user );
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function setDataFinder( DataFinderInterface $data_finder ) {
		$this->dataFinder = $data_finder;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getUser() {
		return $this->user;
	}

	/**
	 * {@inheritdoc}
	 */
	public function setUser( \WP_User $user ) {
		$this->user = $user;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isValid() {
		return (
			$this->user &&
			$this->has( 'connection' ) &&
			$this->has( 'value_key' ) &&
			$this->has( 'data_target' ) &&
			$this->has( 'data_key' )
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function has( string $name ) {
		return isset( $this->values[ $name ] ) && $this->values[ $name ] !== '';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get( string $name, $default = NULL ) {
		return $this->has( $name ) ? $this->values[ $name ] : $default;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDataValue( array $data ) {
		$traversing = $data;
		if ( $this->has( 'response_data_row_path' ) ) {
			$traversing = $this->dataFinder->getPathValue( $traversing, $this->get( 'response_data_row_path' ) );
		}
		if ( $traversing && $this->has( 'response_data_row_key' ) && $this->has( 'response_data_row_value' ) ) {
			$traversing = $this->dataFinder->getComparisonRow( $traversing, $this->get( 'response_data_row_key' ), $this->get( 'response_data_row_value' ) );
		}

		return $this->dataFinder->getPathValue( $traversing ?? [], $this->get( 'value_key' ) );
	}

	/**
	 * {@inheritdoc}
	 */
	public function getMappingResult( array $data ) {
		$value = $this->getDataValue( $data );

		// Only care about strings, ints, floats.
		if ( !is_string( $value ) && !is_numeric( $value ) && !is_bool( $value ) ) {
			return new MappingResult( false, strtr( __( 'Field mapping failed to find the value_key %value_key in the response.', 'oidc-wp-roles' ), [
				'%value_key' => '<code>' . $this->get( 'value_key' ) . '</code>',
			] ) );
		}

		return new MappingResult( true, strtr( __( 'Field mapping successfully found value %value in response at the value_key %value_key', 'oidc-wp-roles' ), [
			'%value' => '<strong>' . $value . '</strong>',
			'%value_key' => '<code>' . $this->get( 'value_key' ) . '</code>',
		] ) );
	}

	/**
	 * {@inheritdoc}
	 */
	public function performMapping( array $data, array $results_so_far = [] ) {
		$mapping_result = $this->getMappingResult( $data );
		if ( $mapping_result->success() ) {
			$value = $this->getDataValue( $data );

			switch ( $this->get( 'data_target' ) ) {
				case 'user_property':
					$update_result = wp_update_user( [
						'ID' => $this->getUser()->ID,
						$this->get( 'data_key' ) => $value,
					] );

					return ! is_wp_error( $update_result );
					break;

				case 'user_meta':
					return (bool) update_user_meta( $this->getUser()->ID, $this->get( 'data_key' ), $value );
					break;
			}
		}

		return FALSE;
	}

}
