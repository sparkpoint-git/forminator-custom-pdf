<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Template_Receipt_PDF
 *
 * @since 1.1
 */
class Forminator_Template_Receipt_PDF {

	/*
	 * @var Object Forminator_Form_Entry_Model
	*/
	public $entry = null;

	/*
	 * @var array $post_data
	*/
	private static $post_data = array();

	/**
	 * Template defaults
	 *
	 * @return array
	 * @since 1.1
	 */
	public function defaults() {
		return array(
			'id'          => 'receipt_pdf',
			'name'        => esc_html__( 'Receipt PDF Form', 'forminator-addons-pdf' ),
			'description' => esc_html__( 'A simple receipt PDF form', 'forminator-addons-pdf' ),
			'icon'        => 'clipboard-notes',
			'priortiy'    => 2,
		);
	}

	/**
	 * Template fields
	 *
	 * @return array
	 * @since 1.1
	 */
	public function fields() {
		$payee_info   = self::$post_data['payee_info'] ?? '';
		$payer_info   = self::$post_data['payer_info'] ?? '';
		$payment_note = self::$post_data['payment_note'] ?? '';

		return array(
			array(
				'wrapper_id' => 'wrapper-1511347711919-1679',
				'fields'     => array(
					array(
						'element_id'  => 'rich-text-1',
						'type'        => 'rich-text',
						'cols'        => '6',
						'field_label' => esc_html__( 'Payee details', 'forminator-addons-pdf' ),
						'placeholder' => esc_html__( 'Enter label', 'forminator-addons-pdf' ),
						'value'       => esc_html( $payee_info ),
					),
					array(
						'element_id'  => 'rich-text-2',
						'type'        => 'rich-text',
						'cols'        => '6',
						'field_label' => esc_html__( 'Payer details', 'forminator-addons-pdf' ),
						'placeholder' => esc_html__( 'Enter label', 'forminator-addons-pdf' ),
						'value'       => esc_html( $payer_info ),
					),
				),
			),
			array(
				'wrapper_id' => 'wrapper-1511347765432-1980',
				'fields'     => array(
					array(
						'element_id'           => 'payment-1',
						'type'                 => 'payment',
						'cols'                 => '12',
						'product_name'         => true,
						'product_label'        => esc_html__( 'Product Name', 'forminator-addons-pdf' ),
						'product_value'        => '{product_name} <br/> {transaction_id}',
						'payment_amount'       => true,
						'quantity'             => true,
						'payment_type'         => true,
						'payment_method'       => true,
						'transaction_id'       => true,
						'payment_status'       => true,
						'tax'                  => false,
						'subtotal'             => true,
						'total_amount'         => true,
						'payment_amount_label' => esc_html__( 'Price', 'forminator-addons-pdf' ),
						'quantity_label'       => esc_html__( 'Quantity', 'forminator-addons-pdf' ),
						'payment_type_label'   => esc_html__( 'Type', 'forminator-addons-pdf' ),
						'payment_method_label' => esc_html__( 'Payment Method', 'forminator-addons-pdf' ),
						'transaction_id_label' => esc_html__( 'Transaction ID', 'forminator-addons-pdf' ),
						'payment_status_label' => esc_html__( 'Payment Status', 'forminator-addons-pdf' ),
						'subtotal_label'       => esc_html__( 'Subtotal', 'forminator-addons-pdf' ),
						'tax_label'            => esc_html__( 'Tax', 'forminator-addons-pdf' ),
						'total_amount_label'   => sprintf( esc_html__( 'Total %s', 'forminator-addons-pdf' ), '{payment_currency}' ),
					),
				),
			),
			array(
				'wrapper_id' => 'wrapper-1511347711876-2345',
				'fields'     => array(
					array(
						'element_id'  => 'rich-text-3',
						'type'        => 'rich-text',
						'cols'        => '12',
						'field_label' => esc_html__( 'Additional Information', 'forminator-addons-pdf' ),
						'placeholder' => esc_html__( 'Enter label', 'forminator-addons-pdf' ),
						'value'       => esc_html( $payment_note ),
					),
				),
			),
		);
	}

	/**
	 * Template settings
	 * All values should be specified as LENGTH in millimetres.
	 * @link https://mpdf.github.io/reference/mpdf-functions/construct.html
	 *
	 * @return array
	 * @since 1.1
	 */
	public function settings() {
		return array(
			'form-type'           => 'pdf-form',
			'form-template'       => 'receipt',
			'paper_size'          => 'A4',
			'hide_empty_fields'   => true,
			'pdf_margin'          => 'default',
			'pdf_margin_unit'     => 'px',
			'pdf_margin_top'      => 30,
			'pdf_margin_bottom'   => 30,
			'pdf_margin_left'     => 30,
			'pdf_margin_right'    => 30,
			'pdf_margin_footer'   => 45,
			'pdf_title_enabled'   => true,
			'pdf_title'           => esc_html__( 'Invoice', 'forminator' ),
			'pdf_logo_enabled'    => true,
			'pdf_logo_type'       => 'text',
			'pdf_logo_text'       => '{site_title}',
			'pdf_title_alignment' => 'right',
			'pdf_logo_image_type' => 'image_site_logo',
		);
	}

