<?php
/**
 * @file
 * This is a temporary measure. Submitted a PR to CMB2 to add this functionality:
 * https://github.com/CMB2/CMB2/pull/1422
 */
namespace OidcRoles\Cmb2;

/**
 * Class GroupFieldRenderer.
 *
 * @package OidcRoles\Cmb2
 */
class GroupFieldRenderer {

	/**
	 * Metabox instance.
	 *
	 * @var \CMB2
	 */
	private $cmb2;

	/**
	 * GroupFieldRenderer constructor.
	 *
	 * @param \CMB2 $cmb2
	 *   Meta box instance.
	 */
	public function __construct( \CMB2 $cmb2 ) {
		$this->cmb2 = $cmb2;
	}

	/**
	 * Get missing values on the cmb2 class.
	 *
	 * @param string $name
	 *   Method name.
	 *
	 * @return mixed|null
	 */
	public function __get( string $name ) {
		if ( $this->cmb2->{$name} ) {
			return $this->cmb2->{$name};
		}
	}

	/**
	 * Call missing methods on the cmb2 class.
	 *
	 * @param string $name
	 *   Method name.
	 * @param array $args
	 *   Method parameters.
	 *
	 * @return mixed|null
	 */
	public function __call( string $name, array $args = [] ) {
		return call_user_func_array( [ $this->cmb2, $name ], $args );
	}

	/**
	 * Copy of \CMB2::render_group_callback().
	 *
	 * @see \CMB2::render_group_callback().
	 *
	 * @param  array      $field_args  Array of field arguments for the group field parent.
	 * @param  \CMB2_Field $field_group The CMB2_Field group object.
	 *
	 * @return \CMB2_Field|null Group field object.
	 */
	public function renderGroup( $field_args, $field_group ) {
		// If field is requesting to be conditionally shown.
		if ( ! $field_group || ! $field_group->should_show() ) {
			return;
		}

		$field_group->index = 0;

		$field_group->peform_param_callback( 'before_group' );

		$desc      = $field_group->args( 'description' );
		$label     = $field_group->args( 'name' );
		$group_val = (array) $field_group->value();

		echo '<div class="cmb-row cmb-repeat-group-wrap ', esc_attr( $field_group->row_classes() ), '" data-fieldtype="group"><div class="cmb-td"><div data-groupid="', esc_attr( $field_group->id() ), '" id="', esc_attr( $field_group->id() ), '_repeat" ', $this->group_wrap_attributes( $field_group ), '>';

		if ( $desc || $label ) {
			$class = $desc ? ' cmb-group-description' : '';
			echo '<div class="cmb-row', $class, '"><div class="cmb-th">';
			if ( $label ) {
				echo '<h2 class="cmb-group-name">', $label, '</h2>';
			}
			if ( $desc ) {
				echo '<p class="cmb2-metabox-description">', $desc, '</p>';
			}
			echo '</div></div>';
		}

		if ( ! empty( $group_val ) ) {
			foreach ( $group_val as $group_key => $field_id ) {
				$this->renderGroupRow( $field_group );
				$field_group->index++;
			}
		} else {
			$this->renderGroupRow( $field_group );
		}

		if ( $field_group->args( 'repeatable' ) ) {
			echo '<div class="cmb-row"><div class="cmb-td"><p class="cmb-add-row"><button type="button" data-selector="', esc_attr( $field_group->id() ), '_repeat" data-grouptitle="', esc_attr( $field_group->options( 'group_title' ) ), '" class="cmb-add-group-row button-secondary">', $field_group->options( 'add_button' ), '</button></p></div></div>';
		}

		echo '</div></div></div>';

		$field_group->peform_param_callback( 'after_group' );

		return $field_group;
	}

	/**
	 * Copy of \\CMB2::render_group_row()
	 *
	 * @see \CMB2::render_group_row()
	 *
	 * @param \CMB2_Field $field_group
	 *   CMB2_Field group field object.
	 *
	 * @return \CMB2
	 */
	public function renderGroupRow( $field_group ) {
		$field_group->peform_param_callback( 'before_group_row' );
		$closed_class     = $field_group->options( 'closed' ) ? ' closed' : '';
		$confirm_deletion = $field_group->options( 'remove_confirm' );
		$confirm_deletion = ! empty( $confirm_deletion ) ? $confirm_deletion : '';

		echo '
		<div id="cmb-group-', $field_group->id(), '-', $field_group->index, '" class="postbox cmb-row cmb-repeatable-grouping', $closed_class, '" data-iterator="', $field_group->index, '">';

		if ( $field_group->args( 'repeatable' ) ) {
			echo '<button type="button" data-selector="', $field_group->id(), '_repeat" data-confirm="', esc_attr( $confirm_deletion ), '" class="dashicons-before dashicons-no-alt cmb-remove-group-row" title="', esc_attr( $field_group->options( 'remove_button' ) ), '"></button>';
		}

		/*
		 * Start changes.
		 */
		// Gather field values for group_title replacements.
		$fields       = [];
		$field_values = [];
		foreach ( array_values( $field_group->args( 'fields' ) ) as $field_args ) {
			if ( 'hidden' === $field_args['type'] ) {
				// Save rendering for after the metabox.
				$this->add_hidden_field( $field_args, $field_group );
			} else {
				$field_args['show_names'] = $field_group->args( 'show_names' );
				$field_args['context']    = $field_group->args( 'context' );
				$field = $this->get_field( $field_args, $field_group );
				$field_values[ $field->id( true ) ] = $field->value();
				$fields[ $field->id() ] = $field;
			}
		}

		$group_title = $this->replaceHash( ( $field_group->index + 1 ), $field_group->options( 'group_title' ), $field_values );

		echo '
			<div class="cmbhandle" title="' , esc_attr__( 'Click to toggle', 'cmb2' ), '"><br></div>
			<h3 class="cmb-group-title cmbhandle-title"><span>', $group_title, '</span></h3>

			<div class="inside cmb-td cmb-nested cmb-field-list">';
		// Loop and render repeatable group fields.
		foreach ( $fields as $field ) {
			$field->render_field();
		}
		/*
		 * End changes.
		 */

		if ( $field_group->args( 'repeatable' ) ) {
			echo '
				<div class="cmb-row cmb-remove-field-row">
					<div class="cmb-remove-row">
						<button type="button" data-selector="', $field_group->id(), '_repeat" data-confirm="', esc_attr( $confirm_deletion ), '" class="cmb-remove-group-row cmb-remove-group-row-button alignright button-secondary">', $field_group->options( 'remove_button' ), '</button>
					</div>
				</div>
				';
		}
		echo '
			</div>
		</div>
		';

		$field_group->peform_param_callback( 'after_group_row' );

		return $this->cmb2;
	}

	/**
	 * Replaces a hash key - {#} - with the repeatable index
	 *
	 * @param string $value       Value to update.
	 * @param array $replacements Replacement key value pairs.
	 *
	 * @return string        Updated value
	 */
	public function replaceHash( $index, $value, $replacements = [] ) {
		// Replace hash with 1 based count.
		$result = str_replace( '{#}', $index, $value );
		foreach ( $replacements as $search => $replace ) {
			$result = str_replace( "{#$search}", $replace, $result );
		}
		return $result;
	}

}
