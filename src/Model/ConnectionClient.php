<?php

namespace OidcRoles\Model;

/**
 * Class ConnectionClientInterface.
 *
 * @package OidcRoles\Model
 */
class ConnectionClient implements ConnectionClientInterface {

	/**
	 * Settings array for a single connection.
	 *
	 * @var array
	 */
	protected $connectionSettings = [];

	/**
	 * Replacement tokens and their values.
	 *
	 * @var array
	 */
	protected $replacements = [];

	/**
	 * ConnectionClient constructor.
	 *
	 * @param array $connection_settings
	 *
	 */
	public function __construct( array $connection_settings ) {
		$this->connectionSettings = $connection_settings;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isReady() {
		return (
			$this->getRequestUrl() &&
			$this->getRequestMethod() &&
			$this->getRequestHeaders() &&
			$this->getResponseType()
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function setReplacements( array $replacements ) {
		$this->replacements = $replacements;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getUserAgent() {
		return $this->connectionSettings['request_user_agent'];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRequestUrl() {
		return \strtr( $this->connectionSettings['request_url'], $this->replacements );
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRequestMethod() {
		return $this->connectionSettings['request_method'] ?? 'GET';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRequestHeaders() {
		$lines = \array_filter( \explode(PHP_EOL, \strtr( $this->connectionSettings['request_headers'], $this->replacements ) ) );
		$headers = [];
		foreach ( $lines as $line ) {
			$parts = array_map( 'trim', explode( ':', $line ) );
			$headers[ $parts[0] ] = $parts[1];
		}
		return $headers;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getResponseType() {
		return $this->connectionSettings['response_type'] ?? 'json';
	}

	/**
	 * {@inheritdoc}
	 */
	public function performRequest() {
		$request = [
			'headers' => $this->getRequestHeaders(),
			'user-agent' => $this->getUserAgent(),
		];
		switch ( $this->getRequestMethod() ) {
			case 'POST':
				$response = \wp_remote_post( $this->getRequestUrl(), $request );
				break;
			case 'GET':
			default:
				$response = \wp_remote_get( $this->getRequestUrl(), $request );
				break;
		}

		if ( \is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['body'] ) ) {
			return new \WP_Error( 'response-empty', __( 'Response from connection was empty.', 'oidc-wp-roles' ) );
		}

		$response_data = new \WP_Error( 'response-decode-failed', __( 'Connection request response failed to decode.', 'oidc-wp-roles' ) );

		switch ($this->getResponseType()) {
			case 'json':
			default:
				$decoded = \json_decode( $response['body'], TRUE );
				if ( \is_array( $decoded ) ) {
					$response_data = $decoded;
				}
				break;
		}

		return $response_data;
	}

}
