<?php

namespace OidcRoles\Admin;

use DI\Container;

/**
 * Class SettingsTabGeneral.
 *
 * @package OidcRoles\Admin
 */
class SettingsTabTesting extends SettingsTabBase {

	/**
	 * @var \OidcRoles\Service\MappingsManagerInterface
	 */
	private $mappingsManager;

	/**
	 * {@inheritdoc}
	 */
	public function id() {
		return 'oidc_wp_roles_testing';
	}

	/**
	 * {@inheritdoc}
	 */
	public function title() {
		return __( 'Testing', 'oidc-wp-roles' );
	}

	/**
	 * Registers General options tab and main admin menu item
	 */
	public function register( Container $container) {
		$this->mappingsManager = $container->get( 'mappings_manager' );

		$args = $this->parseMetaBoxArgs( [
			'id' => $this->id(),
			'tab_title' => $this->title(),
			'save_button' => __( 'Test Connection', 'oidc-wp-roles' ),
			'message_cb' => [ $this, 'testConnection' ],
			'display_cb'   => [ $this, 'displayAsTab' ],
		] );

		$meta_box = new_cmb2_box( $args );

		$meta_box->add_field( [
			'name' => __( 'User ID or Email', 'oidc-wp-roles' ),
			'desc' => __( 'The WP User ID or Email that should attempt the connection. Leave blank to test against yourself.', 'oidc-wp-roles' ),
			'id'   => 'user',
			'type' => 'text',
		] );
		$meta_box->add_field( [
			'name' => __( 'Connection', 'oidc-wp-roles' ),
			'desc' => __( 'Select which connection should be used test mappings.', 'oidc-wp-roles' ),
			'id'   => 'connection',
			'type' => 'select',
			'options' => $this->getConnectionOptions(),
		] );
		$meta_box->add_field( [
			'name' => __( 'Response Data', 'oidc-wp-roles' ),
			'desc' => __( 'Provide example connection response data as JSON.', 'oidc-wp-roles' ),
			'id'   => 'response_data',
			'default' => "{}",
			'type' => 'textarea_code',
			'attributes' => [
				'data-codeeditor' => json_encode( [
					'codemirror' => [
						'mode' => 'json',
					],
				] ),
			],
		] );
	}

	/**
	 * Perform the connection test.
	 *
	 * @param \CMB2 $cmb
	 * @param array $args
	 */
	public function testConnection( \CMB2 $cmb, array $args ) {
		$settings = get_option( $this->id(), [] );
		if ( empty( $args['should_notify'] ) || empty( $settings ) ) {
			return;
		}

		$data = \json_decode( $settings['response_data'], TRUE );
		$user = wp_get_current_user();
		if ( !empty( $settings['user'] ) ) {
			$user = is_numeric( $settings['user'] ) ?
				get_user_by( 'id', (int) $settings['user'] ) :
				get_user_by( 'email', $settings['user'] );
			$meta = \get_user_meta( $user->ID, "oidc_wp_roles--connection-response--{$settings['connection']}", TRUE );
			if (!empty($meta)) {
				$data = $meta;
			}
		}

		$this->mappingsManager->setUser( $user );
		$collections = $this->mappingsManager->getConnectionClientMappingCollections();
		$collection = $collections[ $settings['connection'] ];

		$role_mapping_results = $this->mappingsManager->getMappingsResults( $data, $collection->getRoleMappings() );
		$field_mapping_results = $this->mappingsManager->getMappingsResults( $data, $collection->getFieldMappings() );
		\ob_start();
			?>
			<h3><?php _e( 'Role Mapping Results' ) ?></h3>
			<table class="widefat">
				<thead>
					<tr>
						<th><?php _e( 'Result' ) ?></th>
						<th><?php _e( 'Comparison' ) ?> <small><code>if (<?php _e('[test] [operator] [response]') ?>)</code></small></th>
						<th><?php _e( 'Description' ) ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $role_mapping_results as $comparison_result )
					{
						$test_value = $comparison_result->testValue();
						if ( is_array( $test_value ) ) {
							$test_value = \json_encode( $test_value );
						}
						?>
					<tr class="oidc-wp-test-row-<?php echo ($comparison_result->success() ? 'success' : 'fail') ?>">
						<td><?php echo $comparison_result->success() ? __( 'Success' ) : __( 'Fail', 'oidc-wp-roles' ) ?></td>
						<td><code><?php echo "if ({$test_value} {$comparison_result->operator()} {$comparison_result->comparisonValue()})" ?></code></td>
						<td><?php echo $comparison_result->description() ?></td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
			<hr>
			<h3><?php _e( 'Field Mapping Results' ) ?></h3>
			<table class="widefat">
				<thead>
				<tr>
					<th><?php _e( 'Result' ) ?></th>
					<th><?php _e( 'Description' ) ?></th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ( $field_mapping_results as $mapping_result ) { ?>
					<tr class="oidc-wp-test-row-<?php echo ($mapping_result->success() ? 'success' : 'fail') ?>">
						<td><?php echo $mapping_result->success() ? __( 'Success' ) : __( 'Fail', 'oidc-wp-roles' ) ?></td>
						<td><?php echo $mapping_result->description() ?></td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
			<hr>
			<h3><?php _e( 'Response', 'oidc-wp-roles' ) ?></h3>
			<pre class="oidc-wp-roles-admin-pre"><?php echo \json_encode($data, JSON_PRETTY_PRINT) ?></pre>
			<?php
		$message = \ob_get_clean();

		$this->setMessage([
			'text' => $message,
			'type' => 'success',
		]);
	}

	/**
	 * @param \CMB2_Options_Hookup $cmb_options
	 */
	public function displayAsTab( $cmb_options, string $before_box = '' ) {
		parent::displayAsTab( $cmb_options );
		$message = $this->getMessage();
		if ( !empty($message) ) {
			$this->deleteMessage();
			echo $message['text'];
		}
	}

	/**
	 * Get an array of connection names.
	 *
	 * @return array
	 */
	private function getConnectionOptions() {
		$connection_names = array_keys( $this->mappingsManager->getConnectionClientMappingCollections() );
		return array_combine( $connection_names, $connection_names );
	}

}
