<?php

namespace OidcRoles\Admin;

use DI\Container;
use OidcRoles\Cmb2\GroupFieldRenderer;
use OidcRoles\Service\Comparison;

/**
 * Class SettingsTabRoleMapping.
 *
 * @package OidcRoles\Admin
 */
class SettingsTabFieldMapping extends SettingsTabBase {

	/**
	 * @var \OidcRoles\Service\SettingsInterface
	 */
	private $connectionSettings;

	/**
	 * @var \OidcRoles\Service\SettingsInterface
	 */
	private $fieldMappingSettings;

	/**
	 * @inheritDoc
	 */
	public function id() {
		return $this->fieldMappingSettings->optionName();
	}

	/**
	 * @inheritDoc
	 */
	public function title() {
		return __( 'Field Mapping', 'oidc-wp-roles' );
	}

	/**
	 * Register metabox.
	 */
	public function register( Container $container ) {
		$this->connectionSettings = $container->get( 'settings.connections' );
		$this->fieldMappingSettings = $container->get( 'settings.field_mappings' );

		$args = $this->parseMetaBoxArgs( [
			'id' => $this->id(),
			'tab_title' => $this->title(),
			'message_cb' => [ $this, 'filterCmbMessage' ],
		] );

		$meta_box = \new_cmb2_box( $args );
		$group_field_renderer = new GroupFieldRenderer( $meta_box );

		/*
		 * Map user fields based on connections.
		 */
		$group_id = $meta_box->add_field( [
			'id' => 'field_mapping',
			'name' => __( 'Field Mapping', 'oidc-wp-roles' ),
			'desc' => __( 'Using data from a connection, assign data to users when they login.', 'oidc-wp-roles' ),
			'type' => 'group',
			'repeatable' => true,
			'render_row_cb' => [ $group_field_renderer, 'renderGroup' ],
			'options' => [
				'group_title' => __( '{#} - Connection: {#connection} - Assign value found at: {#value_key} to {#data_target} {#data_key}', 'oidc-wp-roles' ),
				'add_button' => __( 'Add another mapping', 'oidc-wp-roles' ),
				'remove_button' => __( 'Remove mapping', 'oidc-wp-roles' ),
				'remove_confirm' => esc_html__( 'Are you sure you want to remove this mapping?', 'oidc-wp-roles' ),
				'closed' => !empty( $this->fieldMappingSettings->get( 'field_mapping', [] ) ),
				'sortable' => true,
			],
		] );

		$meta_box->add_group_field( $group_id, [
			'name' => __( 'Notes', 'oidc-wp-roles' ),
			'desc' => '<em>' . __( 'Optional', 'oidc-wp-roles' ) . '</em><br>' .
				__( 'Keep notes about this mapping for future reference. This does not affect the mapping process.', 'oidc-wp-roles' ),
			'id'   => 'notes',
			'type' => 'textarea_small',
			'attributes' => [
				'rows' => 2,
			],
		] );
		$meta_box->add_group_field( $group_id, [
			'name' => __( 'Connection', 'oidc-wp-roles' ),
			'desc' => '<strong>' . __( 'Required', 'oidc-wp-roles' ) . '</strong><br>' .
				__( 'Which connection should be used to find this data.', 'oidc-wp-roles' ),
			'id'   => 'connection',
			'type' => 'select',
			'options' => $this->getConnectionOptions(),
			'attributes' => [
				'required' => 'required',
			],
		] );
		$meta_box->add_group_field( $group_id, [
			'name' => __( 'Response Data - Row Path', 'oidc-wp-roles' ),
			'desc' => '<em>' . __( 'Optional', 'oidc-wp-roles' ) . '</em><br>' .
				__( 'If the desired value is nested deeply within the connection response data, use this field to narrow down the location where the value can be found.', 'oidc-wp-roles' ) . '<br>' .
				__( 'Use forward slashes (/) to traverse the response data properties.', 'oidc-wp-roles' ) . '<br>' .
				__( 'Example: ', 'oidc-wp-roles' ) . '<code>/top-level/2nd-level/0/property-in-first-item-of-2nd-level-array</code>',
			'id'   => 'response_data_row_path',
			'type' => 'text',
		] );
		$meta_box->add_group_field( $group_id, [
			'name' => __( 'Response Data - Row Key', 'oidc-wp-roles' ),
			'desc' => '<em>' . __( 'Optional', 'oidc-wp-roles' ) . '</em><br>' .
				__( 'Works in combination with <strong>Response Data - Row Value</strong>', 'oidc-wp-roles' ) . '<br>' .
				__( 'If your connection response data is further complicated by returning an array of values without properties, this can be used in combination with <strong>Response Data - Row Value</strong> to find a single row within that array.', 'oidc-wp-roles' ),
			'id'   => 'response_data_row_key',
			'type' => 'text',
		] );
		$meta_box->add_group_field( $group_id, [
			'name' => __( 'Response Data - Row Value', 'oidc-wp-roles' ),
			'desc' => '<em>' . __( 'Optional', 'oidc-wp-roles' ) . '</em><br>' .
				__( 'Works in combination with <strong>Response Data - Row Key</strong>', 'oidc-wp-roles' ) . '<br>' .
				__( 'The value of the <strong>Response Data - Row Key</strong> that identifies the correct row where the <strong>Test Value Key</strong> can be found.', 'oidc-wp-roles' ),
			'id'   => 'response_data_row_value',
			'type' => 'text',
		] );
		$meta_box->add_group_field( $group_id, [
			'name' => __( 'Value Key', 'oidc-wp-roles' ),
			'desc' => '<strong>' . __( 'Required', 'oidc-wp-roles' ) . '</strong><br>' .
				__( 'This property name within the connection response data where the value can be found.', 'oidc-wp-roles' ),
			'id'   => 'value_key',
			'type' => 'text',
			'attributes' => [
				'required' => 'required',
			],
		] );
		$meta_box->add_group_field( $group_id, [
			'name' => __( 'Data Target', 'oidc-wp-roles' ),
			'desc' => '<strong>' . __( 'Required', 'oidc-wp-roles' ) . '</strong><br>' .
				__( 'Choose where this data should be mapped.', 'oidc-wp-roles' ),
			'id'   => 'data_target',
			'type' => 'select',
			'options' => [
				'user_property' => __( 'Property on the WP_User object', 'oidc-wp-roles' ),
				'user_meta' => __( 'User meta data.', 'oidc-wp-roles' ),
			],
			'attributes' => [
				'required' => 'required',
			],
		] );
		$meta_box->add_group_field( $group_id, [
			'name' => __( 'Data Key', 'oidc-wp-roles' ),
			'desc' => '<strong>' . __( 'Required', 'oidc-wp-roles' ) . '</strong><br>' .
				__( 'Provide the name of the user property or meta data field where the value should be saved.', 'oidc-wp-roles' ),
			'id'   => 'data_key',
			'type' => 'text',
			'attributes' => [
				'required' => 'required',
			],
		] );
	}

	/**
	 * Get an array of connection names.
	 *
	 * @return array
	 */
	private function getConnectionOptions() {
		$connection_names = \array_column( $this->connectionSettings->get( 'connections', [] ), 'name' );
		return \array_combine( $connection_names, $connection_names );
	}

}
