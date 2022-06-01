<?php

namespace OidcRoles\Admin;

use DI\Container;
use OidcRoles\Cmb2\GroupFieldRenderer;

/**
 * Class SettingsTabGeneral.
 *
 * @package OidcRoles\Admin
 */
class SettingsTabConnections extends SettingsTabBase {

	/**
	 * @var \OidcRoles\Service\SettingsInterface
	 */
	private $connectionSettings;

	/**
	 * {@inheritdoc}
	 */
	public function id() {
		return $this->connectionSettings->optionName();
	}

	/**
	 * {@inheritdoc}
	 */
	public function title() {
		return __( 'Connections', 'oidc-wp-roles' );
	}

	/**
	 * Registers General options tab and main admin menu item
	 */
	public function register( Container $container ) {
		$this->connectionSettings = $container->get( 'settings.connections' );

		$args = $this->parseMetaBoxArgs( [
			'id' => $this->id(),
			'tab_title' => $this->title(),
			'message_cb' => [ $this, 'filterCmbMessage' ]
		] );

		$meta_box = new_cmb2_box( $args );
		$group_field_renderer = new GroupFieldRenderer( $meta_box );
		$field_token_desc = strtr( __( "This field has replacement values available for the logged in user's %subject-identity and %access-token.", 'oidc-wp-roles' ), [
			'%subject-identity' => '<code>[subject-identity]</code>',
			'%access-token' => '<code>[access-token]</code>',
		] );

		$group_id = $meta_box->add_field( [
			'id' => 'connections',
			'name' => __( 'Connections', 'oidc-wp-roles' ),
			'desc' => __( 'A connection is a remote service where data about the logged in user can be found and latter mapped to the user.', 'oidc-wp-roles' ),
			'type' => 'group',
			'repeatable' => true,
			'render_row_cb' => [ $group_field_renderer, 'renderGroup' ],
			'options' => [
				'group_title' => __( 'Connection: {#name}', 'oidc-wp-roles' ),
				'add_button' => __( 'Add another connection', 'oidc-wp-roles' ),
				'remove_button' => __( 'Remove connection', 'oidc-wp-roles' ),
				'remove_confirm' => __( 'Are you sure you want to remove this connection? This may break Role or Field Mapping settings.', 'oidc-wp-roles' ),
				'closed' => false,
				'sortable' => false,
			],
		] );
		$meta_box->add_group_field( $group_id, [
			'id'   => 'name',
			'name' => __( 'Connection Name', 'oidc-wp-roles' ),
			'desc' => '<strong>' . __( 'Required', 'oidc-wp-roles' ) . '</strong><br>' .
				__( 'Unique name for this connection.', 'oidc-wp-roles' ) .
				'<br><strong>' . __( 'Warning: changing this value will break any mappings that use this connection.', 'oidc-wp-roles' ) . '</strong>',
			'type' => 'text',
			'classes' => 'oidc-field-slugify',
			'attributes' => [
				'required' => 'required',
			],
		] );
		$meta_box->add_group_field( $group_id, [
			'id'   => 'request_url',
			'name' => __( 'Request - Url', 'oidc-wp-roles' ),
			'desc' => '<strong>' . __( 'Required', 'oidc-wp-roles' ) . '</strong><br>' .
				__( 'The URL that should receive the request for this connection.', 'oidc-wp-roles' ) . '<br>' .
				$field_token_desc,
			'type' => 'text',
			'sanitize_cb' => [ $this, 'sanitizeRequestUrl' ],
			'attributes' => [
				'required' => 'required',
			],
		] );
		$meta_box->add_group_field( $group_id, [
			'id'   => 'request_method',
			'name' => __( 'Request - Method', 'oidc-wp-roles' ),
			'desc' => '<strong>' . __( 'Required', 'oidc-wp-roles' ) . '</strong><br>' .
				__( 'The type of request that should be made for this connection.', 'oidc-wp-roles' ),
			'type' => 'select',
			'default' => 'GET',
			'options' => [
				'GET' => __( 'GET', 'oidc-wp-roles' ),
				'POST' => __( 'POST', 'oidc-wp-roles' ),
			],
			'attributes' => [
				'required' => 'required',
			],
		] );
		$meta_box->add_group_field( $group_id, [
			'name' => __( 'Request - User Agent', 'oidc-wp-roles' ),
			'desc' => '<em>' . __( 'Optional', 'oidc-wp-roles' ) . '</em><br>' .
				__( 'Provide a custom user agent for the client request. This value depends on the requirements of the server this connection makes requests to.', 'oidc-wp-roles' ),
			'id'   => 'request_user_agent',
			'type' => 'text',
		] );
		$meta_box->add_group_field( $group_id, [
			'name' => __( 'Request - Headers', 'oidc-wp-roles' ),
			'desc' => '<em>' . __( 'Optional', 'oidc-wp-roles' ) . '</em><br>' .
				__( 'One header per line.', 'oidc-wp-roles' ) . '<br>' .
				$field_token_desc,
			'id'   => 'request_headers',
			'type' => 'textarea_code',
		] );
		$meta_box->add_group_field( $group_id, [
			'name' => __( 'Response - Type', 'oidc-wp-roles' ),
			'desc' => '<strong>' . __( 'Required', 'oidc-wp-roles' ) . '</strong><br>' .
		        __( 'The type of response data that is expected from this connection.', 'oidc-wp-roles' ),
			'id'   => 'response_type',
			'type' => 'select',
			'options' => [
				'json' => __( 'JSON', 'oidc-wp-roles' ),
			],
			'attributes' => [
				'required' => 'required',
			],
		] );
	}

	/**
	 * Clean up the request url on save.
	 *
	 * @param string $values
	 *
	 * @return array|string|string[]
	 */
	public function sanitizeRequestUrl( $values ) {
		return str_replace( '&amp;', '&', $values );
	}

}
