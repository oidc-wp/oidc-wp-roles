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
class SettingsTabRoleMapping extends SettingsTabBase {

	/**
	 * @var \OidcRoles\Service\SettingsInterface
	 */
	private $connectionSettings;

	/**
	 * @var \OidcRoles\Service\SettingsInterface
	 */
	private $roleMappingSettings;

	/**
	 * @var \OidcRoles\Service\RoleManagerInterface
	 */
	private $roleManager;

	/**
	 * @inheritDoc
	 */
	public function id() {
		return $this->roleMappingSettings->optionName();
	}

	/**
	 * @inheritDoc
	 */
	public function title() {
		return __( 'Role Mapping', 'oidc-wp-roles' );
	}

	/**
	 * Register metabox.
	 */
	public function register( Container $container ) {
		$this->connectionSettings = $container->get( 'settings.connections' );
		$this->roleMappingSettings = $container->get( 'settings.role_mappings' );
		$this->roleManager = $container->get( 'role_manager' );

		$args = $this->parseMetaBoxArgs( [
			'id' => $this->id(),
			'tab_title' => $this->title(),
			'message_cb' => [ $this, 'filterCmbMessage' ],
		] );

		$meta_box = \new_cmb2_box( $args );
		$group_field_renderer = new GroupFieldRenderer( $meta_box );

		/*
		 * Custom Roles.
		 */
		$group_id = $meta_box->add_field( [
			'id' => 'roles',
			'name' => __( 'Roles', 'oidc-wp-roles' ),
			'desc' => __( 'If you do not want to use existing roles, use this field to define new roles that can be assigned to users.', 'oidc-wp-roles' ),
			'type' => 'group',
			'repeatable' => true,
			'before_group' => [ $this, 'displayExistingRoles' ],
			'render_row_cb' => [ $group_field_renderer, 'renderGroup' ],
			'sanitization_cb' => [ $this, 'sanitizeRolesGroup' ],
			'options' => [
				'group_title' => __( 'Role: {#name} -- Show admin: {#show_admin}', 'oidc-wp-roles' ),
				'add_button' => __( 'Add another role', 'oidc-wp-roles' ),
				'remove_button' => __( 'Remove role', 'oidc-wp-roles' ),
				'remove_confirm' => esc_html__( 'Are you sure you want to remove this role?', 'oidc-wp-roles' ),
				'closed' => !empty( $this->roleMappingSettings->get( 'roles', [] ) ),
				'sortable' => true,
			],
		] );
		$meta_box->add_group_field( $group_id, [
			'name' => __( 'Role Slug', 'oidc-wp-roles' ),
			'desc' => '<strong>' . __( 'Required', 'oidc-wp-roles' ) . '</strong><br>' .
				__( 'Machine safe name for this role. Lowercase and underscores instead of spaces.', 'oidc-wp-roles' ),
			'id'   => 'slug',
			'type' => 'text',
			'sanitization_cb' => [ $this, 'sanitizeRoleNames' ],
			'classes' => 'oidc-field-slugify',
		] );
		$meta_box->add_group_field( $group_id, [
			'name' => __( 'Role Name', 'oidc-wp-roles' ),
			'desc' => __( 'Human readable name for this role.', 'oidc-wp-roles' ),
			'id'   => 'name',
			'type' => 'text',
			'sanitization_cb' => [ $this, 'sanitizeRoleNames' ],
		] );
		$meta_box->add_group_field( $group_id, [
			'name' => __( 'Show admin', 'oidc-wp-roles' ),
			'desc' => __( 'Show admin toolbar and allow backend access for this role. This setting grants the "read" capability for the role.', 'oidc-wp-roles' ),
			'id'   => 'show_admin',
			'type' => 'checkbox',
		] );

		/*
		 * Map roles based on connections.
		 */
		$group_id = $meta_box->add_field( [
			'id' => 'role_mapping',
			'name' => __( 'Role Mapping', 'oidc-wp-roles' ),
			'desc' => __( 'Using data from the IDP user_info, assign roles to users when they login.', 'oidc-wp-roles' ),
			'type' => 'group',
			'repeatable' => true,
			'render_row_cb' => [ $group_field_renderer, 'renderGroup' ],
			'options' => [
				'group_title' => __( '{#} - Connection: {#connection} - Assign Role: {#role} if ( {#test_value_key} {#comparison_operator} {#comparison_value} )', 'oidc-wp-roles' ),
				'add_button' => __( 'Add another mapping', 'oidc-wp-roles' ),
				'remove_button' => __( 'Remove mapping', 'oidc-wp-roles' ),
				'remove_confirm' => esc_html__( 'Are you sure you want to remove this mapping?','oidc-wp-roles' ),
				'closed' => !empty( $this->roleMappingSettings->get( 'role_mapping', [] ) ),
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
		] );
		$meta_box->add_group_field( $group_id, [
			'name' => __( 'Response Data - Row Path', 'oidc-wp-roles' ),
			'desc' => '<em>' . __( 'Optional', 'oidc-wp-roles' ) . '</em><br>' .
				__( 'If your <strong>Comparison Value</strong> is nested deeply within the connection response data, use this field to narrow down the location where the <strong>Comparison Value</strong> can be found.', 'oidc-wp-roles' ) . '<br>' .
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
			'name' => __( 'Test Value Key', 'oidc-wp-roles' ),
			'desc' => '<strong>' . __( 'Required', 'oidc-wp-roles' ) . '</strong><br>' .
				__( 'This property name within the connection response data where the value that is compared against the <strong>Comparison Value</strong> can be found.', 'oidc-wp-roles' ),
			'id'   => 'test_value_key',
			'type' => 'text',
			'attributes' => [
				'required' => 'required',
			],
		] );
		$meta_box->add_group_field( $group_id, [
			'name' => __( 'Comparison Operator', 'oidc-wp-roles' ),
			'desc' => '<strong>' . __( 'Required', 'oidc-wp-roles' ) . '</strong><br>' .
				__( 'How the value found in connection response should be compared to <strong>Comparison Value</strong>.', 'oidc-wp-roles' ),
			'id'   => 'comparison_operator',
			'type' => 'select',
			'options' => Comparison::comparisonOperatorOptions(),
			'attributes' => [
				'required' => 'required',
			],
		] );
		$meta_box->add_group_field( $group_id, [
			'name' => __( 'Comparison Value', 'oidc-wp-roles' ),
			'desc' => '<strong>' . __( 'Required', 'oidc-wp-roles' ) . '</strong><br>' .
				__( 'The value found in the connection response (identified by the <strong>Test Value Key</strong>) is compared against this value using the <strong>Comparison Operator</strong>.', 'oidc-wp-roles' ),
			'id'   => 'comparison_value',
			'type' => 'text',
			'attributes' => [
				'required' => 'required',
			],
		] );
		$meta_box->add_group_field( $group_id, [
			'name' => __( 'WordPress Role', 'oidc-wp-roles' ),
			'desc' => '<strong>' . __( 'Required', 'oidc-wp-roles' ) . '</strong><br>' .
				__( 'When the comparison between the connection response data and the <strong>Comparison Value</strong> is successful, grant the WordPress user this role.', 'oidc-wp-roles' ),
			'id'   => 'role',
			'type' => 'select',
			'options' => $this->roleManager->getAllRolesAsOptionsArray(),
			'attributes' => [
				'required' => 'required',
			],
		] );
	}

	/**
	 * Show a list of existing roles.
	 */
	public function displayExistingRoles() {
		$non_oidc_roles = $this->roleManager->getNonOidcWpRoles();
		$oidc_roles = $this->roleManager->getOidcWpRoles();
		?>
		<div class="cmb-row">
			<h3><?php _e( 'Existing roles' ); ?></h3>
			<p class="description"><?php _e( 'Roles defined by WordPress or other plugins.' ); ?></p>
			<hr>
			<ul style="list-style: disc; padding-left: 20px;">
				<?php foreach( $non_oidc_roles as $role ) { ?>
					<li><?= \esc_html( $role['slug'] . '|' . $role['name'] ); ?></li>
				<?php } ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Handles sanitization for the roles field group as a whole
	 *
	 * Note this sanitization method runs before the sanitization methods for the group's individual fields. The reason
	 * for this additional layer is to filter out array items (i.e. roles to be registered) that are not allowed to be
	 * defined by this plugin (e.g. administrator)
	 *
	 * @param array $roles
	 *   The unsanitized group of values from the form
	 * @param array $field_args
	 *   Array of field arguments.
	 * @param \CMB2_Field $field
	 *   The field object
	 *
	 * @return array
	 *   Sanitized group of values to be stored.
	 */
	public function sanitizeRolesGroup( $roles, $field_args, $field ) {
		$non_oidc_role_slugs = array_column( $this->roleManager->getNonOidcWpRoles(), 'slug' );

		foreach($roles as $index => $role) {
			if( in_array( $role['slug'], $non_oidc_role_slugs ) ) {
				unset( $roles[ $index ] );
			}
		}
		return array_values( $roles );
	}

	/**
	 * Handles sanitization for the roles field.
	 *
	 * @param string $values
	 *   The unsanitized value from the form.
	 * @param array $field_args
	 *   Array of field arguments.
	 * @param \CMB2_Field $field
	 *   The field object
	 *
	 * @return string
	 *   Sanitized value to be stored.
	 */
	public function sanitizeRoleNames( $values, $field_args, $field ) {
		return \sanitize_text_field( \trim( $values ) );
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
