<?php

namespace OidcRoles\EventSubscriber;

use OidcRoles\Service\MappingsManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class OpenIdConnectGenericMappings.
 *
 * @package OidcRoles\EventSubscriber
 */
class OpenIdConnectGenericMappings {

	/**
	 * Mappings manager service.
	 * @var MappingsManagerInterface
	 */
	private $mappingsManager;

	/**
	 * Logger.
	 *
	 * @var \Psr\Log\LoggerInterface
	 */
	private $logger;

	/**
	 * OpenIdConnectGenericMappings constructor.
	 *
	 * @param \OidcRoles\Service\MappingsManagerInterface $mappings_manager
	 *   Mappings manager service.
	 * @param \Psr\Log\LoggerInterface $logger
	 *   Logger.
	 */
	private function __construct( MappingsManagerInterface $mappings_manager, LoggerInterface $logger  ) {
		$this->mappingsManager = $mappings_manager;
		$this->logger = $logger;
	}

	/**
	 * Register our events.
	 *
	 * @param \OidcRoles\Service\MappingsManagerInterface $mappings_manager
	 *   Mappings manager service.
	 * @param \Psr\Log\LoggerInterface $logger
	 *   Logger.
	 */
	public static function register( MappingsManagerInterface $mappings_manager, LoggerInterface $logger ) {
		$self = new static( $mappings_manager, $logger );

		\add_action( 'openid-connect-generic-user-logged-in', [ $self, 'oidcUserLogin' ] );
	}

	/**
	 * Hook into the openid-connect-generic user login event.
	 *
	 * @param \WP_User $user
	 */
	public function oidcUserLogin( \WP_User $user ) {
		$token_response = \get_user_meta( $user->ID, 'openid-connect-generic-last-token-response', true );
		$subject_identity = \get_user_meta( $user->ID, 'openid-connect-generic-subject-identity', true );
		$this->mappingsManager->setUser( $user );
		$client_mappings_collections = $this->mappingsManager->getConnectionClientMappingCollections();
		$this->logger->debug( \strtr( __( 'Attempting connections: %connection_names, for user %user (%user_id)', 'oidc-wp-roles' ), [
			'%connection_names' => \implode( ', ', \array_keys( $client_mappings_collections ) ),
			'%user' => $user->display_name,
			'%user_id' => $user->ID,
		] ) );

		if ( empty( $token_response['access_token'] ) ) {
			$this->logger->warning("User {$user->ID} does not have access_token from oidc.");
			return;
		}
		if ( empty( $subject_identity ) ) {
			$this->logger->warning("User {$user->ID} does not have subject_identity from oidc.");
			return;
		}

		foreach ( $client_mappings_collections as $connection_name => $collection ) {
			$client = $collection->getClient();
			$client->setReplacements( [
				'[subject-identity]' => $subject_identity,
				'[access-token]'     => $token_response['access_token'],
			] );

			if ( ! $client->isReady() ) {
				$this->logger->error( \strtr( __( 'Connection client not ready to perform request: %connection_name', 'oidc-wp-roles' ), [
					'%connection_name' => $connection_name,
				] ) );
				continue;
			}

			$data = $client->performRequest();
			if ( \is_wp_error( $data ) ) {
				$this->logger->error( \strtr( __( 'Connection client request failed with error: %error', 'oidc-wp-roles' ), [
					'%error' => $data->get_error_message(),
				] ) );
				continue;
			}

			// Save the last data response for debugging purpose.
			update_user_meta( $user->ID, "oidc_wp_roles--connection-response--{$connection_name}", $data );

			/*
			 * Role mappings.
			 */
			$results = [];
			$success = 0;
			foreach ( $collection->getRoleMappings() as $mapping ) {
				if ( ! $mapping->isValid() ) {
					$this->logger->warning( \strtr( __( 'Role mapping invalid. Connection: %connection_name, Role: %role, Comparison: %test_value_key %comparison_operator %comparison_value', 'oidc-wp-roles' ), [
						'%connection_name' => $connection_name,
						'%test_value_key' => $mapping->get( 'test_value_key' ),
						'%comparison_operator' => $mapping->get( 'comparison_operator' ),
						'%comparison_value' => $mapping->get( 'comparison_value' ),
						'%role' => $mapping->get( 'role' ),
					] ) );
					continue;
				}

				$mapping_result = $mapping->getMappingResult( $data );
				$mapping_success = $mapping->performMapping( $data, $results );
				if ( $mapping_success ) {
					$success += 1;
					$mapping_result->setDescription( \strtr( __( 'Role %role mapped success for user %user (%user_id).', 'oidc-wp-roles' ), [
						'%role' => $mapping->get( 'role' ),
						'%user' => $mapping->getUser()->display_name,
						'%user_id' => $mapping->getUser()->ID,
					] ) );
					$this->logger->debug( $mapping_result->description() );
				}
				else {
					$this->logger->debug( $mapping_result->description() );
				}
				$results[] = $mapping_result;
			}
			$this->logger->info( \strtr( __( 'Role mapping complete for %user (%user_id). Successfully mapped %success_count out of %total role mappings.', 'oidc-wp-roles' ), [
				'%user' => $user->display_name,
				'%user_id' => $user->ID,
				'%success_count' => $success,
				'%total' => count( $collection->getRoleMappings() ),
			] ) );

			/*
			 * Field mappings.
			 */
			$results = [];
			$success = 0;
			foreach ( $collection->getFieldMappings() as $mapping ) {
				if ( ! $mapping->isValid() ) {
					$this->logger->warning( \strtr( __( 'Field mapping invalid. Connection: %connection_name, Value key: %value_key, Data Target: %data_target, Data key: %data_key', 'oidc-wp-roles' ), [
						'%connection_name' => $connection_name,
						'%value_key' => $mapping->get( 'value_key' ),
						'%data_target' => $mapping->get( 'data_target' ),
						'%data_key' => $mapping->get( 'data_key' ),
					] ) );
					continue;
				}

				$value = $mapping->getDataValue( $data );
				$mapping_result = $mapping->getMappingResult( $data );
				$mapping->performMapping( $data, $results );
				// Check the mapping_result for success rather than performMapping() because update_user_meta() returns
				// false when a meta field _could_ have been updated, but wasn't due to the new value being the same as
				// the old value.
				if ( $mapping_result->success() ) {
					$success += 1;
					$mapping_result->setDescription( \strtr( __( 'Field mapped successfully for user %user (%user_id): %value_key mapped to %data_target %data_key = %data_value.', 'oidc-wp-roles' ), [
						'%user' => $mapping->getUser()->display_name,
						'%user_id' => $mapping->getUser()->ID,
						'%value_key' => $mapping->get( 'value_key' ),
						'%data_target' => $mapping->get( 'data_target' ),
						'%data_key' => $mapping->get( 'data_key' ),
						'%data_value' => $value,
					] ) );
					$this->logger->debug( $mapping_result->description() );
				}
				else {
					$this->logger->debug( $mapping_result->description() );
				}
				$results[] = $mapping_result;
			}
			$this->logger->info( \strtr( __( 'Field mapping complete for %user (%user_id). Successfully mapped %success_count out of %total field mappings.', 'oidc-wp-roles' ), [
				'%user' => $user->display_name,
				'%user_id' => $user->ID,
				'%success_count' => $success,
				'%total' => count( $collection->getFieldMappings() ),
			] ) );
		}
	}

}
