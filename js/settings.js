/**
 * JS for the plugin settings page
 */
(function($) {

	/**
	 * Slugify fields.
	 */
	$(document).ready(function() {
		var $textFields = $('.oidc-field-slugify input[type=text]');
		if( ! $textFields.length ) {
			return;
		}

		// Convert text into a slug.
		$textFields.on('keyup', function() {
			var $el = $(this);
			var slug = $el.val().toLowerCase().replace(/ /g, '_');
			$el.val(slug);
		} );
		// Remove beginning and ending underscores in case of stray spaces.
		$textFields.on('change', function() {
			var $el = $(this);
			$el.val( $el.val().replace(/^_/, '').replace(/_$/, '') );
		} )
	});

	/**
	 * CMB2 group row titles - temporary.
	 * PR: https://github.com/CMB2/CMB2/pull/1422
	 */
	// Update all group row titles with token replacements.
	function updateGroupRowTitles() {
		var $metabox = $( this );

		// Loop repeatable group tables
		$metabox.find( '.cmb-repeatable-group.repeatable' ).each( function() {
			var $table = $( this );

			// Loop repeatable group table rows
			$table.find( '.cmb-repeatable-grouping' ).each( function( rowindex ) {
				var groupTitle = $table.find( '.cmb-add-group-row' ).data( 'grouptitle' );
				var $row = $( this );
				var $rowTitle = $row.find( 'h3.cmb-group-title' );
				// Reset rows iterator
				$row.data( 'iterator', rowindex );
				// Reset rows title
				if ( $rowTitle.length ) {
					groupTitle = groupTitle.replace( '{#}', ( rowindex + 1 ) )
					$row.find( 'input,select' ).each( function() {
						var $element = $( this );
						var fieldIdRaw = $element.attr( 'name' ).replace( /]/g, '' ).split( '[' ).pop();
						if ( fieldIdRaw ) {
							groupTitle = groupTitle.replace( '{#' + fieldIdRaw + '}', $element.val() )
						}
					} );
					$rowTitle.text( groupTitle );
				}
			});
		});
	}
	$(document).ready(function() {
		$('.cmb2-wrap > .cmb2-metabox')
			.on( 'cmb2_add_row cmb2_remove_row cmb2_shift_rows_complete', updateGroupRowTitles )
	});

})( jQuery );
