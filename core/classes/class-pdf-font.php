<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_PDF_Font
 *
 * @since 1.5
 */
class Forminator_PDF_Font {

	/**
	 * Forminator_PDF_Font instance
	 *
	 * @var Forminator_PDF_Font|null
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return Forminator_PDF_Font
	 * @since 1.5
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Add uploaded font configuration
	 *
	 * @param array $config Configuration.
	 * @return array
	 */
	public function add_uploaded_font_configuration( $config ) {
		$font_directory = $this->get_uploaded_fonts_path();
		if ( ! is_null( $font_directory ) ) {
			$config['fontDir'][]     = $font_directory;
			$available_font_settings = $this->get_available_font_settings();
			if ( ! empty( $available_font_settings ) ) {
				$settings_to_add = array( 'fontdata', 'BMPonly', 'backupSubsFont', 'sans_fonts', 'serif_fonts', 'mono_fonts' );
				foreach ( $settings_to_add as $settings_key ) {
					if ( ! empty( $available_font_settings[ $settings_key ] ) ) {
						if ( isset( $config[ $settings_key ] ) && is_array( $config[ $settings_key ] ) ) {
							$config[ $settings_key ] = array_merge( $config[ $settings_key ], $available_font_settings[ $settings_key ] );
						} else {
							$config[ $settings_key ] = $available_font_settings[ $settings_key ];
						}
					}
				}
				if ( ! empty( $available_font_settings['backupSIPFont'] ) ) {
					$config['backupSIPFont'] = $available_font_settings['backupSIPFont'];
				}
			}
		}

		return $config;
	}

	/**
	 * Get uploaded fonts folder path
	 *
	 * @return string|null
	 */
	private function get_uploaded_fonts_path() {
		$upload_root = forminator_upload_root();
		$path        = $upload_root . 'ttfonts/';
		if ( is_dir( $path ) ) {
			return $path;
		}
		return null;
	}

	/**
	 * Get available fonts
	 *
	 * @return array[]
	 */
	private function get_available_font_settings() {
		$fonts = array();
		$path  = $this->get_uploaded_fonts_path();
		if ( is_null( $path ) ) {
			return $fonts;
		}
		$supported_fonts = $this->get_supported_fonts();
		foreach ( $supported_fonts as $font_name => $font ) {
			if ( ! empty( $font['R'] ) && file_exists( $path . $font['R'] ) ) {
				if ( in_array( $font_name, array( 'dejavusans', 'dejavuserifcondensed', 'dejavuserif', 'dejavusansmono' ), true ) ) {
					$fonts['BMPonly'][] = $font_name;
				}
				if ( in_array( $font_name, array( 'sun-exta', 'freesans' ), true ) ) {
					$fonts['backupSubsFont'][] = $font_name;
				}
				if ( 'sun-extb' === $font_name ) {
					$fonts['backupSIPFont'] = $font_name;
				}
				if ( in_array( $font_name, array( 'dejavusans', 'freesans', 'xbriyaz' ), true ) ) {
					$fonts['sans_fonts'][] = $font_name;
				}
				if ( in_array( $font_name, array( 'dejavuserifcondensed' ), true ) ) {
					$fonts['serif_fonts'][] = $font_name;
				}
				if ( in_array( $font_name, array( 'dejavusansmono' ), true ) ) {
					$fonts['mono_fonts'][] = $font_name;
				}
				foreach ( $font as $key => $value ) {
					if ( in_array( $key, array( 'R', 'B', 'I', 'BI' ), true ) ) {
						if ( file_exists( $path . $value ) ) {
							$fonts['fontdata'][ $font_name ][ $key ] = $value;
						}
					} else {
						$fonts['fontdata'][ $font_name ][ $key ] = $value;
					}
				}
			}
		}
		return $fonts;
	}