	/**
	 * PDF markup
	 *
	 * @param $pdf
	 * @param $custom_form
	 * @param $wrappers
	 * @param $form_id
	 * @param $field_values
	 *
	 * @return string
	 * @since 1.1
	 *
	 */
	public function markup( $pdf, $custom_form, $wrappers, $form_id, $field_values ) {
		if ( ob_get_length() > 0 ) {
			ob_clean();
		}

		$total_column = 12;
		$col_width    = round( Forminator_PDF_Form_Actions::$content_width / $total_column, 2 ) . 'px';
		$entry_id     = ! empty( $this->entry->entry_id ) ? $this->entry->entry_id : 0;
		$rtl_class    = isset( $pdf->settings['enable_rtl'] ) && filter_var( $pdf->settings['enable_rtl'], FILTER_VALIDATE_BOOLEAN ) ? 'pdf-rtl' : '';
		$custom_class = $pdf->settings['custom-class'] ?? '';

		ob_start();
		?>
		<html lang="en">
			<head>
				<meta charset="UTF-8"/>
				<?php
				if (
					! empty( $pdf->settings['pdf_title_enabled'] ) &&
					! empty( $pdf->settings['pdf_title'] )
				) {
					echo '<title>' . wp_kses_post( forminator_pdf_replace_variables( $custom_form, $pdf->settings['pdf_title'], $this->entry, $field_values ) ) . '</title>';
				}
				?>
			</head>

			<body>
				<div class="forminator-pdf-receipt-template <?php echo $rtl_class; ?>">
					<table class="forminator-pdf-header logo-container <?php echo esc_attr( $custom_class ); ?>">
						<tr>
							<?php echo $this->get_pdf_logo( $custom_form, $pdf->settings, $field_values ); ?>
							<?php echo $this->get_pdf_title( $custom_form, $pdf->settings, $field_values ); ?>
						</tr>
					</table>

					<table class="info-container">
						<thead>
							<tr>
								<?php
								for ( $i = 0; $i < $total_column; $i ++ ) {
									echo '<th width="' . esc_attr( $col_width ) . '"></th>' . PHP_EOL;
								}
								?>
							</tr>
						</thead>
						<tbody>
						<?php
						foreach ( $wrappers as $row ) {
							if ( empty( $row['fields'] ) ) {
								continue;
							}
							echo '<tr class="forminator-row-heading" width="100">';
							foreach ( $row['fields'] as $pdf_field ) {
								$this->show_pdf_content( $custom_form, $pdf_field, $form_id, $pdf->settings, $field_values );
							}
							echo '</tr>';
						}
						?>
						</tbody>
					</table>
				</div>
			</body>
		</html>
		<?php
		$html = ob_get_clean();

		return apply_filters( 'forminator_pdf_receipt_template_markup', $html, $pdf->settings, $form_id, $entry_id );
	}

	/**
	 * PDF Header logo.
	 *
	 * @param $custom_form
	 * @param $pdf_settings
	 * @param $form_fields
	 *
	 * @return string
	 */
	public function get_pdf_logo( $custom_form, $pdf_settings, $form_fields ) {
		$logo_html = '';
		if ( ! empty( $pdf_settings['pdf_logo_enabled'] ) ) {
			$logo_value = forminator_pdf_header_logo( $custom_form, $pdf_settings, $this->entry, $form_fields );
			if ( ! empty( $logo_value ) ) {
				$logo_html .= '<td class="pdf-logo" style="width:auto;"><div class="logo-wrapper">' . wp_kses_post( $logo_value ) . '</div></td>';
			}
		}

		/**
		 * Filter the PDF receipt template header logo html
		 *
		 * @param string $logo_html    Logo html
		 * @param array  $pdf_settings PDF settings
		 *
		 * @since 1.1
		 */
		return apply_filters(
			'forminator_pdf_receipt_template_header_logo',
			$logo_html,
			$pdf_settings
		);
	}

