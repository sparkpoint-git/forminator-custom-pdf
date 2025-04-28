<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Rich_Text
 *
 * @since 1.0
 */
class Forminator_Rich_Text extends Forminator_PDF_Field {

	/**
	 * @var string
	 */
	public $name = '';

	/**
	 * @var string
	 */
	public $slug = 'rich-text';

	/**
	 * @var string
	 */
	public $type = 'rich-text';

	/**
	 * @var int
	 */
	public $position = 26;

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
	public $icon = 'sui-icon-blog';

	/**
	 * Forminator_Html constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		parent::__construct();

		$this->name = esc_html__( 'Rich Text', 'forminator-addons-pdf' );
	}

	/**
	 * Field defaults
	 *
	 * @return array
	 * @since 1.0
	 */
	public function defaults() {
		return array(
			'field_label' => esc_html__( 'Label', 'forminator-addons-pdf' ),
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
	 * Field front-end markup
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
		$hide_empty  = isset( $pdf_settings['hide_empty_fields'] ) ? $pdf_settings['hide_empty_fields'] : true;
		$hide_empty  = apply_filters( 'forminator_pdf_hide_empty_fields', $hide_empty, $pdf_field, $form_id, $pdf_settings, $entry );
		$hide_label  = isset( $pdf_field['hide-label'] ) ? filter_var( $pdf_field['hide-label'], FILTER_VALIDATE_BOOLEAN ) : false;
		$field_class = isset( $pdf_field['custom-class'] ) ? $pdf_field['custom-class'] : '';
		$layout      = $pdf_settings['pdf_layout'] ?? 'table';
		$field_value = $pdf_field['value'] ?? '';
		$colspan     = $pdf_field['cols'];
		$value       = forminator_pdf_replace_variables(
			$custom_form,
			$field_value,
			$entry,
			$form_fields,
			! $hide_label,
			$hide_empty
		);

		if ( 'table' === $layout ) {
			?>
			<td class="forminator-rich-text <?php echo esc_attr( $field_class ); ?>" colspan="<?php echo esc_attr( $colspan ); ?>">
				<?php if ( ( ! empty( $value ) && '' !== trim( strip_tags( $value, '<img>' ) ) && $hide_empty ) || ! $hide_empty ) : ?>
					<table width="100%">
						<?php if ( ! $hide_label && ! empty( $pdf_field['field_label'] ) ) : ?>
							<thead>
							<tr class="forminator-row-heading row-heading">
								<td>
									<?php echo esc_html( $pdf_field['field_label'] ); ?>
								</td>
							</tr>
							</thead>
						<?php endif; ?>
						<tbody>
						<tr class="forminator-row-content row-content">
							<td>
								<?php echo ! empty( $value ) ? wp_kses_post( $value ) : ''; ?>
							</td>
						</tr>
						</tbody>
					</table>
				<?php endif; ?>
			</td>
			<?php
		} else {
			?>
			<div class="forminator-rich-text <?php echo esc_attr( $field_class ); ?>">
				<?php if ( ( ! empty( $value ) && '' !== trim( strip_tags( $value, '<img>' ) ) && $hide_empty ) || ! $hide_empty ) : ?>
					<div class="forminator-field-wrapper">
						<?php if ( ! $hide_label && ! empty( $pdf_field['field_label'] ) ) : ?>
							<div class="forminator-field-label">
								<?php echo esc_html( $pdf_field['field_label'] ); ?>
							</div>
						<?php endif; ?>
						<div class="forminator-field-content">
							<?php echo ! empty( $value ) ? wp_kses_post( $value ) : ''; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
			<?php
		}
	}
}