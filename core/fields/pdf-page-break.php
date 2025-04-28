<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_PDF_Page_Break
 *
 * @since 1.0
 */
class Forminator_PDF_Page_Break extends Forminator_PDF_Field {

	/**
	 * @var string
	 */
	public $name = '';

	/**
	 * @var string
	 */
	public $slug = 'pdf-page-break';

	/**
	 * @var string
	 */
	public $type = 'pdf-page-break';

	/**
	 * @var int
	 */
	public $position = 28;

	/**
	 * @var array
	 */
	public $options = array();

	/**
	 * @var string
	 */
	public $category = 'standard';

	/**
	 * @var string
	 */
	public $hide_advanced = 'true';

	/**
	 * @var string
	 */
	public $icon = 'sui-icon forminator-icon-pagination';

	/**
	 * Forminator_Pagination constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {

		parent::__construct();

		$this->name = esc_html__( 'Page Break', 'forminator-addons-pdf' );

	}

	/**
	 * Field defaults
	 *
	 * @since 1.0
	 */
	public function defaults() {
	}

	/**
	 * Autofill Setting
	 *
	 * @param array $settings
	 *
	 * @return array
	 * @since 1.0.5
	 *
	 */
	public function autofill_settings( $settings = array() ) {
		// Unsupported Autofill.
		$autofill_settings = array();

		return $autofill_settings;
	}

	/**
	 * Field markup on PDF
	 *
	 * @param $custom_form
	 * @param $pdf_field
	 * @param $form_id
	 * @param $pdf_settings
	 * @param $entry
	 * @param $form_fields
	 *
	 * @return void
	 * @since 1.0
	 */
	public static function markup( $custom_form, $pdf_field, $form_id, $pdf_settings, $entry, $form_fields ) {
		$layout = $pdf_settings['pdf_layout'] ?? 'table';

		if ( 'table' === $layout ) {
			// Close out the table tags so we can begin a new page.
			echo '</tr></tbody></table>
				  <pagebreak/>
				  <table width="100%" autosize="0"><tbody><tr>';
		} else {
			// Close out the div tags so we can begin a new page.
			echo '</div>
				<pagebreak/>
				<div>';
		}
	}
}