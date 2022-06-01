<?php

namespace OidcRoles\Model;

use OidcRoles\Service\ComparisonInterface;
use OidcRoles\Service\DataFinderInterface;

/**
 * Class RoleMapping.
 *
 * @package OidcRoles\Model
 */
class MappingTypeRole implements MappingTypeRoleInterface {

	/**
	 * Role mapping settings values.
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
	 * Comparison service.
	 *
	 * @var \OidcRoles\Service\ComparisonInterface
	 */
	private $comparison;

	/**
	 * WordPress user instance.
	 *
	 * @var \WP_User
	 */
	protected $user;

	/**
	 * RoleMapping constructor.
	 *
	 * @param array $values
	 *   Role mapping settings values.
	 * @param \OidcRoles\Service\DataFinderInterface|null $data_finder
	 *   Data finder service.
	 * @param \OidcRoles\Service\ComparisonInterface|null $comparison
	 *   Comparison service.
	 * @param \WP_User|null $user
	 *   WordPress user instance.
	 */
	public function __construct( array $values, DataFinderInterface $data_finder = NULL, ComparisonInterface $comparison = NULL, \WP_User $user = NULL ) {
		$this->values = $values;
		if ( $data_finder ) {
			$this->setDataFinder( $data_finder );
		}
		if ( $comparison ) {
			$this->setComparison( $comparison );
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
	public function setComparison( ComparisonInterface $comparison ) {
		$this->comparison = $comparison;
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
			$this->has( 'test_value_key' ) &&
			$this->has( 'comparison_operator' ) &&
			$this->has( 'comparison_value' ) &&
			$this->has( 'role' )
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
	public function getUser() {
		return $this->user;
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

		return $this->dataFinder->getPathValue( $traversing, $this->get( 'test_value_key' ) );
	}

	/**
	 * {@inheritdoc}
	 */
	public function getMappingResult( array $data ) {
		$comparison_result = $this->comparison->getComparisonResult(
			$this->getDataValue( $data ),
			$this->get( 'comparison_operator' ),
			$this->get( 'comparison_value' )
		);

		$replacements = [
			'%role' => '<code>' . $this->get( 'role' ) . '</code>',
			'%user' => '<strong>' . $this->getUser()->display_name . '</strong>',
			'%user_id' => $this->getUser()->ID,
		];
		$description = $comparison_result->success() ?
			\strtr( __( 'Role %role would be assigned to %user (%user_id).', 'oidc-wp-roles' ), $replacements ) :
			\strtr( __( 'Role %role not assigned to %user (%user_id).', 'oidc-wp-roles' ), $replacements );

		$comparison_result->setDescription( $description );

		return $comparison_result;
	}

	/**
	 * {@inheritdoc}
	 */
	public function performMapping( array $data, array $results_so_far = [] ) {
		if ( $this->getMappingResult( $data )->success() ) {
			$successful_results_so_far = array_filter( $results_so_far, function ( $result ) {
				return $result->success();
			} );
			$assigned_role_previously = !empty( $successful_results_so_far );

			if ( !$assigned_role_previously ) {
				// If we have not already assigned a role, then we use set_role() so
				// that the first role we assign removes the users other roles.
				$this->user->set_role( $this->get( 'role' ) );
			}
			else {
				// Subsequent role assignments use add_role().
				$this->user->add_role( $this->get( 'role' ) );
			}
			return TRUE;
		}

		return FALSE;
	}

}
