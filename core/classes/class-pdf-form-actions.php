<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Utils\UtfString;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_PDF_Form_Actions
 *
 * @since 1.0
 */
class Forminator_PDF_Form_Actions {

	/**
	 * Content width excluding the margins.
	 *
	 * @var null
	 */
	public static $content_width = 0;

	/**
	 * Plugin instance
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Return the plugin instance
	 *
	 * @return Forminator_PDF_Form_Actions
	 * @since 1.0
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Forminator_PDF_Form_Actions constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		add_action( 'admin_post_forminator_pdf_download', array( $this, 'download_pdf' ) );
		add_action( 'admin_post_forminator_pdf_download_all', array( $this, 'download_all_pdf' ) );
		add_action( 'forminator_form_action_delete', array( $this, 'delete_pdf' ), 10, 1 );
		add_filter( 'forminator_form_model_to_exportable_data', array( $this, 'export_form_pdf' ), 10, 3 );
		add_action( 'forminator_form_action_imported', array( $this, 'import_form_pdf' ), 10, 4 );
		add_action( 'forminator_form_action_clone', array( $this, 'clone_form_pdf' ), 10, 3 );
	}

	/**
	 * Export form PDF.
	 *
	 * @since 1.32
	 *
	 * @param array   $exportable_data Export data.
	 * @param string  $module_type Form type.
	 * @param integer $model_id Form Id.
	 */
	public function export_form_pdf( $exportable_data, $module_type, $model_id ) {
		$pdfs = Forminator_API::get_forms( null, 1, 999, 'pdf_form', $model_id );
		if ( ! empty( $pdfs ) ) {
			$available_pdfs = array();
			foreach ( $pdfs as $pdf ) {
				$post_meta = get_post_meta( $pdf->id, Forminator_Base_Form_Model::META_KEY, true );
				if ( is_array( $post_meta ) ) {
					if ( isset( $post_meta['settings']['parent_form_id'] ) ) {
						unset( $post_meta['settings']['parent_form_id'] );
					}
					if ( isset( $post_meta['settings']['form_id'] ) ) {
						unset( $post_meta['settings']['form_id'] );
					}
					// To export pdf in notification. We remove this while processing import.
					$post_meta['settings']['old_form_id'] = $pdf->id;
					$available_pdfs[]                     = $post_meta;
				}
			}
			$exportable_data['pdfs'] = $available_pdfs;
		}
		return $exportable_data;
	}

	/**
	 * Clone form PDF.
	 *
	 * @since 1.39
	 *
	 * @param int    $post_id  Module id.
	 * @param object $model Module model.
	 * @param int    $old_post_id Old Module id.
	 */
	public function clone_form_pdf( $post_id, $model, $old_post_id = 0 ) {
		if ( $old_post_id ) {
			$import_data = array();
			$import_data = $this->export_form_pdf( $import_data, 'form', $old_post_id );
			$post_status = $model->status;
			$this->import_form_pdf( $post_id, $post_status, $model, $import_data );
		}
	}

	/**
	 * Import form PDF.
	 *
	 * @since 1.32
	 *
	 * @param int    $post_id  Module id.
	 * @param string $post_status Module status.
	 * @param object $model Module model.
	 * @param array  $import_data Import data.
	 *
	 * @return void
	 * @throws Exception When import fails.
	 */
	public function import_form_pdf( $post_id, $post_status, $model, $import_data = array() ) {
		if ( empty( $import_data['pdfs'] ) ) {
			return;
		}
		foreach ( $import_data['pdfs'] as $data ) {
			if ( ! empty( $data['settings']['formName'] ) && ! empty( $data['settings']['old_form_id'] ) ) {
				$form_name = $data['settings']['formName'];

				$old_form_id = $data['settings']['old_form_id'];
				unset( $data['settings']['old_form_id'] );

				// Update form ids.
				$data['settings']['parent_form_id'] = strval( $post_id );
				$create_pdf['parent_form_id']       = strval( $post_id );
				$create_pdf['pdf_filename']         = $form_name;
				$create_pdf['pdf_template']         = $data['settings']['pdf_template'];

				$pdf_id = self::create_new_pdf_form( $create_pdf );
				if ( empty( $pdf_id ) || ! is_numeric( $pdf_id ) ) {
					throw new Exception( esc_html__( 'Failed to import PDF.', 'forminator-addons-pdf' ) );
				}

				// Build the fields for update.
				foreach ( $data['fields'] as $fields ) {
					$wrapper                 = array();
					$wrapper['wrapper_id']   = $fields['wrapper_id'];
					$wrapper['parent_group'] = $fields['parent_group'];
					$wrapper['fields'][]     = $fields;
					$data['wrappers'][]      = $wrapper;
				}
				unset( $data['fields'] );

				$id = $this->update_pdf_form( $pdf_id, $form_name, $data );
				if ( is_wp_error( $id ) ) {
					throw new Exception( esc_html__( 'Failed to import PDF settings.', 'forminator-addons-pdf' ) );
				}

				// Update pdf in email notification.
				$this->update_email_notification_pdf( $post_id, $old_form_id, $pdf_id );
			}
		}
	}