	/**
	 * Escape PDF title before showing it.
	 *
	 * @param $custom_form
	 * @param $pdf_settings
	 * @param $form_fields
	 *
	 * @return string
	 */
	public function get_pdf_title( $custom_form, $pdf_settings, $form_fields ) {
		$title = '';
		$align_class = 'pdf-title-wrapper ';

		if ( ! empty( $pdf_settings['pdf_title_alignment'] ) ) {
			$align_class .= 'align-title--' . $pdf_settings['pdf_title_alignment'];
		}

		if (
			! empty( $pdf_settings['pdf_title_enabled'] ) &&
			! empty( $pdf_settings['pdf_title'] )
		) {
			$pdf_title = forminator_pdf_replace_variables( $custom_form, $pdf_settings['pdf_title'], $this->entry, $form_fields );
			$title    .= '<td class="' . esc_attr( $align_class ) . '"><h1 class="pdf-title" style="text-transform: uppercase;"><strong>' . wp_kses_post( $pdf_title ) . '</strong></h1></td>';
		}

		return apply_filters(
			'forminator_pdf_receipt_template_title_value',
			$title,
			$pdf_settings
		);
	}

	/**
	 * Process PDF content based on PDF fields value from form entry.
	 *
	 * @param $custom_form
	 * @param $pdf_field
	 * @param $form_id
	 * @param $pdf_settings
	 * @param $field_values
	 *
	 * @return void
	 */
	public function show_pdf_content( $custom_form, $pdf_field, $form_id, $pdf_settings, $field_values ) {
		$field_object = Forminator_PDF_Addon_Core::get_field_object( $pdf_field['type'] );
		if ( is_null( $field_object ) ) {
			return;
		}

		$field_object::markup( $custom_form, $pdf_field, $form_id, $pdf_settings, $this->entry, $field_values );
	}

	/**
	 * PDF footer value
	 *
	 * @param $custom_form
	 * @param $pdf_settings
	 * @param $form_fields
	 *
	 * @return string
	 */
	public function get_pdf_footer_value( $custom_form, $pdf_settings, $form_fields ) {
		$footer = '';

		if ( ! empty( $pdf_settings['footer_value'] ) ) {
			$footer = forminator_pdf_replace_variables( $custom_form, $pdf_settings['footer_value'], $this->entry, $form_fields );
			$footer = '<div>' . wp_kses_post( $footer ) . '</div>';
		}

		return apply_filters(
			'forminator_pdf_receipt_template_footer_value',
			$footer,
			$pdf_settings
		);
	}

	/**
	 * Sanitize PDF pagination before showing it.
	 *
	 * @param $pdf_settings
	 *
	 * @return string/null
	 */
	public function get_pdf_pagination( $pdf_settings ) {
		$pagination = ! empty( $pdf_settings['show_page_number'] ) ?
			'<div name="forminator-pdf-pagination">{PAGENO}{nbpg}</div>' :
			'';

		return apply_filters(
			'forminator_pdf_receipt_template_pagination',
			$pagination,
			$pdf_settings
		);
	}

	/**
	 * PDF footer markup
	 *
	 * @param $custom_form
	 * @param $pdf_settings
	 * @param $form_fields
	 *
	 * @return string
	 */
	public function get_pdf_footer_markup( $custom_form, $pdf_settings, $form_fields ) {
		$custom_class = $pdf_settings['custom-class'] ?? '';

		$markup = sprintf(
			'<table width="100%%" class="forminator-pdf-footer %s">
				<tr class="forminator-pdf-footer-text">
					<td>%s</td>
				</tr>
				<tr class="forminator-pdf-pagination">
					<td>%s</td>
				</tr>
			</table>',
			esc_html( $custom_class ),
			$this->get_pdf_footer_value( $custom_form, $pdf_settings, $form_fields ),
			$this->get_pdf_pagination( $pdf_settings )
		);

		return apply_filters(
			'forminator_pdf_receipt_template_footer_markup',
			$markup,
			$pdf_settings
		);
	}

	/**
	 * Get PDF CSS.
	 *
	 * @param $pdf_settings
	 *
	 * @return string
	 */
	public function get_pdf_css( $pdf_settings ) {
		$css_file = forminator_pdf_addon_plugin_url() . 'assets/css/forminator.pdf.css';
		$pdf_css  = file_get_contents( $css_file );

		return apply_filters(
			'forminator_pdf_receipt_template_css',
			esc_html( $pdf_css ),
			$pdf_settings
		);
	}

	/**
	 * Preload PDF content based on parent fields or start with blank.
	 *
	 * @param object $model
	 * @param array $post_data
	 * @param object $template
	 *
	 * @return void
	 * @since 1.1
	 */
	public static function set_content( $model, $post_data, $template ) {
		self::$post_data = $post_data;
		foreach ( $template->fields() as $field ) {
			foreach ( $field['fields'] as $f ) {
				$form_field          = new Forminator_Form_Field_Model();
				$form_field->form_id = $field['wrapper_id'];
				$form_field->slug    = $f['element_id'];
				unset( $f['element_id'] );
				$form_field->import( $f );
				$model->add_field( $form_field );
			}
		}
	}
}