	/**
	 * Get Supported fonts
	 *
	 * @return array
	 */
	private function get_supported_fonts() {
		$fonts = array(
			'dejavusans'            => array(
				'R'          => 'DejaVuSans.ttf',
				'B'          => 'DejaVuSans-Bold.ttf',
				'I'          => 'DejaVuSans-Oblique.ttf',
				'BI'         => 'DejaVuSans-BoldOblique.ttf',
				'useOTL'     => 0xFF,
				'useKashida' => 75,
			),
			'dejavuserif'           => array(
				'R'  => 'DejaVuSerif.ttf',
				'B'  => 'DejaVuSerif-Bold.ttf',
				'I'  => 'DejaVuSerif-Italic.ttf',
				'BI' => 'DejaVuSerif-BoldItalic.ttf',
			),
			'dejavuserifcondensed'  => array(
				'R'  => 'DejaVuSerifCondensed.ttf',
				'B'  => 'DejaVuSerifCondensed-Bold.ttf',
				'I'  => 'DejaVuSerifCondensed-Italic.ttf',
				'BI' => 'DejaVuSerifCondensed-BoldItalic.ttf',
			),
			'dejavusansmono'        => array(
				'R'          => 'DejaVuSansMono.ttf',
				'B'          => 'DejaVuSansMono-Bold.ttf',
				'I'          => 'DejaVuSansMono-Oblique.ttf',
				'BI'         => 'DejaVuSansMono-BoldOblique.ttf',
				'useOTL'     => 0xFF,
				'useKashida' => 75,
			),
			'freesans'              => array(
				'R'      => 'FreeSans.ttf',
				'B'      => 'FreeSansBold.ttf',
				'I'      => 'FreeSansOblique.ttf',
				'BI'     => 'FreeSansBoldOblique.ttf',
				'useOTL' => 0xFF,
			),
			'freeserif'             => array(
				'R'          => 'FreeSerif.ttf',
				'B'          => 'FreeSerifBold.ttf',
				'I'          => 'FreeSerifItalic.ttf',
				'BI'         => 'FreeSerifBoldItalic.ttf',
				'useOTL'     => 0xFF,
				'useKashida' => 75,
			),
			'freemono'              => array(
				'R'  => 'FreeMono.ttf',
				'B'  => 'FreeMonoBold.ttf',
				'I'  => 'FreeMonoOblique.ttf',
				'BI' => 'FreeMonoBoldOblique.ttf',
			),
			/* OCR-B font for Barcodes */
			'ocrb'                  => array(
				'R' => 'ocrb10.ttf',
			),
			/* Miscellaneous language font(s) */
			'estrangeloedessa'      => array(/* Syriac */
				'R'      => 'SyrCOMEdessa.otf',
				'useOTL' => 0xFF,
			),
			'kaputaunicode'         => array(/* Sinhala  */
				'R'      => 'kaputaunicode.ttf',
				'useOTL' => 0xFF,
			),
			'abyssinicasil'         => array(/* Ethiopic */
				'R'      => 'Abyssinica_SIL.ttf',
				'useOTL' => 0xFF,
			),
			'aboriginalsans'        => array(/* Cherokee and Canadian */
				'R' => 'AboriginalSansREGULAR.ttf',
			),
			'jomolhari'             => array(/* Tibetan */
				'R'      => 'Jomolhari.ttf',
				'useOTL' => 0xFF,
			),
			'sundaneseunicode'      => array(/* Sundanese */
				'R'      => 'SundaneseUnicode-1.0.5.ttf',
				'useOTL' => 0xFF,
			),
			'taiheritagepro'        => array(/* Tai Viet */
				'R' => 'TaiHeritagePro.ttf',
			),
			'aegean'                => array(
				'R'      => 'Aegean.otf',
				'useOTL' => 0xFF,
			),
			'aegyptus'              => array(
				'R'      => 'Aegyptus.otf',
				'useOTL' => 0xFF,
			),
			'akkadian'              => array(/* Cuneiform */
				'R'      => 'Akkadian.otf',
				'useOTL' => 0xFF,
			),
			'quivira'               => array(
				'R'      => 'Quivira.otf',
				'useOTL' => 0xFF,
			),
			'eeyekunicode'          => array(/* Meetei Mayek */
				'R' => 'Eeyek.ttf',
			),
			'lannaalif'             => array(/* Tai Tham */
				'R'      => 'lannaalif-v1-03.ttf',
				'useOTL' => 0xFF,
			),
			'daibannasilbook'       => array(/* New Tai Lue */
				'R' => 'DBSILBR.ttf',
			),
			'garuda'                => array(/* Thai */
				'R'      => 'Garuda.ttf',
				'B'      => 'Garuda-Bold.ttf',
				'I'      => 'Garuda-Oblique.ttf',
				'BI'     => 'Garuda-BoldOblique.ttf',
				'useOTL' => 0xFF,
			),
			'khmeros'               => array(/* Khmer */
				'R'      => 'KhmerOS.ttf',
				'useOTL' => 0xFF,
			),
			'dhyana'                => array(/* Lao fonts */
				'R'      => 'Dhyana-Regular.ttf',
				'B'      => 'Dhyana-Bold.ttf',
				'useOTL' => 0xFF,
			),
			'tharlon'               => array(/* Myanmar / Burmese */
				'R'      => 'Tharlon-Regular.ttf',
				'useOTL' => 0xFF,
			),
			'padaukbook'            => array(/* Myanmar / Burmese */
				'R'      => 'Padauk-book.ttf',
				'useOTL' => 0xFF,
			),
			'zawgyi-one'            => array(/* Myanmar / Burmese */
				'R'      => 'ZawgyiOne.ttf',
				'useOTL' => 0xFF,
			),
			'ayar'                  => array(/* Myanmar / Burmese */
				'R'      => 'ayar.ttf',
				'useOTL' => 0xFF,
			),
			'taameydavidclm'        => array(/* Hebrew with full Niqud and Cantillation */
				'R'      => 'TaameyDavidCLM-Medium.ttf',
				'useOTL' => 0xFF,
			),
			/* SMP */
			'mph2bdamase'           => array(
				'R' => 'damase_v.2.ttf',
			),
			/* Indic */
			'lohitkannada'          => array(
				'R'      => 'Lohit-Kannada.ttf',
				'useOTL' => 0xFF,
			),
			'pothana2000'           => array(
				'R'      => 'Pothana2000.ttf',
				'useOTL' => 0xFF,
			),
			/* Arabic fonts */
			'xbriyaz'               => array(
				'R'          => 'XB Riyaz.ttf',
				'B'          => 'XB RiyazBd.ttf',
				'I'          => 'XB RiyazIt.ttf',
				'BI'         => 'XB RiyazBdIt.ttf',
				'useOTL'     => 0xFF,
				'useKashida' => 75,
			),
			'lateef'                => array(/* Sindhi, Pashto and Urdu */
				'R'          => 'LateefRegOT.ttf',
				'useOTL'     => 0xFF,
				'useKashida' => 75,
			),
			'kfgqpcuthmantahanaskh' => array(/* KFGQPC Uthman Taha Naskh - Koranic */
				'R'          => 'Uthman.otf',
				'useOTL'     => 0xFF,
				'useKashida' => 75,
			),
			/* CJK fonts */
			'sun-exta'              => array(
				'R'       => 'Sun-ExtA.ttf',
				'sip-ext' => 'sun-extb', /* SIP=Plane2 Unicode (extension B) */
			),
			'sun-extb'              => array(
				'R' => 'Sun-ExtB.ttf',
			),
			'unbatang'              => array(/* Korean */
				'R' => 'UnBatang_0613.ttf',
			),
		);

		return $fonts;
	}
}