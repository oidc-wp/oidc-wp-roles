<?php

namespace OidcRoles\Service;

/**
 * Interface RoleManagerInterface.
 *
 * @package OidcRoles\Service
 */
interface RoleManagerInterface {

	/**
	 * Get existing WP roles.
	 *
	 * @return array[]
	 *   - slug
	 *   - name
	 */
	public function getAllRoles();

	/**
	 * Get roles as an array of options for a settings field.
	 *
	 * @return array
	 */
	public function getAllRolesAsOptionsArray();

	/**
	 * Get an array of roles that this plugin manages.
	 *
	 * @return array
	 */
	public function getOidcWpRoles();

	/**
	 * Get a specific custom role by its slug.
	 *
	 * @param string $slug
	 *   Slug for role array to find.
	 *
	 * @return array|false
	 *   Role details array if found, otherwise false.
	 */
	public function getOidcWpRoleBySlug( string $slug );

	/**
	 * Get all role details for the user.
	 *
	 * @param \WP_User $user
	 *   User to get roles for.
	 *
	 * @return array
	 *   Array of roles created by this plugin.
	 */
	public function getOidcWpRolesForUser( \WP_User $user );

	/**
	 * Get an array of roles provided by WP or other plugins.
	 *
	 * @return array
	 */
	public function getNonOidcWpRoles();

}
