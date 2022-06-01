<?php

namespace OidcRoles\EventSubscriber;

use OidcRoles\Service\RoleManagerInterface;

/**
 * Class RoleShowAdmin.
 *
 * @package OidcRoles\EventSubscriber
 */
class RoleShowAdmin {

	/**
	 * Role manager service.
	 *
	 * @var \OidcRoles\Service\RoleManagerInterface
	 */
	private $roleManager;

	/**
	 * WordPress user.
	 *
	 * @var \WP_User
	 */
	private $currentUser;

	/**
	 * RoleShowAdmin constructor.
	 *
	 * @param \OidcRoles\Service\RoleManagerInterface $role_manager
	 *   Role manager service.
	 * @param \WP_User $current_user
	 *   WordPress user.
	 */
	private function __construct( RoleManagerInterface $role_manager, \WP_User $current_user ) {
		$this->roleManager = $role_manager;
		$this->currentUser = $current_user;
	}

	/**
	 * Register event subscriber.
	 *
	 * @param \OidcRoles\Service\RoleManagerInterface $role_manager
	 *   Role manager service.
	 * @param \WP_User $current_user
	 *   WordPress user.
	 */
	public static function register( RoleManagerInterface $role_manager, \WP_User $current_user  ) {
		$self = new static( $role_manager, $current_user );

		// Hide the admin toolbar and back-end from users as directed in the Yoko SSO options.
		add_filter( 'show_admin_bar', [ $self, 'maybeShowAdminBar' ], 100 );
		add_action( 'admin_page_access_denied', [ $self, 'maybeAllowBackendAccess' ], 100 );
	}

	/**
	 * Hide the toolbar if the user has a role that is not intended to access
	 * the admin backend.
	 *
	 * @param bool $current_bar_status
	 *   Current access to toolbar;
	 *
	 * @return bool
	 *   Whether or not to show the admin toolbar.
	 */
	public function maybeShowAdminBar( bool $current_bar_status ) {
		// Ensure user is logged in.
		// Approach taken from is_user_logged_in().
		if ( ! $this->currentUser->exists() ) {
			return $current_bar_status;
		}

		$oidc_roles = $this->roleManager->getOidcWpRolesForUser( $this->currentUser );
		if ( empty( $oidc_roles ) ) {
			return $current_bar_status;
		}

		// We are dealing with a user that has an oidc role.
		// If the user has any role where admin backend is allowed, show the toolbar.
		foreach ( $oidc_roles as $oidc_role ) {
			if ( $oidc_role['show_admin'] ) {
				return true;
			}
		}

		return $current_bar_status;
	}

	/**
	 * Limit the current user's access to the WordPress back-end based on the SSO options.
	 * This will either let them through or redirect them to the homepage.
	 *
	 * @return void
	 */
	public function maybeAllowBackendAccess() {
		// We don't want to mess with ajax.
		if ( wp_doing_ajax() ) {
			return;
		}

		$oidc_roles = $this->roleManager->getOidcWpRolesForUser( $this->currentUser );
		if ( empty( $oidc_roles ) ) {
			return;
		}

		// We are dealing with a user that has an oidc role.
		// If the user has any role where admin backend is hidden, redirect to home page.
		$allow_backend = false;
		foreach ( $oidc_roles as $oidc_role ) {
			if ( $oidc_role['show_admin'] ) {
				$allow_backend = true;
				break;
			}
		}

		if ( ! $allow_backend ) {
			wp_safe_redirect( home_url() );
			exit;
		}
	}

}
