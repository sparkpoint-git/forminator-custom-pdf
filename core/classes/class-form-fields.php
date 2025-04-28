<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Fields
 *
 * @since 1.0
 */
class Forminator_PDF_Addon_Fields {
	/**
	 * Store fields objects
	 *
	 * @var array
	 */
	public $fields = array();

	/**
	 * Forminator_Fields constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		$loader = new Forminator_PDF_Addon_Loader();

		$fields = $loader->load_files(
			'core/fields'
		);

		/**
		 * Filters the pdf fields
		 */
		$this->fields = apply_filters( 'forminator_pdf_fields', $fields );

		add_filter( 'forminator_fields', array( $this, 'add_pdf_fields' ) );
	}

	/**
	 * Retrieve fields objects
	 *
	 * @return array
	 * @since 1.0
	 */
	public function get_fields() {
		return $this->fields;
	}

	/**
	 * Add PDF field
	 *
	 * @param Forminator_Field $fields
	 *
	 * @return mixed
	 */
	public function add_pdf_fields( $fields ) {
		if ( ! $this->is_pdf_form() ) {
			return $fields;
		}
		$loader = new Forminator_PDF_Addon_Loader();

		return $loader->load_files(
			'core/fields'
		);
	}

	/**
	 * Check pdf form
	 *
	 * @return bool
	 */
	public function is_pdf_form() {
		$form_id    = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );
		$form_model = Forminator_Base_Form_Model::get_model( $form_id );
		if ( is_object( $form_model ) ) {
			if ( isset( $form_model->status ) && 'pdf_form' === $form_model->status ) {
				return true;
			}
		}

		return false;
	}
}