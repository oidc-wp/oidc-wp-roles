<?php

namespace OidcRoles\Admin;

use DI\Container;

/**
 * Class SettingsTabGeneral.
 *
 * @package OidcRoles\Admin
 */
class SettingsTabImportExport extends SettingsTabBase {

	/**
	 * @var \OidcRoles\Service\SettingsInterface
	 */
	private $generalSettings;

	/**
	 * @var \OidcRoles\Service\SettingsInterface
	 */
	private $connectionSettings;

	/**
	 * @var \OidcRoles\Service\SettingsInterface
	 */
	private $roleMappingsSettings;

	/**
	 * @var \OidcRoles\Service\SettingsInterface
	 */
	private $fieldMappingsSettings;

	/**
	 * {@inheritdoc}
	 */
	public function id() {
		return 'oidc_wp_roles_settings_import_export';
	}

	/**
	 * {@inheritdoc}
	 */
	public function title() {
		return __( 'Settings Export/Import', 'oidc-wp-roles' );
	}

	/**
	 * Registers General options tab and main admin menu item
	 */
	public function register( Container $container ) {
		$this->generalSettings = $container->get( 'settings.general' );
		$this->connectionSettings = $container->get( 'settings.connections' );
		$this->roleMappingsSettings = $container->get( 'settings.role_mappings' );
		$this->fieldMappingsSettings = $container->get( 'settings.field_mappings' );

		$args = $this->parseMetaBoxArgs( [
			'id' => $this->id(),
			'tab_title' => $this->title(),
			'message_cb' => [ $this, 'filterCmbMessage' ],
		] );

		$meta_box = new_cmb2_box( $args );
		$meta_box->add_field( [
			'name' => __( 'Import Settings', 'oidc-wp-roles' ),
			'id'   => 'import_settings',
			'type' => 'textarea_code',
			'before_field' => '<p class="cmb2-metabox-description">' . __( 'Paste JSON from settings export here.', 'oidc-wp-roles' ) . '</p>',
			'save_field' => false,
			'sanitization_cb' => [ $this, 'importSettings' ],
			'attributes' => [
				'data-codeeditor' => json_encode( [
					'codemirror' => [
						'mode' => 'json',
					],
				] ),
			],
		] );

		$meta_box->add_field( [
			'name' => __( 'Export Settings', 'oidc-wp-roles' ),
			'id'   => 'export_settings',
			'type' => 'textarea',
			'before_field' => '<p class="cmb2-metabox-description">' . __( 'Copy these settings to another site.', 'oidc-wp-roles' ) . '</p>',
			'save_field' => false,
			'default_cb' => [ $this, 'exportSettings' ],
			'attributes' => [
				'readonly' => 'readonly',
			],
		] );
	}

	/**
	 * Uses the sanitize callback to update options for other settings, but
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
	public function importSettings( $values, $field_args, $field ) {
		$values = stripcslashes( $values );
		$import = \json_decode( $values, true );
		if ( ! $import ) {
			$this->setMessage( [
				'text' => __( 'There was a json_decode error with the settings. Please review the code and try again.', 'oidc-wp-roles' ),
				'type' => 'error',
			] );

			return '';
		}

		$updated = false;
		if ( !empty( $import['general'] ) && is_array( $import['general'] ) ) {
			$updated = \update_option( $this->generalSettings->optionName(), $import['general'] ) || $updated;
		}
		if ( !empty( $import['connections'] ) && is_array( $import['connections'] ) ) {
			$updated = \update_option( $this->connectionSettings->optionName(), $import['connections'] ) || $updated;
		}
		if ( !empty( $import['field_mappings'] ) && is_array( $import['field_mappings'] ) ) {
			$updated = \update_option( $this->fieldMappingsSettings->optionName(), $import['field_mappings'] ) || $updated;
		}
		if ( !empty( $import['role_mappings'] ) && is_array( $import['role_mappings'] ) ) {
			$updated = \update_option( $this->roleMappingsSettings->optionName(), $import['role_mappings'] ) || $updated;
		}

		if( $updated ) {
			$this->setMessage( [
				'text' => __( 'Setting imported' ),
				'type' => 'success',
			] );
		}

		return '';
	}

	/**
	 * @return false|string
	 */
	public function exportSettings() {
		$export = [
			'general' => $this->generalSettings->getValues(),
			'connections' => $this->connectionSettings->getValues(),
			'field_mappings' => $this->fieldMappingsSettings->getValues(),
			'role_mappings' => $this->roleMappingsSettings->getValues(),
		];

		return \json_encode( $export, JSON_PRETTY_PRINT );
	}

	/**
	 * @inheritDoc
	 * We're removing the check for $args['is_updated'] from the base class here, because these settings are never
	 * actually saved to the database and therefore 'is_updated' is always false. Instead, we're just checking for the
	 * existence of a message
	 */
	public function filterCmbMessage( $cmb, $args ) {
		if ( ! empty( $args['should_notify'] ) ) {

			if( $message = $this->getMessage() ) {
				if( isset( $message['text'] ) ) {
					$args['message'] = $message['text'];
				}
				if( isset( $message['type'] ) ) {
					$args['type'] = $message['type'];
				}

				$this->deleteMessage();
			}

			add_settings_error( $args['setting'], $args['code'], $args['message'], $args['type'] );
		}
	}
}
