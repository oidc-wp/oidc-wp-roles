<?php

namespace OidcRoles;

use DI\Container;
use OidcRoles\Admin\SettingsTabConnections;
use OidcRoles\Admin\SettingsTabFieldMapping;
use OidcRoles\Admin\SettingsTabImportExport;
use OidcRoles\Admin\SettingsTabGeneral;
use OidcRoles\Admin\SettingsTabLogs;
use OidcRoles\Admin\SettingsTabRoleMapping;
use OidcRoles\Admin\SettingsTabTesting;
use OidcRoles\EventSubscriber\OpenIdConnectGeneric;
use OidcRoles\EventSubscriber\RoleSettingsUpdate;
use OidcRoles\EventSubscriber\RoleShowAdmin;
use OidcRoles\Service\ConnectionClientFactory;
use OidcRoles\Service\LoggerFactory;
use OidcRoles\Service\Settings;
use OidcRoles\EventSubscriber\OpenIdConnectGenericMappings;
use OidcRoles\Service\Comparison;
use OidcRoles\Service\DataFinder;
use OidcRoles\Service\MappingsManager;
use OidcRoles\Service\RoleManager;

/**
 * Class Plugin.
 *
 * @package OidcRoles
 */
class Plugin {

	/**
	 * @var \DI\Container
	 */
	private static $container;

	/**
	 * Startup the plugin.
	 */
	public static function bootstrap() {
		$plugin = new self( new Container() );
		\add_action( 'init', [ $plugin, 'init' ] );
	}

	/**
	 * Plugin constructor.
	 *
	 * @param \DI\Container $container
	 */
	private function __construct( Container $container ) {
		$container->set( 'data_finder', new DataFinder() );
		$container->set( 'comparison', new Comparison() );
		$container->set( 'settings.general', new Settings( 'oidc_wp_roles_general_settings' ) );
		$container->set( 'settings.connections', new Settings( 'oidc_wp_roles_connections' ) );
		$container->set( 'settings.role_mappings', new Settings( 'oidc_wp_roles_role_mapping' ) );
		$container->set( 'settings.field_mappings', new Settings( 'oidc_wp_roles_field_mapping' ) );
		$container->set( 'logger_factory', new LoggerFactory( $container->get( 'settings.general' ) ) );
		$container->set( 'logger.default', $container->get( 'logger_factory' )->channel( 'default' ) );
		$container->set( 'connection_client_factory', new ConnectionClientFactory( $container->get( 'settings.connections' ) ) );
		$container->set( 'role_manager', new RoleManager( $container->get( 'settings.role_mappings' ) ) );
		$container->set( 'mappings_manager', new MappingsManager(
			$container->get( 'settings.connections' ),
			$container->get( 'settings.role_mappings' ),
			$container->get( 'settings.field_mappings' ),
			$container->get( 'data_finder' ),
			$container->get( 'comparison' ),
			$container->get( 'connection_client_factory' ),
			$container->get( 'logger.default' )
		) );

		self::$container = $container;
	}

	/**
	 * Services container.
	 *
	 * @return \DI\Container
	 */
	public static function getContainer() {
		return self::$container;
	}

	/**
	 * Service locator.
	 *
	 * @param string $name
	 *   Service name.
	 */
	public static function service( string $name ) {
		return self::$container->get( $name );
	}

	/**
	 * Implements WP action "init".
	 */
	public function init() {
		\add_action( 'admin_enqueue_scripts', [ $this, 'adminEnqueueScripts' ] );
		\add_action( 'cmb2_admin_init', [ $this, 'registerSettingsPages' ] );

		/**
		 * @var \OidcRoles\Service\SettingsInterface $general_settings
		 * @var \OidcRoles\Service\MappingsManagerInterface $mappings_manager
		 * @var \OidcRoles\Service\RoleManagerInterface $role_manager
		 * @var \Psr\Log\LoggerInterface $logger
		 */
		$mappings_manager = self::service( 'mappings_manager' );
		$logger = self::service( 'logger.default' );
		$general_settings = self::service( 'settings.general' );
		OpenIdConnectGeneric::register( $general_settings );
		OpenIdConnectGenericMappings::register( $mappings_manager, $logger );

		$role_mapping_settings = self::service( 'settings.role_mappings' );
		$role_manager = self::service( 'role_manager' );
		RoleSettingsUpdate::register( $role_mapping_settings, $role_manager );
		RoleShowAdmin::register( $role_manager, \wp_get_current_user() );
	}

	/**
	 * Implements action "admin_enqueue_scripts".
	 *
	 * @param string $current_page
	 *   Current page identifier provided by WP.
	 */
	public function adminEnqueueScripts( $current_page ) {
		if( false === \strpos( $current_page, 'oidc_wp_roles' ) ) {
			return;
		}

		\wp_enqueue_script( 'oidc-wp-roles-settings', OIDC_PLUGIN_URL . 'js/settings.js', [ 'jquery' ], \filemtime( OIDC_PLUGIN_DIR . 'js/settings.js' ), true );
		\wp_enqueue_style( 'oidc-wp-roles-settings', OIDC_PLUGIN_URL . 'css/settings.css', [], \filemtime( OIDC_PLUGIN_DIR . 'css/settings.css' ) );
	}

	/**
	 * Implements action "cmb2_admin_init".
	 */
	public function registerSettingsPages() {
		$container = self::getContainer();
		# The first tab registers the admin menu item as well as the `general` tab on our settings page
		(new SettingsTabGeneral)->register( $container );

		# Other tabs will be added as submenu items in the admin menu
		(new SettingsTabConnections)->register( $container );
		(new SettingsTabRoleMapping)->register( $container );
		(new SettingsTabFieldMapping)->register( $container );
		(new SettingsTabTesting)->register( $container );
		(new SettingsTabLogs())->register( $container );
		(new SettingsTabImportExport())->register( $container );
	}

}
