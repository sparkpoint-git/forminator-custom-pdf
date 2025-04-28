<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Plugin Name: Forminator PDF Generator Add-on
 * Version: 1.8.0
 * Plugin URI:  https://wpmudev.com/project/forminator/
 * Description: Generate and send PDF files (e.g., form entries, receipts, invoices, quotations) to users after form submission.
 * Author: WPMU DEV
 * Author URI: https://wpmudev.com
 * Text Domain: forminator-addons-pdf
 * Domain Path: /languages/
 * WDP ID: 4262971
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! defined( 'FORMINATOR_PDF_ADDON' ) ) {
	define( 'FORMINATOR_PDF_ADDON', '1.8.0' );
}

/**
 * Class Forminator_PDF_Addon
 *
 * Main class. Initialize add-on
 *
 * @since 1.0.0
 */
if ( ! class_exists( 'Forminator_PDF_Addon' ) ) {

	/**
	 * PDF Add-on class
	 */
	class Forminator_PDF_Addon {
		/**
		 * Plugin instance
		 *
		 * @since 1.0.0
		 * @var null
		 */
		private static $instance = null;

		/**
		 * Minimum version of Forminator, that the addon will work correctly
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected $_min_forminator_version = '1.38.0';

		/**
		 * Return the plugin instance
		 *
		 * @return Forminator_PDF_Addon
		 * @since 1.0.0
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			if ( $this->is_supported_version() ) {
				add_action( 'forminator_pdf_addon_loaded', array( $this, 'forminator_pdf_addon_loaded' ), 10 );

				/**
				 * Triggered when plugin is loaded
				 */
				do_action( 'forminator_pdf_addon_loaded' );
			} else {
				// Initialize PDF generation after main plugin loaded only if Forminator version is supported.
				add_action( 'admin_notices', array( $this, 'forminator_pdf_show_admin_notice' ) );
			}
		}

		/**
		 * Initialise PDF Generation class
		 *
		 * @since 1.0.0
		 */
		public function forminator_pdf_addon_loaded() {
			// Core files.
			include_once forminator_pdf_addon_plugin_dir() . 'core/class-core.php';

			Forminator_PDF_Addon_Core::get_instance();
		}

		/**
		 * Check if Forminator version is supported
		 *
		 * @return bool
		 * @since 1.0.0
		 *
		 */
		public function is_supported_version() {
			if ( defined( 'FORMINATOR_VERSION' ) ) {
				$is_forminator_version_supported = version_compare( FORMINATOR_VERSION, $this->_min_forminator_version, '>=' );

				if ( $is_forminator_version_supported > 0 ) {
					return true;
				}
			}

			return false;
		}

		public function prefix_plugin_update_message( $data, $response ) { ?>
			<tr class="plugin-update-tr" id="forminator-update" data-slug="forminator-addons-pdf"
				data-plugin="forminator-addons-pdf/forminator-addons-pdf.php">
				<td colspan="4" class="plugin-update colspanchange">
					<div class="notice inline notice-warning notice-alt">
						<p><?php printf( esc_html__( 'Forminator %s is required! Activate it now or download it today!', 'forminator-addons-pdf' ), $this->_min_forminator_version ); ?></p>
					</div>
				</td>
			</tr>
			<?php
		}

		/**
		 * Show pdf admin notice
		 *
		 * @return void
		 */
		public function forminator_pdf_show_admin_notice() {
			global $pagenow;
			$page = (string) filter_input( INPUT_GET, 'page' );
			if ( 'forminator' === substr( $page, 0, 10 ) || 'plugins.php' === $pagenow ) {
				?>
				<div class="notice notice-error">
					<p>
						<?php
						printf(
							esc_html__( '%1$sForminator PDF Generator Add-on%2$s requires the latest version of Forminator Pro in order to work. Please install and activate the latest version %3$shere%4$s.', 'forminator-addons-pdf' ),
							'<strong>',
							'</strong>',
							'<a href="https://wpmudev.com/project/forminator-pro/" target="_blank">',
							'</a>'
						);
						?>
					</p>
				</div>
				<?php
			}
		}
	}

	/**
	 * PDF notice for forminator version
	 *
	 * @param string $plugin The plugin name.
	 *
	 * @return void
	 */
	function forminator_pdf_check_main( $plugin ) {
		if ( class_exists( 'Forminator' ) || 'forminator-addons-pdf/forminator-addons-pdf.php' !== $plugin ) {
			return;
		}
		wp_die(
			sprintf(
				/* translators: 1. Open H1 tag. 2. Close H1 tag and open P tag. 3. Open A tag. 4. Close A tag. 5. Close P tag. */
				esc_html__( '%1$sForminator PDF Generator Add-on%2$s requires the latest version of Forminator Pro in order to work. Please install and activate the latest version %3$shere%4$s.%5$s', 'forminator-addons-pdf' ),
				'<h1>',
				'</h1><p>',
				'<a href="https://wpmudev.com/project/forminator-pro/" target="_blank">',
				'</a>',
				'</p>'
			),
			'',
			array(
				'response'  => 500,
				'back_link' => true,
			)
		);
	}

	add_action( 'activate_plugin', 'forminator_pdf_check_main', 11, 1 );

	function forminator_pdf_main_inactive_check() {
		if ( ! class_exists( 'Forminator' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
		}
	}

	add_action( 'admin_init', 'forminator_pdf_main_inactive_check' );
}

if ( ! function_exists( 'forminator_pdf_addon' ) ) {
	function forminator_pdf_addon() {
		return Forminator_PDF_Addon::get_instance();
	}

	/**
	 * Init the plugin and load the plugin instance
	 *
	 * @since 1.0.0
	 * The priority is set to -1 to support Forminator hooks.
	 */
	add_action( 'init', 'forminator_pdf_addon', -1 );
}

if ( ! function_exists( 'forminator_pdf_addon_plugin_url' ) ) {
	/**
	 * Return plugin URL
	 *
	 * @return string
	 * @since 1.0
	 */
	function forminator_pdf_addon_plugin_url() {
		return trailingslashit( plugin_dir_url( __FILE__ ) );
	}
}


if ( ! function_exists( 'forminator_pdf_addon_plugin_dir' ) ) {
	/**
	 * Return plugin path
	 *
	 * @return string
	 * @since 1.0
	 */
	function forminator_pdf_addon_plugin_dir() {
		return trailingslashit( plugin_dir_path( __FILE__ ) );
	}
}

if ( ! function_exists( 'forminator_pdf_addon_plugin_templates_dir' ) ) {
	/**
	 * Return plugin templates path
	 *
	 * @return string
	 * @since 1.0
	 */
	function forminator_pdf_addon_plugin_templates_dir() {
		return forminator_pdf_addon_plugin_dir() . 'core/templates/';
	}
}
