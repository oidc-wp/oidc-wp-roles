<?php
/*
Plugin Name: OpenID Connect - WordPress Roles
Plugin URI: TBD
Description:
Author: daggerhart
Version: 1.0
Author URI: https://www.daggerhartlab.com
Text Domain: oidc-wp-roles
*/

define( 'OIDC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OIDC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

if ( file_exists( __DIR__ . "/vendor/autoload.php" ) ) {
    require_once __DIR__ . "/vendor/autoload.php";
    \OidcRoles\Plugin::bootstrap();
}

/**
 * Prepare database for plugin.
 */
register_activation_hook(__FILE__, 'oidc_wp_roles_install');
function oidc_wp_roles_install() {
	require_once __DIR__ . "/vendor/autoload.php";
	$handler = \OidcRoles\Service\LoggerFactory::getDefaultHandler();
	$handler->initialize(['extra' => []]);
}

/**
 * Clean up database after uninstall of plugin.
 */
register_uninstall_hook(__FILE__, 'oidc_wp_roles_uninstall');
function oidc_wp_roles_uninstall() {
	require_once __DIR__ . "/vendor/autoload.php";
	$handler = \OidcRoles\Service\LoggerFactory::getDefaultHandler();
	$handler->uninitialize();

	/**
	 * If "Cleanup" setting is enabled, then perform cleanup
	 */
	$container = \OidcRoles\Plugin::getContainer();

	/** @var \OidcRoles\Service\Settings $settings */
	$settings = $container->get( 'settings.general' );

	if( ! $settings->get( 'cleanup', false ) ) {
		return;
	}

	/**
	 * Remove roles created by this plugin
	 */

	/** @var \OidcRoles\Service\RoleManager $role_manager */
	$role_manager = $container->get( 'role_manager' );
	$oidc_roles = $role_manager->getOidcWpRoles();

	foreach( $oidc_roles as $role ) {
		remove_role( $role['slug'] );
	}

	/**
	 * Delete plugin settings
	 */
	delete_option( 'oidc_wp_roles_general_settings' );
	delete_option( 'oidc_wp_roles_connections' );
	delete_option( 'oidc_wp_roles_role_mapping' );
	delete_option( 'oidc_wp_roles_field_mapping' );
}
