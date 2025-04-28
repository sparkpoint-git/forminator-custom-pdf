<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_PDF_Addon_Upgrade
 *
 * Handle any installation upgrade or install tasks
 */
class Forminator_PDF_Addon_Upgrade {

	/**
	 * Initialise data before plugin is fully loaded
	 *
	 * @since 1.0
	 */
	public static function init() {
		/**
		 * Initialize the plugin data
		 */
		$old_version = get_option( 'forminator_pdf_addon_version', false );
		if ( $old_version ) {
			$version_changed = version_compare( $old_version, FORMINATOR_PDF_ADDON, 'lt' );

			if ( $version_changed ) {
				update_option( 'forminator_pdf_addons_version_upgraded', true );
			}
		} else {
			$version_changed = true;
		}
		if ( $version_changed ) {
			// Update tables if required.

			add_action( 'admin_init', array( __CLASS__, 'flush_rewrite' ) );

			// Update version.
			update_option( 'forminator_pdf_addon_version', FORMINATOR_VERSION );

			add_action(
				'forminator_pdf_addon_loaded',
				function () use ( $old_version ) {
					/**
					 * Triggered when Forminator PDF version is updated
					 *
					 * @param string FORMINATOR_PDF_ADDON New plugin version
					 * @param string $old_version Old plugin version.
					 */
					do_action( 'forminator_pdf_addon_update_version', FORMINATOR_PDF_ADDON, $old_version );
				}
			);
		}
	}

	public static function flush_rewrite() {
		// Flush rewrite rules.
		flush_rewrite_rules();
	}
}