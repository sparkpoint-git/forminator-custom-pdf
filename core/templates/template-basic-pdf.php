<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Template_Basic_PDF
 *
 * @since 1.0
 */
class Forminator_Template_Basic_PDF {

	/*
	 * @var Object Forminator_Form_Entry_Model
	*/
	public $entry = null;

	/**
	 * Template defaults
	 *
	 * @return array
	 * @since 1.0
	 */
	public function defaults() {
		return array(
			'id'          => 'basic_pdf',
			'name'        => esc_html__( 'Basic PDF Form', 'forminator-addons-pdf' ),
			'description' => esc_html__( 'A simple Basic PDF form', 'forminator-addons-pdf' ),
			'icon'        => 'clipboard-notes',
			'priortiy'    => 2,
		);
	}

	/**
	 * Template fields
	 *
	 * @return array
	 * @since 1.0
	 */
	public function fields() {
		return array();
	}

	/**
	 * Template settings
	 * All values should be specified as LENGTH in millimetres.
	 * @link https://mpdf.github.io/reference/mpdf-functions/construct.html
	 *
	 * @return array
	 * @since 1.0
	 */
	public function settings() {
		return array(
			'form-type'           => 'pdf-form',
			'form-template'       => 'basic',
			'paper_size'          => 'A4',
			'hide_empty_fields'   => true,
			'pdf_margin'          => 'default',
			'pdf_layout'          => 'div',
			'pdf_margin_unit'     => 'px',
			'pdf_margin_top'      => 30,
			'pdf_margin_bottom'   => 30,
			'pdf_margin_left'     => 30,
			'pdf_margin_right'    => 30,
			'pdf_margin_footer'   => 45,
			'pdf_title_enabled'   => true,
			'pdf_title'           => esc_html__( 'Form Data', 'forminator' ),
			'pdf_logo_enabled'    => false,
			'pdf_logo_type'       => 'text',
			'pdf_logo_text'       => '{site_title}',
			'pdf_title_alignment' => 'center',
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
	 * @since 1.0
	 *
	 */
	public function markup( $pdf, $custom_form, $wrappers, $form_id, $field_values ) {
		// Remove debugging output before ob_start to prevent broken pdfs.
		if ( ob_get_length() > 0 ) {
			ob_clean();
		}
		$total_column = 12;
		$width        = round( Forminator_PDF_Form_Actions::$content_width / $total_column, 2 );
		$col_width    = $width . 'px';
		$entry_id     = ! empty( $this->entry->entry_id ) ? $this->entry->entry_id : 0;
		$rtl_class    = isset( $pdf->settings['enable_rtl'] ) && filter_var( $pdf->settings['enable_rtl'], FILTER_VALIDATE_BOOLEAN ) ? 'pdf-rtl' : '';
		$layout       = ! empty( $pdf->settings['pdf_layout'] ) ? $pdf->settings['pdf_layout'] : 'table';
		$layout_class = 'forminator-pdf-layout-' . $layout;
		$classes      = array( 'forminator-pdf-basic-template', $rtl_class, $layout_class );

		ob_start();
		?>
		<html>
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
			<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
				<!-- PDF Header -->
				<table class="forminator-pdf-header logo-container">
					<tr>
						<?php echo $this->get_pdf_logo( $custom_form, $pdf->settings, $field_values ); ?>
						<?php echo $this->get_pdf_title( $custom_form, $pdf->settings, $field_values ); ?>
					</tr>
				</table>


				<?php if ( 'table' === $layout ) : ?>

					<!-- PDF Content -->
					<table>
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

							echo '<tr width="100">';
							foreach ( $row['fields'] as $pdf_field ) {
								$this->show_pdf_content( $custom_form, $pdf_field, $form_id, $pdf->settings, $field_values );
							}
							echo '</tr>';
						}
						?>
						</tbody>
					</table>
				<?php else : ?>

					<!-- PDF Content -->
					<div class="forminator-pdf-div-layout">
						<?php
						foreach ( $wrappers as $row ) {
							if ( empty( $row['fields'] ) ) {
								continue;
							}

							$field_count = count( $row['fields'] );
							$div_width   = ( $total_column / $field_count ) * $width . 'px';

							foreach ( $row['fields'] as $pdf_field ) {
								echo '<div class="forminator-pdf-row" style="width:' . esc_attr( $div_width ) . ';">';
								echo '<div class="forminator-pdf-col">';
								$this->show_pdf_content( $custom_form, $pdf_field, $form_id, $pdf->settings, $field_values, $div_width );
								echo '</div>';
								echo '</div>';
							}
						}
						?>
					</div>
				<?php endif; ?>
			</div>
		</body>
		</html>
		<?php
		$html = ob_get_clean();

		return apply_filters( 'forminator_pdf_basic_template_markup', $html, $pdf->settings, $form_id, $entry_id );
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
		 * Filter the PDF basic template header logo html
		 *
		 * @param string $logo_html    Logo image
		 * @param array  $pdf_settings PDF settings
		 *
		 * @since 1.1
		 */
		return apply_filters(
			'forminator_pdf_basic_template_header_logo',
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
	 * @return void
	 */
	public function get_pdf_title( $custom_form, $pdf_settings, $form_fields ) {
		$title       = '';
		$align_class = 'pdf-title-wrapper ';

		if ( ! empty( $pdf_settings['pdf_title_alignment'] ) ) {
			$align_class .= 'align-title--' . $pdf_settings['pdf_title_alignment'];
		}

		if (
			! empty( $pdf_settings['pdf_title_enabled'] ) &&
			! empty( $pdf_settings['pdf_title'] )
		) {
			$pdf_title = forminator_pdf_replace_variables( $custom_form, $pdf_settings['pdf_title'], $this->entry, $form_fields );
			$title .= '<td class="' . esc_attr( $align_class ) . '"><h1 class="pdf-title"><strong>' . wp_kses_post( $pdf_title ) . '</strong></h1></td>';
		}

		return apply_filters(
			'forminator_pdf_basic_template_title_value',
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
			'forminator_pdf_basic_template_footer_value',
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
			'forminator_pdf_basic_template_pagination',
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
		$markup = sprintf(
			'<table width="100%%" class="forminator-pdf-footer">
				<tr class="forminator-pdf-footer-text">
					<td>%s</td>
				</tr>
				<tr class="forminator-pdf-pagination">
					<td>%s</td>
				</tr>
			</table>',
			$this->get_pdf_footer_value( $custom_form, $pdf_settings, $form_fields ),
			$this->get_pdf_pagination( $pdf_settings )
		);

		return apply_filters(
			'forminator_pdf_basic_template_footer_markup',
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
			'forminator_pdf_basic_template_css',
			esc_html( $pdf_css ),
			$pdf_settings
		);
	}

	/**
	 * Preload PDF content based on parent fields or start with blank.
	 *
	 * @param $model
	 * @param $post_data
	 * @param $template
	 *
	 * @return void
	 * @since 1.0
	 *
	 */
	public static function set_content( $model, $post_data, $template ) {
		// Preload fields if set.
		$pdf_preload    = isset( $post_data['pdf_preload'] ) ? esc_html( $post_data['pdf_preload'] ) : 'preload';
		$parent_form_id = isset( $post_data['parent_form_id'] ) ? esc_html( $post_data['parent_form_id'] ) : '';
		if ( 'preload' === $pdf_preload ) {
			$field_counter = 0;
			$parent_rows   = Forminator_API::get_form_wrappers( $parent_form_id );
			$skip_fields   = array( 'captcha', 'page-break', 'password' );
			if ( ! empty( $parent_rows ) ) {

				foreach ( $parent_rows as $parent_row ) {

					if ( ! empty( $parent_row['parent_group'] ) ) {
						continue;
					}

					if ( ! empty( $parent_row['fields'] ) ) {
						$wrapper_id = $parent_row['wrapper_id'];


						foreach ( $parent_row['fields'] as $parent_field ) {
							if ( in_array( $parent_field['type'], $skip_fields, true ) ) {
								continue;
							}

							$field          = new Forminator_Form_Field_Model();
							$parent_field   = forminator_pdf_field( $parent_field );
							$field->form_id = $wrapper_id;

							$field_counter ++;
							$field->slug = 'rich-text-' . $field_counter;
							$field->__set( 'type', 'rich-text' );

							// esc_html/wp_kses_post will be used on output in other functions.
							if ( 'section' === $parent_field['type'] ) {

								// We will set label but hide it so it will be seen in builder only.
								$field->__set( 'field_label', $parent_field['section_title'] );
								$field->__set( 'hide-label', true );

								// This will pass thru wp_kses_post on output.
								$value = '<h2>' . esc_html( $parent_field['section_title'] ) . '</h2>';
								if ( ! empty( $parent_field['section_subtitle'] ) ) {
									$value .= '<small>' . esc_html( $parent_field['section_subtitle'] ) . '</small>';
								}
								$value .= '<hr>';

							} elseif ( 'html' === $parent_field['type'] ) {
								$field->__set( 'field_label', $parent_field['field_label'] );
								$value = ! empty( $parent_field['variations'] ) ? wp_kses_post( $parent_field['variations'] ) : '';

							} else {
								$field->__set( 'field_label', ( $parent_field['field_label'] ) );
								$value = '{' . $parent_field['element_id'] . '}';
							}

							$field->__set( 'value', $value );
							$field->__set( 'cols', $parent_field['cols'] );
							$model->add_field( $field );
						}
					}
				}
			}

		} else {
			// Setup default field.
			foreach ( $template->fields() as $row ) {
				foreach ( $row['fields'] as $f ) {
					$field          = new Forminator_Form_Field_Model();
					$field->form_id = $row['wrapper_id'];
					$field->slug    = $f['element_id'];
					unset( $f['element_id'] );
					$field->import( $f );
					$model->add_field( $field );
				}
			}
		}
	}
}