	/**
	 * Update pdf form.
	 *
	 *  @since 1.32
	 *
	 * @param integer $id  PDF Id.
	 * @param string  $title  Form name.
	 * @param array   $form_data  Form data.
	 *
	 * @return WP_Error|integer
	 */
	private function update_pdf_form( $id, $title, $form_data ) {
		$form_model = Forminator_Base_Form_Model::get_model( $id );

		if ( ! is_object( $form_model ) ) {
			return new WP_Error( 'forminator_model_not_exist', esc_html__( 'Form model doesn\'t exist', 'forminator' ) );
		}

		$status = $form_model->status;

		$template           = new stdClass();
		$template->fields   = $form_data['wrappers'];
		$template->settings = $form_data['settings'];

		return Forminator_Custom_Form_Admin::update( $id, $title, $status, $template );
	}

	/**
	 * Update pdf in email notification.
	 *
	 * @since 1.32
	 *
	 * @param integer $post_id Form Id.
	 * @param integer $old_pdf_id Old pdf Id.
	 * @param integer $new_pdf_id New pdf Id.
	 */
	private function update_email_notification_pdf( $post_id, $old_pdf_id, $new_pdf_id ) {
		$post_meta = get_post_meta( $post_id, Forminator_Base_Form_Model::META_KEY, true );
		$update    = false;
		if ( ! empty( $post_meta['notifications'] ) ) {
			foreach ( $post_meta['notifications'] as &$notifications ) {
				if ( ! empty( $notifications['email-pdfs'] ) ) {
					foreach ( $notifications['email-pdfs'] as &$email_pdf ) {
						if ( strval( $email_pdf ) === strval( $old_pdf_id ) ) {
							$email_pdf = strval( $new_pdf_id );
							$update    = true;
						}
					}
				}
			}
		}
		if ( true === $update ) {
			update_post_meta( $post_id, Forminator_Base_Form_Model::META_KEY, $post_meta );
		}
	}

	/**
	 * Create PDF module.
	 *
	 * @param array $post_data
	 *
	 * @return mixed
	 * @since 1.0
	 */
	public static function create_new_pdf_form( $post_data ) {
		if ( empty( $post_data ) ) {
			return new WP_Error( 'forminator_empty_response', esc_html__( 'Empty response', 'forminator-addons-pdf' ) );
		}

		$model = new Forminator_Form_Model();

		// Post data is already sanitized from get_post_data function in Forminator
		$parent_form_id = isset( $post_data['parent_form_id'] ) ? esc_html( $post_data['parent_form_id'] ) : '';
		$name           = $post_data['pdf_filename'] ?? 'noname';
		$pdf_template   = isset( $post_data['pdf_template'] ) ? esc_html( $post_data['pdf_template'] ) : 'basic';

		$model->name          = sanitize_title( $name );
		$model->notifications = array();

		$template = self::get_template_class_object( $pdf_template );
		$template::set_content( $model, $post_data, $template );

		$settings = $template->settings();

		// form name & version.
		$settings['formName']       = $name;
		$settings['version']        = FORMINATOR_VERSION;
		$settings['parent_form_id'] = $parent_form_id;
		$settings['pdf_template']   = $pdf_template;

		// settings.
		$model->settings = $settings;

		// status.
		$model->status = 'pdf_form';

		// Save data.
		return $model->save();
	}

	/**
	 * Download single PDF.
	 *
	 * @return void
	 * @since 1.0
	 *
	 */
	public function download_pdf() {
		if ( ! method_exists( 'Forminator_Core', 'sanitize_text_field' ) ) {
			return;
		}

		$data = Forminator_Core::sanitize_array( $_GET );

		if ( ! isset( $data['action'] ) || 'forminator_pdf_download' !== $data['action'] ) {
			return;
		}

		try {
			if ( ! wp_verify_nonce( $data['pdf_nonce'], 'forminator_download_pdf' ) || ! forminator_get_permission( 'forminator-entries' ) ) {
				throw new Exception( esc_html__( 'You are not allowed to download a PDF.', 'forminator-addons-pdf' ) );
			}

			if ( empty( $data['pdf_id'] ) ) {
				throw new Exception( esc_html__( 'Failed to get PDF. Please try again.', 'forminator-addons-pdf' ) );
			}

			$pdf_id = esc_html( $data['pdf_id'] );
			$pdf    = Forminator_API::get_module( $pdf_id );

			if ( is_wp_error( $pdf ) ) {
				throw new Exception( esc_html__( 'Something went wrong', 'forminator-addons-pdf' ) );
			}

			$this->process_pdf_download(
				$pdf,
				$data['entry_id'],
				esc_html( $pdf->name ) . '_' . wp_date( 'M-j-y' ) . '.pdf',
				'D'
			);

		} catch ( Exception $e ) {
			wp_die(
				esc_html( $e->getMessage() ),
				esc_html__( 'Download PDF', 'forminator-addons-pdf' ),
				array(
					'response'  => 200,
					'back_link' => true,
				)
			);
		}
	}

