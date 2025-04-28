<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_PDF_Generation
 *
 * @since 1.0
 */
class Forminator_PDF_Generation {

	/**
	 * Plugin instance
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Return the plugin instance
	 *
	 * @return Forminator_PDF_Generation
	 * @since 1.0
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Forminator_PDF_Generation constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		$this->include_lib();
		add_action( 'admin_notices', array( $this, 'show_pdf_extensions_notice' ) );
		add_filter( 'forminator_custom_form_mail_attachment', array( $this, 'attach_pdfs_to_email' ), 10, 4 );
		// add_filter( 'site_status_tests', array( $this, 'check_required_extensions' ) );
	}

	/**
	 * Include library
	 *
	 * @return void
	 */
	public function include_lib() {
		require_once forminator_pdf_addon_plugin_dir() . '/vendor/autoload.php';
	}

	/**
	 * Get the MPDF Config
	 *
	 * All values should be specified as LENGTH in millimetres.
	 * @link https://mpdf.github.io/reference/mpdf-functions/construct.html
	 *
	 * @param array $settings PDF settings.
	 *
	 * @return array
	 */
	public static function pdf_configuration( $settings ) {
		$config = array(
			// 'mode'                => 'utf-8',
			'format'              => $settings['paper_size'],
			'pagenumPrefix'       => esc_html__( 'Page ', 'forminator-addons-pdf' ),
			'nbpgPrefix'          => esc_html__( ' of ', 'forminator-addons-pdf' ),
			// Margins in millimeters.
			'margin_top'          => self::convert_margin( 'pdf_margin_top', $settings ),
			'margin_bottom'       => self::convert_margin( 'pdf_margin_bottom', $settings ),
			'margin_left'         => self::convert_margin( 'pdf_margin_left', $settings ),
			'margin_right'        => self::convert_margin( 'pdf_margin_right', $settings ),
			'margin_footer'       => self::convert_margin( 'pdf_margin_bottom', $settings ), // 9,
			'setAutoBottomMargin' => 'stretch',
			'autoMarginPadding'   => 5,
			// 'margin_header' => 9,
			'fontDir'             => array_merge( ( new Mpdf\Config\ConfigVariables() )->getDefaults()['fontDir'], [
				forminator_pdf_addon_plugin_dir() . 'assets/fonts',
			] ),

			// Overriding as we removed some fonts from the package due to file size concerns.
			'fontdata'            => array(
				'dejavusanscondensed'  => array(
					'R'          => 'DejaVuSansCondensed.ttf',
					'B'          => 'DejaVuSansCondensed-Bold.ttf',
					'I'          => 'DejaVuSansCondensed-Oblique.ttf',
					'BI'         => 'DejaVuSansCondensed-BoldOblique.ttf',
					'useOTL'     => 0xFF,
					'useKashida' => 75,
				),
				'forminator-pdf-icons' => array(
					'R' => 'forminator-pdf-icons.ttf',
				),
			),

			'autoLangToFont'      => true,
			'autoScriptToLang'    => true,
			'backupSubsFont'      => array( 'dejavusanscondensed' ),
			'backupSIPFont'       => '',
			'BMPonly'             => array(
				'dejavusanscondensed',
			),
			'sans_fonts'          => array(
				'dejavusanscondensed',
				'sans',
				'sans-serif',
				'cursive',
				'freesans',
				'arial',
				'helvetica',
				'verdana',
				'geneva',
				'lucida',
				'arialnarrow',
				'arialblack',
				'franklin',
				'franklingothicbook',
				'tahoma',
				'garuda',
				'calibri',
				'trebuchet',
				'lucidagrande',
				'microsoftsansserif',
				'trebuchetms',
				'lucidasansunicode',
				'franklingothicmedium',
				'futura',
				'hobo',
				'segoeprint',
			),
			'serif_fonts'         => array(
				'serif',
				'dejavuserif',
				'freeserif',
				'liberationserif',
				'timesnewroman',
				'times',
				'centuryschoolbookl',
				'palatinolinotype',
				'centurygothic',
				'bookmanoldstyle',
				'bookantiqua',
				'cyberbit',
				'cambria',
				'norasi',
				'charis',
				'palatino',
				'constantia',
				'georgia',
				'albertus',
				'xbzar',
				'algerian',
				'garamond',
			),
			'mono_fonts'          => array(
				'mono',
				'monospace',
				'freemono',
				'liberationmono',
				'courier',
				'ocrb',
				'ocr-b',
				'lucidaconsole',
				'couriernew',
				'monotypecorsiva',
			),
		);

		// Add font configuration based on uploaded font on path: UPLOAD_DIR/forminator/ttfonts/.
		$config = Forminator_PDF_Font::get_instance()->add_uploaded_font_configuration( $config );

		return apply_filters( 'forminator_pdf_config', $config, $settings );
	}

