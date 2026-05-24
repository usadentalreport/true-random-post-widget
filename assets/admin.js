/**
 * True Random Post Widget – Admin JS
 *
 * Handles:
 *  1. WP Color Picker initialisation (settings page)
 *  2. Show/hide global image row based on radio selection
 *  3. WP Media Library picker for global image (settings page)
 *  4. WP Media Library picker for taxonomy term logo (term edit page)
 */
/* global wp, trpwAdmin, jQuery */
(function ( $ ) {
	'use strict';

	/* ─── 1. Colour pickers (settings page only) ────────────────────── */
	if ( trpwAdmin.onSettingsPage === '1' ) {
		$( '.trpw-color-picker' ).wpColorPicker();
	}

	/* ─── 2. Show / hide global image row ───────────────────────────── */
	function syncImageRow() {
		var val = $( 'input[name="trpw_image_source"]:checked' ).val();
		$( '#trpw-global-image-row' ).toggle( val === 'global' );
	}

	$( 'input[name="trpw_image_source"]' ).on( 'change', syncImageRow );
	// Run once on page load in case "global" is already selected.
	syncImageRow();

	/* ─── 3. Global image picker ────────────────────────────────────── */
	var globalFrame;

	$( document ).on( 'click', '#trpw-select-global-image', function ( e ) {
		e.preventDefault();

		if ( globalFrame ) {
			globalFrame.open();
			return;
		}

		globalFrame = wp.media( {
			title    : trpwAdmin.mediaTitle,
			button   : { text: trpwAdmin.mediaButton },
			library  : { type: 'image' },
			multiple : false,
		} );

		globalFrame.on( 'select', function () {
			var attachment = globalFrame.state().get( 'selection' ).first().toJSON();
			var thumb      = ( attachment.sizes && attachment.sizes.thumbnail )
				? attachment.sizes.thumbnail.url
				: attachment.url;

			$( '#trpw-global-image-id' ).val( attachment.id );
			$( '#trpw-global-image-preview' ).html(
				'<img src="' + thumb + '" style="max-width:150px;height:auto;display:block;margin-bottom:8px;border:1px solid #ddd;border-radius:4px;" />'
			);
			$( '#trpw-select-global-image' ).text( trpwAdmin.changeText );
			$( '#trpw-remove-global-image' ).show();
		} );

		globalFrame.open();
	} );

	$( document ).on( 'click', '#trpw-remove-global-image', function ( e ) {
		e.preventDefault();
		$( '#trpw-global-image-id' ).val( '' );
		$( '#trpw-global-image-preview' ).html( '' );
		$( '#trpw-select-global-image' ).text( trpwAdmin.selectText );
		$( this ).hide();
	} );

	/* ─── 4. Term logo picker ───────────────────────────────────────── */
	var termFrame;

	$( document ).on( 'click', '#trpw-select-term-logo', function ( e ) {
		e.preventDefault();

		if ( termFrame ) {
			termFrame.open();
			return;
		}

		termFrame = wp.media( {
			title    : trpwAdmin.termMediaTitle,
			button   : { text: trpwAdmin.termMediaButton },
			library  : { type: 'image' },
			multiple : false,
		} );

		termFrame.on( 'select', function () {
			var attachment = termFrame.state().get( 'selection' ).first().toJSON();
			var thumb      = ( attachment.sizes && attachment.sizes.thumbnail )
				? attachment.sizes.thumbnail.url
				: attachment.url;

			$( '#trpw-term-logo-id' ).val( attachment.id );
			$( '#trpw-term-logo-preview' ).html(
				'<img src="' + thumb + '" style="max-width:80px;height:auto;display:block;margin-bottom:6px;border:1px solid #ddd;border-radius:4px;" />'
			);
			$( '#trpw-select-term-logo' ).text( trpwAdmin.changeLogoText );
			$( '#trpw-remove-term-logo' ).show();
		} );

		termFrame.open();
	} );

	$( document ).on( 'click', '#trpw-remove-term-logo', function ( e ) {
		e.preventDefault();
		$( '#trpw-term-logo-id' ).val( '' );
		$( '#trpw-term-logo-preview' ).html( '' );
		$( '#trpw-select-term-logo' ).text( trpwAdmin.selectLogoText );
		$( this ).hide();
	} );

} )( jQuery );
