<?php

namespace OidcRoles\Service;

use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use WordPressHandler\WordPressHandler;

/**
 * Class LoggerFactory.
 *
 * @link https://github.com/bradmkjr/monolog-wordpress
 *
 * @package OidcRoles\Service
 */
class LoggerFactory {

	/**
	 * General settings.
	 *
	 * @var \OidcRoles\Service\SettingsInterface
	 */
	private $generalSettings;

	/**
	 * LoggerFactory constructor.
	 *
	 * @param \OidcRoles\Service\SettingsInterface $general_settings
	 *   General settings.
	 */
	public function __construct( SettingsInterface $general_settings ) {
		$this->generalSettings = $general_settings;
	}

	/**
	 * Get a WordPress database log handler for plugin activation/deactivation.
	 *
	 * @param string $table
	 *   Table name without prefix.
	 * @param int $max_rows
	 *   Number of rows to keep.
	 * @param int $level
	 *   Log level.
	 *
	 * @return WordPressHandler
	 */
	public static function getDefaultHandler( $level = \Monolog\Logger::INFO ) {
		global $wpdb;
		$handler = new WordPressHandler( $wpdb, 'oidc_wp_roles_logs', [], $level );
		$handler->conf_table_size_limiter( 25000 );
		return $handler;
	}

	/**
	 * Get database handler based on plugin settings.
	 *
	 * @return \WordPressHandler\WordPressHandler
	 */
	public function getDbHandler() {
		global $wpdb;

		switch ( $this->generalSettings->get( 'log_level' ) ) {
			case 'disabled':
				$level = Logger::EMERGENCY;
				break;
			case 'error':
				$level = Logger::ERROR;
				break;
			case 'debug':
				$level = Logger::DEBUG;
				break;
			case 'info':
			default:
				$level = Logger::INFO;
				break;
		}

		$handler = new WordPressHandler( $wpdb, 'oidc_wp_roles_logs', [], $level );
		$handler->conf_table_size_limiter( (int) $this->generalSettings->get( 'log_limit', 10000 ) );
		return $handler;
	}

	/**
	 * Get a logger for the given channel name.
	 *
	 * @param string $name
	 *   Channel name.
	 * @param HandlerInterface[] $handlers
	 *   Provide custom handlers to the logger.
	 *
	 * @return LoggerInterface
	 */
	public function channel( string $name, $handlers = [] ) {
		$logger = new \Monolog\Logger( $name );
		foreach ( $handlers as $handler ) {
			$logger->pushHandler($handler);
		}

		if ( empty( $logger->getHandlers() ) ) {
			$logger->pushHandler( $this->getDbHandler() );
		}

		return $logger;
	}

}
