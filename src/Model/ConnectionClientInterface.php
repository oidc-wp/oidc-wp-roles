<?php

namespace OidcRoles\Model;

/**
 * Interface ConnectionClientInterface.
 *
 * @package OidcRoles\Model
 */
interface ConnectionClientInterface {

	/**
	 * Whether or not the client is ready to perform a request.
	 *
	 * @return bool
	 */
	public function isReady();

	/**
	 * Set the replacement values as key value pairs.
	 */
	public function setReplacements( array $replacements );

	/**
	 * Get request user agent.
	 *
	 * @return string
	 */
	public function getUserAgent();

	/**
	 * Get the connection request url with replacements performed.
	 *
	 * @return string
	 *   Prepared request url.
	 */
	public function getRequestUrl();

	/**
	 * Get the connection request method.
	 *
	 * @return string
	 *   Request method. GET, POST, etc.
	 */
	public function getRequestMethod();

	/**
	 * Get the connection request headers with replacements performed.
	 *
	 * @return array
	 *   Prepared request headers.
	 */
	public function getRequestHeaders();

	/**
	 * Get the connection expected response type.
	 *
	 * @return string
	 *   Response type: json, etc.
	 */
	public function getResponseType();

	/**
	 * Perform the connection request.
	 *
	 * @return array|\WP_Error
	 *   Array of response data on success, WP_Error on failure.
	 */
	public function performRequest();

}
