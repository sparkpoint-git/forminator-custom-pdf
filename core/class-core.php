<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_PDF_Addon_Core
 *
 * @since 1.0
 */
class Forminator_PDF_Addon_Core {

	/**
	 * Plugin instance
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Store fields objects
	 *
	 * @var array
	 */
	public $fields = array();

	/**
	 * Store field objects
	 *
	 * @var array
	 */
	private static $field_objects = array();

	/**
	 * Return the plugin instance
	 *
	 * @return Forminator_PDF_Addon_Core
	 * @since 1.0
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Forminator_PDF_Addon_Core constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		// Include all necessary files.
		$this->includes();
		// First check if upgrade of data is needed.
		Forminator_PDF_Addon_Upgrade::init();

		// Get enabled fields.
		$fields       = new Forminator_PDF_Addon_Fields();
		$this->fields = $fields->get_fields();

		$this->set_field_objects();

		// Init Loader.
		new Forminator_PDF_Addon_Loader();

		Forminator_PDF_Form_Actions::get_instance();

		// PDF generation class
		Forminator_PDF_Generation::get_instance();
	}

	/**
	 * Includes
	 *
	 * @since 1.0
	 */
	private function includes() {

		/**
		 * Core helper files
		 */
		include_once forminator_pdf_addon_plugin_dir() . 'core/helpers/helper-core.php';

		/**
		 * Fields helper files
		 */
		include_once forminator_pdf_addon_plugin_dir() . 'core/helpers/helper-fields.php';

		/**
		 * Core classes files
		 */
		include_once forminator_pdf_addon_plugin_dir() . 'core/classes/class-upgrade.php';
		include_once forminator_pdf_addon_plugin_dir() . 'core/classes/class-loader.php';
		include_once forminator_pdf_addon_plugin_dir() . 'core/classes/class-form-fields.php';
		include_once forminator_pdf_addon_plugin_dir() . 'core/classes/class-pdf-form-actions.php';
		include_once forminator_pdf_addon_plugin_dir() . 'core/classes/class-pdf-font.php';
		include_once forminator_pdf_addon_plugin_dir() . 'core/classes/class-pdf-generation.php';
		include_once forminator_pdf_addon_plugin_dir() . 'core/abstracts/abstract-class-field.php';

		/**
		 * Templates files
		 */
		$this->includes_templates();
	}

	/**
	 * Includes Templates
	 *
	 * @return void
	 */
	public function includes_templates() {
		include_once forminator_pdf_addon_plugin_templates_dir() . 'template-basic-pdf.php';
		include_once forminator_pdf_addon_plugin_templates_dir() . 'template-receipt-pdf.php';
	}

	/**
	 * Set field objects
	 */
	private function set_field_objects() {
		if ( self::$field_objects ) {
			return;
		}
		foreach ( $this->fields as $field_object ) {
			self::$field_objects[ $field_object->slug ] = $field_object;
		}
	}

	/**
	 * Get field object by field type
	 *
	 * @param string $type Field type.
	 *
	 * @return object
	 */
	public static function get_field_object( $type ) {
		$object = isset( self::$field_objects[ $type ] ) ? self::$field_objects[ $type ] : null;

		return $object;
	}
}