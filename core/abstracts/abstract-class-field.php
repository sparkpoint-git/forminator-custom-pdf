<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_PDF_Field
 *
 * @since 1.0
 * Abstract class for fields
 *
 * @since 1.0.0
 */
abstract class Forminator_PDF_Field {

	/**
	 * @var string
	 */
	public $name = '';

	/**
	 * @var string
	 */
	public $slug = '';

	/**
	 * @var string
	 */
	public $category = '';

	/**
	 * @var int
	 */
	public $position = 99;

	/**
	 * @var string
	 */
	public $icon = 'sui-icon-element-radio';

	/**
	 * @var array
	 */
	public $settings = array();

	/**
	 * @var array
	 */
	public $defaults = array();

	public function __construct() {

		add_action( 'admin_init', array( &$this, 'admin_init_field' ) );
	}

	/**
	 * admin init field
	 *
	 * @since 1.0.0
	 */
	public function admin_init_field() {
		$this->settings = apply_filters( "forminator_field_{$this->slug}_general_settings", array() );
		$this->defaults = apply_filters( "forminator_field_{$this->slug}_defaults", $this->defaults() );
		$this->position = apply_filters( "forminator_field_{$this->slug}_position", $this->position );
	}

	/**
	 * Return field name
	 *
	 * @return string
	 * @since 1.0
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Return field slug
	 *
	 * @return string
	 * @since 1.0
	 */
	public function get_slug() {
		return $this->slug;
	}

	/**
	 * @return string
	 */
	public function get_category() {
		return $this->category;
	}

	/**
	 * Return field settings
	 *
	 * @return array
	 * @since 1.0
	 */
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * Return field property
	 *
	 * @param string $property
	 * @param array $field
	 * @param string $fallback
	 * @param string $data_type data type to return.
	 *
	 * @return mixed
	 * @since 1.6 add $data_type, to cast it
	 *
	 * @since 1.0
	 */

	public static function get_property( $property, $field, $fallback = '', $data_type = null ) {

		$property_value = $fallback;

		if ( isset( $field[ $property ] ) ) {
			$property_value = $field[ $property ];
		}

		return $property_value;
	}

	/**
	 * PDF Markup
	 *
	 * @param $custom_form
	 * @param $pdf_field
	 * @param $form_id
	 * @param $pdf_settings
	 * @param $entry_id
	 * @param $form_fields
	 *
	 * @return string
	 * @since 1.0
	 *
	 */
	public static function markup(
		/** @noinspection PhpUnusedParameterInspection */
		$custom_form,
		$pdf_field,
		$form_id,
		$pdf_settings,
		$entry_id,
		$form_fields
	) {
		return '';
	}

	/**
	 * @return array
	 * @since 1.0
	 */
	public function defaults() {
		return array();
	}

}