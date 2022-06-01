<?php

namespace OidcRoles\Service;

/**
 * Class RoleManager.
 *
 * @package OidcRoles\Service
 */
class RoleManager implements RoleManagerInterface {

	/**
	 * Role mapping settings.
	 *
	 * @var \OidcRoles\Service\SettingsInterface
	 */
	private $roleMappingSettings;

	/**
	 * RoleManager constructor.
	 *
	 * @param \OidcRoles\Service\SettingsInterface $role_mapping_settings
	 *   Role mapping settings.
	 */
	public function __construct( SettingsInterface $role_mapping_settings ) {
		$this->roleMappingSettings = $role_mapping_settings;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAllRoles() {
		$wp_roles = wp_roles();
		$roles = $wp_roles->roles;

		$existing_roles = [];

		foreach( $roles as $slug => $role ) {
			/** @var \WP_Role $role */
			$existing_roles[] = [
				'slug' => $slug,
				'name' => $role['name'],
			];
		}

		return $existing_roles;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAllRolesAsOptionsArray() {
		$options = [];
		foreach ( $this->getAllRoles() as $role ) {
			$options[ $role['slug'] ] = $role['name'];
		}
		return $options;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getOidcWpRoles() {
		$roles = $this->roleMappingSettings->get( 'roles', [] );
		foreach ( $roles as $i => $role ) {
			$roles[ $i ]['show_admin'] = !empty( $role['show_admin'] );
			if ( empty( $role['name'] ) ) {
				$roles[ $i ]['name'] = $role['slug'];
			}
		}
		return $roles;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getOidcWpRoleBySlug( string $slug ) {
		$roles = $this->getOidcWpRoles();
		foreach ( $roles as $role ) {
			if ( $role['slug'] == $slug ) {
				return $role;
			}
		}
		return FALSE;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getOidcWpRolesForUser( \WP_User $user ) {
		$user_roles = $user->roles;
		return array_filter( $this->getOidcWpRoles(), function( $role ) use ( $user_roles ) {
			return in_array( $role['slug'], $user_roles );
		} );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getNonOidcWpRoles() {
		$all_roles = $this->getAllRoles();
		$saved_oidc_roles = $this->getOidcWpRoles();
		$saved_oidc_roles_slugs = \array_column( $saved_oidc_roles, 'slug' );
		$non_oidc_roles = [];
		foreach ( $all_roles as $role ) {
			if ( ! \in_array( $role['slug'], $saved_oidc_roles_slugs) ) {
				$non_oidc_roles[] = $role;
			}
		}
		return $non_oidc_roles;
	}

}