	/**
	 * Convert margin's unit to mm.
	 * MPDF only uses millimeters in pdf margins as per doc.
	 *
	 * @param string $config PDF setting to convert.
	 * @param array $settings PDF settings.
	 *
	 * @return string
	 */
	public static function convert_margin( $config, $settings ) {
		if ( 'custom' === $settings['pdf_margin'] ) {
			$pdf_margin_unit = $settings['pdf_margin_unit'];
			$pdf_margin_val  = $settings[ $config ];

			// Convert the value to mm.
			switch ( $pdf_margin_unit ) {
				case 'in':
					$pdf_margin_val = floatval( $pdf_margin_val ) * 25.4;
					break;
				case 'px':
					// 96 dpi is the default for web and css.
					$pdf_margin_val = floatval( $pdf_margin_val ) * 25.4 / 96;
					break;
				case 'pt':
					$pdf_margin_val = floatval( $pdf_margin_val ) * 0.352778;
					break;
			}
		} else {
			// Pre convert default 30px to mm.
			$pdf_margin_val = 7.9375;
		}

		return $pdf_margin_val;
	}

	/**
	 * Submission button
	 *
	 * @param Int $form_id The parent form ID.
	 * @param String $form_name The parent form name.
	 * @param Int $entry_id The entry ID.
	 *
	 * @return string
	 */
	public static function download_button( $form_id, $form_name, $entry_id ) {
		// No need to check for class_exists, this plugin will be deactivated anyway if Forminator is inactive.
		$pdfs  = Forminator_API::get_forms( null, 1, 999, 'pdf_form', $form_id );
		$nonce = wp_create_nonce( 'forminator_download_pdf' );

		$disabled = '';
		if ( ! forminator_pdf_extensions_enabled() ) {
			$disabled = ' disabled';
		}
		ob_start();
		if ( ! empty( $pdfs ) ) {
			?>
			<div class="sui-dropdown sui-dropdown-center forminator-download-pdf-files">
				<button class="sui-button sui-button-ghost sui-dropdown-anchor" aria-label="Dropdown"<?php echo esc_attr( $disabled ); ?>>
					<span class="sui-icon-download" aria-hidden="true"></span>
					<?php esc_html_e( 'Download PDF(s)', 'forminator-addons-pdf' ); ?>
				</button>
				<ul
						data-admin-post-url="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
						data-nonce="<?php echo esc_attr( $nonce ); ?>"
						data-form-name="<?php echo esc_attr( $form_name ); ?>"
						data-entry="<?php echo esc_attr( $entry_id ); ?>"
				>
					<?php foreach ( $pdfs as $pdf ) { ?>
						<li>
							<a
									class="forminator-download-pdf"
									data-pdf-id="<?php echo intval( $pdf->id ); ?>"
									data-pdf-name="<?php echo esc_attr( $pdf->name ); ?>"
									target="_blank"
							>
								<span class="sui-icon-download" aria-hidden="true"></span>
								<?php echo esc_html( $pdf->name ) . '.pdf'; ?>
							</a>
						</li>
					<?php } ?>
					<?php if ( count( $pdfs ) > 1 ) { ?>
						<li class="forminator-download-all-pdfs-list"
							style="border-top:1px solid #ddd;margin-top:10px;margin-bottom:-15px;">
							<a
									class="forminator-download-all-pdfs"
									data-nonce="<?php echo esc_attr( $nonce ); ?>"
									target="_blank"
							>
								<span class="sui-icon-download" aria-hidden="true"></span>
								<?php esc_html_e( 'Download all files', 'forminator-addons-pdf' ); ?>
							</a>
						</li>
					<?php } ?>
				</ul>
			</div>
			<?php
		} else {
			?>
			<div
					class="sui-tooltip"
					data-tooltip="<?php esc_html_e( 'No PDF file available', 'forminator-addons-pdf' ); ?>"
			>
				<button class="sui-button sui-button-ghost" disabled>
					<span class="sui-icon-download" aria-hidden="true"></span>
					<?php esc_html_e( 'Download PDF(s)', 'forminator-addons-pdf' ); ?>
				</button>
			</div>
			<?php
		}
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Attach PDFs to email notification.
	 *
	 * @param Array $attachments Email attachments.
	 * @param Object $custom_form Custom form module object.
	 * @param Object $entry Entry object.
	 * @param Array $pdfs Array of PDF IDs.
	 *
	 * @return array
	 */
	public function attach_pdfs_to_email( $attachments, $custom_form, $entry, $pdfs ) {
		if ( empty( $pdfs ) || ! forminator_pdf_extensions_enabled() ) {
			return $attachments;
		}

		$pdf_path = wp_normalize_path( forminator_upload_root() . '/' );

		foreach ( $pdfs as $pdf_id ) {
			$pdf = Forminator_API::get_module( $pdf_id );

			// PDF will return WP Error if the PDF doesnt exist.
			if ( is_wp_error( $pdf ) ) {
				continue;
			}

			$filename = esc_html( $pdf->name ) . '.pdf';
			$pdf_file = $pdf_path . $filename;

			$pdf_attachment = new Forminator_PDF_Form_Actions();
			$pdf_attachment->process_pdf_download(
				$pdf,
				$entry->entry_id,
				$pdf_file,
				'F',
				$entry
			);

			if ( file_exists( $pdf_file ) ) {
				$attachments[] = $pdf_file;
			}
		}

		return $attachments;
	}

	/**
	 * Show required PHP extensions admin notice
	 *
	 * @since 1.0
	 */
	public function show_pdf_extensions_notice() {
		if ( forminator_pdf_extensions_enabled() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		?>
		<div id="forminator-pdf-required-extensions-notice" class="notice notice-warning">

			<p style="color: #333;">
				<?php printf(
					esc_html__( '%sForminator PDF Generator Add-on%s requires the following modules (%smbstring%s and %sgd%s). Please contact your hosting provider to enable the extensions.', 'forminator-addons-pdf' ),
					'<b>',
					'</b>',
					'<b>',
					'</b>',
					'<b>',
					'</b>'
				); ?>
				<?php /* <a href="<?php echo esc_attr( '#' ); ?>" target="_blank"><?php esc_html_e( 'Learn more.', 'forminator-addons-pdf' ); ?></a> */ ?>
			</p>

		</div>
		<?php
	}

	/**
	 * Add site health info
	 *
	 * @return void
	 */
	public function check_required_extensions( $tests ) {
		if ( empty( $tests['direct'] ) ) {
			$tests['direct'] = [];
		}

		$tests['direct']['forminator_mbstring_loaded'] = array(
			'label' => esc_html__( 'The mbstring extension is not installed.', 'forminator-addons-pdf' ),
			'test'  => function () {
				$result = array(
					'label'       => esc_html__( 'PHP extension mbstring is supported' ),
					'status'      => 'good',
					'badge'       => array(
						'label' => esc_html__( 'Performance' ),
						'color' => 'blue',
					),
					'description' => sprintf(
						'<p>%s</p>',
						esc_html__( 'The mbstring extension is required for Forminator\'s PDF Add-on.', 'forminator-addons-pdf' )
					),
					'actions'     => '',
					'test'        => 'mb_check_encoding',
				);

				if ( ! function_exists( 'mb_check_encoding' ) ) {
					$result['status']      = 'critical';
					$result['label']       = esc_html__( 'The mbstring extension is not installed.', 'forminator-addons-pdf' );
					$result['description'] .= sprintf(
						'<p>%s</p>',
						sprintf(
							'<span class="error"><span class="screen-reader-text">%s</span></span> %s',
							/* translators: Hidden accessibility text. */
							__( 'Error' ),
							esc_html__( 'The mbstring extension is required for Forminator\'s PDF Add-on.', 'forminator-addons-pdf' )
						)
					);

					$result['actions'] = sprintf(
						'<p><a href="%s" target="_blank" rel="noopener">%s <span class="screen-reader-text">%s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a></p>',
						/* translators: Localized Support reference. */
						esc_url( 'https://wpmudev.com/get-support/' ),
						esc_html__( 'Get help resolving this issue.', 'forminator-addons-pdf' ),
						/* translators: Hidden accessibility text. */
						esc_html__( '(opens in a new tab)', 'forminator-addons-pdf' )
					);

				}

				return $result;
			}
		);

		return $tests;
	}

}