	/**
	 * Download multiple PDFs.
	 * Puts multiple PDFs in a zip file then downloads the zip.
	 *
	 * @return void
	 * @since 1.0
	 *
	 */
	public function download_all_pdf() {
		if ( ! method_exists( 'Forminator_Core', 'sanitize_array' ) ) {
			return;
		}

		$data = Forminator_Core::sanitize_array( $_GET );

		if ( ! isset( $data['action'] ) || 'forminator_pdf_download_all' !== $data['action'] ) {
			return;
		}

		try {
			if ( ! wp_verify_nonce( $data['pdf_nonce'], 'forminator_download_pdf' ) || ! forminator_get_permission( 'forminator-entries' ) ) {
				throw new Exception( esc_html__( 'You are not allowed to download all PDFs.', 'forminator-addons-pdf' ) );
			}

			$pdfs      = Forminator_API::get_forms( null, 1, 999, 'pdf_form', $data['form_id'] );
			$pdf_path  = wp_normalize_path( forminator_upload_root() . '/' );
			$pdf_files = array();

			if ( class_exists( 'ZipArchive' ) ) {
				$zip      = new ZipArchive();
				$zip_file = tempnam( $pdf_path, 'zip' );
				$zip->open( $zip_file, ZipArchive::CREATE );

				foreach ( $pdfs as $pdf ) {
					$filename = esc_html( $pdf->name ) . '.pdf';
					$pdf_file = $pdf_path . $filename;

					$this->process_pdf_download(
						$pdf,
						$data['entry_id'],
						$pdf_file,
						'F'
					);

					if ( file_exists( $pdf_file ) ) {
						$zip->addFile( $pdf_file, $filename );
						$pdf_files[] = $pdf_file;
					}
				}

				$zip->close();

				header( 'Content-type: application/zip' );
				header(
					esc_html(
						'Content-Disposition: attachment; filename=' .
						$data['form_name'] .
						'_#' .
						$data['entry_id'] .
						'-pdfs_' .
						wp_date( 'M-j-y' ) .
						'.zip'
					)
				);
				readfile( $zip_file );
				unlink( $zip_file );

				// Remove the PDF files after zip.
				array_walk(
					$pdf_files,
					function ( $item, $key ) {
						unlink( $item );
					}
				);

				exit;
			} else {
				throw new Exception( esc_html__( 'ZipArchive class does not exist.', 'forminator-addons-pdf' ) );
			}
		} catch ( Exception $e ) {
			wp_die(
				esc_html( $e->getMessage() ),
				esc_html( 'Download PDF' ),
				array(
					'response'  => 200,
					'back_link' => true,
				)
			);
		}
	}

