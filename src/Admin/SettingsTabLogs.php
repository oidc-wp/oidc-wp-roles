<?php

namespace OidcRoles\Admin;

use DI\Container;

/**
 * Class SettingsTabGeneral.
 *
 * @package OidcRoles\Admin
 */
class SettingsTabLogs extends SettingsTabBase {

	/**
	 * @var \OidcRoles\Service\LoggerFactory
	 */
	private $loggerFactory;

	/**
	 * {@inheritdoc}
	 */
	public function id() {
		return 'oidc_wp_roles_logs';
	}

	/**
	 * {@inheritdoc}
	 */
	public function title() {
		return __( 'Logs', 'oidc-wp-roles' );
	}

	/**
	 * Registers General options tab and main admin menu item
	 */
	public function register( Container $container ) {
		$this->loggerFactory = $container->get( 'logger_factory' );
		$logger = $container->get( 'logger.default' );
		//$logger->info( __('visited logs page ' . time()) );

		$args = $this->parseMetaBoxArgs( [
			'id' => $this->id(),
			'tab_title' => $this->title(),
			'message_cb' => [ $this, 'filterCmbMessage' ],
			'save_fields' => false,
			// @todo
			'save_button' => __( 'Clear Logs', 'oidc-wp-roles' ),
		] );

		$meta_box = new_cmb2_box( $args );
	}

	/**
	 * @param \CMB2_Options_Hookup $cmb_options
	 */
	public function displayAsTab( $cmb_options, string $before_box = ''  ) {
		global $wpdb;
		$handler = $this->loggerFactory->getDbHandler();
		$rows = $wpdb->get_results("SELECT * FROM {$handler->get_table_name()} ORDER BY id DESC LIMIT 1000", ARRAY_A);

		if (!empty($rows)) {
			$keys = array_keys($rows[0]);
			ob_start();
			?>
			<h3>Logs</h3>
			<table class="wp-list-table widefat fixed striped">
				<thead>
				<?php foreach ($keys as $key) { ?>
					<th><?= $key ?></th>
				<?php }	?>
				</thead>
				<tbody>
				<?php foreach($rows as $row) { ?>
					<tr>
						<?php foreach ($keys as $key) { ?>
							<td><?= $row[$key] ?></td>
						<?php } ?>
					</tr>
				<?php } ?>
				</tbody>
			</table>
			<?php
			$before_box = ob_get_clean();
		}

		parent::displayAsTab( $cmb_options, $before_box );
	}

}
