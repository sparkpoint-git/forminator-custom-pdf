<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_All_Form_Data
 *
 * @since 1.0
 */
class Forminator_All_Form_Data extends Forminator_PDF_Field {

	/**
	 * @var string
	 */
	public $name = '';

	/**
	 * @var string
	 */
	public $slug = 'all-form-data';

	/**
	 * @var string
	 */
	public $type = 'all-form-data';

	/**
	 * @var int
	 */
	public $position = 27;

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
	public $icon = 'sui-icon-list-bullet';

	/**
	 * Forminator_Html constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		parent::__construct();

		$this->name = esc_html__( 'All Form Data', 'forminator-addons-pdf' );
	}

	/**
	 * Field defaults
	 *
	 * @return array
	 * @since 1.0
	 */
	public function defaults() {
		return array(
			'field_label' => esc_html__( 'All Form Data', 'forminator-addons-pdf' ),
			'input_label' => 'true',
		);
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
		$hide_empty    = isset( $pdf_settings['hide_empty_fields'] ) ? $pdf_settings['hide_empty_fields'] : true;
		$hide_empty    = apply_filters( 'forminator_pdf_hide_empty_fields', $hide_empty, $pdf_field, $form_id, $pdf_settings, $entry );
		$show_label    = filter_var( $pdf_field['input_label'], FILTER_VALIDATE_BOOLEAN );
		$field_class   = isset( $pdf_field['custom-class'] ) ? $pdf_field['custom-class'] : '';
		$colspan       = $pdf_field['cols'];
		$width         = Forminator_PDF_Form_Actions::$content_width;
		$width         = round( $width / ( 12 / $colspan ), 2 ) . 'px';
		$pdf_exclusion = ! empty( $pdf_field['exclusion-field'] ) ? $pdf_field['exclusion-field'] : '';
		$layout        = $pdf_settings['pdf_layout'] ?? 'table';
		preg_match_all( '/\{(.*?)\}/', $pdf_exclusion, $exclusions );

		if ( 'table' === $layout ) {
			echo '<td class="forminator-all-form-data ' . esc_attr( $field_class ) . '" width="' . esc_attr( $width ) . '" colspan="' . esc_attr( $colspan ) . '">';

			if ( ! empty( $entry->meta_data ) ) {

				foreach ( $form_fields as $element_id => $field ) {
					if (
						in_array( $element_id, $exclusions[1] ) ||
						( forminator_pdf_is_value_empty( $field ) && ( $hide_empty || ! $show_label ) )
					) {
						continue;
					}
					?>
					<table width="100%">
						<?php if ( $show_label ) : ?>
							<thead>
								<tr class="forminator-row-heading row-heading">
									<td>
										<?php
										if ( ! empty( $field['label'] ) ) {
											echo esc_html( $field['label'] );
										}
										?>
									</td>
								</tr>
							</thead>
						<?php endif; ?>
						<tbody>
							<tr class="forminator-row-content row-content">
								<td>
									<?php
									echo wp_kses_post( forminator_pdf_get_field_value( $custom_form, $element_id, $field, $entry, $show_label, $exclusions, $hide_empty ) );
									?>
								</td>
							</tr>
						</tbody>
					</table>
					<?php
				}
			}

			echo '</td>';
		} else {
			echo '<div class="forminator-all-form-data forminator-all-form-data-col-' . esc_attr( $colspan ) . ' ' . esc_attr( $field_class ) . '" style="width:' . esc_attr( $width ) . ';">';

			if ( ! empty( $entry->meta_data ) ) {
				foreach ( $form_fields as $element_id => $field ) {
					if (
						in_array( $element_id, $exclusions[1] ) ||
						( forminator_pdf_is_value_empty( $field ) && $hide_empty )
					) {
						continue;
					}
					?>
					<div class="forminator-field-wrapper">
						<?php if ( $show_label && ! empty( $field['label'] ) ) : ?>
							<div class="forminator-field-label">
								<?php echo esc_html( $field['label'] ); ?>
							</div>
						<?php endif; ?>
						<div class="forminator-field-content">
							<?php echo wp_kses_post( forminator_pdf_get_field_value( $custom_form, $element_id, $field, $entry, $show_label, $exclusions, $hide_empty ) ); ?>
						</div>
					</div>
					<?php
				}
			}

			echo '</div>';
		}
	}
}