	/**
	 * Processes any type of PDF output.
	 *
	 * @param object $pdf The PDF module object.
	 * @param mixed $entry_id The submission entry ID.
	 * @param string $filename The filename of the PDF.
	 * @param string $output Type of MPDF output. Possible values: I, F, D.
	 *
	 * @return string|void
	 * @throws MpdfException
	 * @since 1.0
	 *
	 */
	public function process_pdf_download( $pdf, $entry_id, $filename, $output, $entry = null ) {
		if ( ! forminator_pdf_extensions_enabled() ) {
			wp_die( esc_html__( 'Forminator PDF Generator Add-on requires the following modules (mbstring and gd). Please contact your hosting provider to enable the extensions.', 'forminator-addons-pdf' ) );
		}

		$wrappers = Forminator_API::get_form_wrappers( $pdf->id );
		if ( ! empty( $wrappers ) ) {
			$pdf_settings  = $pdf->settings;
			$pdf_template  = $pdf_settings['pdf_template'];
			$pdf_direction = isset( $pdf_settings['enable_rtl'] ) && filter_var( $pdf_settings['enable_rtl'], FILTER_VALIDATE_BOOLEAN ) ? 'rtl' : 'ltr';

			$template = self::get_template_class_object( $pdf_template );

			$form_id     = intval( $pdf_settings['parent_form_id'] );
			$form_fields = array();
			$custom_form = Forminator_API::get_form( $form_id );
			if ( is_object( $custom_form ) ) {
				$fields = $custom_form->get_fields();
				if ( is_array( $fields ) && ! empty( $fields ) ) {
					$form_fields = $fields;
				}
			}
			if ( 'preview' === $entry_id ) {
				$template->entry = $this->get_preview_entry( $form_fields, $form_id );
			} elseif ( empty( $entry_id ) && ! empty( $entry ) ) {
				$template->entry = $entry;
			} else {
				$template->entry = Forminator_API::get_entry( $form_id, $entry_id );
			}

			$template_meta_data = ! empty( $template->entry->meta_data ) ? $template->entry->meta_data : array();

			// Set parent form fields.
			$field_values = forminator_pdf_map_field_values( $template_meta_data, $form_fields );

			$mpdf = new Mpdf( Forminator_PDF_Generation::pdf_configuration( $pdf_settings ) );

			// Set the content width in px to be used in columns.
			self::$content_width = $mpdf->pgwidth * ( 96 / 25.4 );

			// Set text direction.
			$mpdf->SetDirectionality( $pdf_direction );

			// Set PDF meta title.
			if (
				! empty( $pdf_settings['pdf_title_enabled'] ) &&
				! empty( $pdf_settings['pdf_title'] )
			) {
				$pdf_title = forminator_pdf_replace_variables( $custom_form, $pdf_settings['pdf_title'], $template->entry, $field_values );
				$mpdf->SetTitle( UtfString::strcode2utf( esc_html( $pdf_title ) ) );
			} else {
				$mpdf->SetTitle( UtfString::strcode2utf( esc_html( $filename ) ) );
			}

			// Set PDF meta creator.
			$mpdf->SetCreator( UtfString::strcode2utf( esc_html( 'Forminator v' . FORMINATOR_VERSION ) ) );

			// Set PDF CSS.
			$mpdf->WriteHTML(
				forminator_prepare_pdf_css( $template->get_pdf_css( $pdf_settings ), $pdf_settings ),
				HTMLParserMode::HEADER_CSS
			);

			// Set PDF footer.
			if ( ! empty( $pdf_settings['footer_value'] ) || ! empty( $pdf_settings['show_page_number'] ) ) {
				$mpdf->SetHTMLFooter( $template->get_pdf_footer_markup( $custom_form, $pdf_settings, $field_values ) );
			}

			// Write PDF content.
			$mpdf->WriteHTML( $template->markup( $pdf, $custom_form, $wrappers, $form_id, $field_values ) );

			// Output the PDF.
			$mpdf->Output( sanitize_text_field( $filename ), sanitize_text_field( $output ) );
			$mpdf->cleanup();
		} else {
			wp_die( esc_html__( 'Failed to get PDF. Please try again.', 'forminator-addons-pdf' ) );
		}
	}

	/**
	 * Get entry object for preview.
	 *
	 * @param $form_fields
	 *
	 * @return Forminator_Form_Entry_Model|void
	 * @since 1.0
	 *
	 */
	public function get_preview_entry( $form_fields, $form_id ) {
		if ( empty( $form_fields ) ) {
			return;
		}
		$entry         = new Forminator_Form_Entry_Model();
		$parent_fields = $form_fields;
		$sample_entry  = array();

		foreach ( $parent_fields as $pfield ) {
			$sample_entry[ $pfield->slug ]['value'] = '{' . $pfield->slug . '}';
		}

		$entry->entry_id   = 'preview';
		$entry->entry_type = 'custom-forms';
		$entry->form_id    = $form_id;
		$entry->draft_id   = null;
		$entry->is_spam    = '0';
		$entry->meta_data  = $sample_entry;

		return $entry;
	}

	/**
	 * Delete PDF
	 *
	 * @param $id
	 *
	 * @return void
	 */
	public function delete_pdf( $id ) {
		if ( ! empty( $id ) ) {
			$pdfs = Forminator_API::get_forms( null, 1, 999, 'pdf_form', $id );
			if ( ! empty( $pdfs ) ) {
				foreach ( $pdfs as $pdf ) {
					wp_delete_post( $pdf->id );
				}
			}
		}
	}

	/**
	 * Get PDF Template Class Object
	 *
	 * @param $type
	 *
	 * @return Forminator_Template_Basic_PDF|Forminator_Template_Receipt_PDF Basic Template object or Receipt Template object
	 */
	public static function get_template_class_object( $type ) {
		switch ( $type ) {
			case 'receipt':
				return new Forminator_Template_Receipt_PDF();
			default:
				return new Forminator_Template_Basic_PDF();
		}
	}
}