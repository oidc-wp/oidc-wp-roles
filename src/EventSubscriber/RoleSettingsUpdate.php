<?php

namespace OidcRoles\EventSubscriber;

use OidcRoles\Service\RoleManagerInterface;
use OidcRoles\Service\SettingsInterface;

/**
 * Class RoleSettingsUpdate.
 *
 * @package OidcRoles\EventSubscriber
 */
class RoleSettingsUpdate {

	/**
	 * @var \OidcRoles\Service\SettingsInterface
	 */
	private $roleMappingSettings;

	/**
	 * Role manager service.
	 *
	 * @var \OidcRoles\Service\RoleManagerInterface
	 */
	private $roleManager;

	/**
	 * RoleShowAdmin constructor.
	 *
	 * @param \OidcRoles\Service\RoleManagerInterface $role_manager
	 *   Role manager service.
	 */
	private function __construct( SettingsInterface $role_mapping_settings, RoleManagerInterface $role_manager ) {
		$this->roleMappingSettings = $role_mapping_settings;
		$this->roleManager = $role_manager;
	}

	/**
	 * Register event subscriber.
	 *
	 * @param \OidcRoles\Service\RoleManagerInterface $role_manager
	 *   Role manager service.
	 */
	public static function register( SettingsInterface $role_mapping_settings, RoleManagerInterface $role_manager ) {
		$self = new static( $role_mapping_settings, $role_manager );

		add_action( "update_option_{$role_mapping_settings->optionName()}", [ $self, 'updateRoles' ], 100, 3 );
	}

	/**
	 * @param array $old_values
	 * @param array $values
	 * @param string $option_name
	 */
	public function updateRoles( $old_values, $values, $option_name ) {
		$old_roles = $old_values['roles'];
		$old_role_slugs = array_column( $old_values['roles'], 'slug' );
		$saved_roles = $values['roles'];
		$saved_role_slugs = array_column( $values['roles'], 'slug' );
		$new_roles = [];
		$deleted_roles = [];
		$updated_roles = [];

		// Get a list of deleted roles.
		// If an old value is not in the saved values, it was deleted.
		// What remains in the $old_roles array are roles that may have been updated.
		foreach ( $old_roles as $i => $old_role ) {
			if ( ! \in_array( $old_role['slug'], $saved_role_slugs ) ) {
				$deleted_roles[] = $old_role;
				unset( $old_roles[ $i ] );
			}
		}

		// Get a list of new roles.
		// If a new value is not in the old values, it is new.
		// What remains in the $saved_roles array are roles that may have been updated.
		foreach ( $saved_roles as $i => $saved_role ) {
			if ( ! \in_array( $saved_role['slug'], $old_role_slugs ) ) {
				$new_roles[] = $saved_role;
				unset( $saved_roles[ $i ] );
			}
		}

		// Get a list of updated roles.
		foreach ( $saved_roles as $i => $saved_role ) {
			foreach ( $old_roles as $j => $old_role ) {
				if ( $saved_role['slug'] == $old_role['slug'] && $saved_role['show_admin'] != $old_role['show_admin'] ) {
					$updated_roles[] = $saved_role;
				}
			}
		}

		// Remove deleted roles.
		foreach ( $deleted_roles as $role ) {
			\remove_role( $role['slug'] );
		}

		// Add new roles.
		foreach ( $new_roles as $role ) {
			$capabilities = [];
			if ( !empty( $role['show_admin'] ) ) {
				$capabilities['read'] = true;
			}
			\add_role( $role['slug'], $role['name'], $capabilities );
		}

		// Adjust capabilities of updated roles.
		foreach ( $updated_roles as $role ) {
			$wp_role = \get_role( $role['slug'] );
			if ( $wp_role ) {
				if ( !empty( $role['show_admin'] ) ) {
					$wp_role->add_cap( 'read' );
				}
				else {
					$wp_role->remove_cap( 'read' );
				}
			}
		}
	}

}
