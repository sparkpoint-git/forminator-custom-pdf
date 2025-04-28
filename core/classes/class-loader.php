<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_PDF_Addon_Loader
 *
 * @since 1.0.0
 */
class Forminator_PDF_Addon_Loader {

	/**
	 * @var array
	 */
	public $files = array();

	/**
	 * Forminator_PDF_Addon_Loader constructor.
	 */
	public function __construct() {
		// Register custom post_status.
		add_action( 'init', array( __CLASS__, 'add_custom_post_status' ) );
		add_filter( 'forminator_data', array( $this, 'forminator_pdf_admin_script' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Retrieve data
	 *
	 * @param       $dir
	 * @param array $requirements
	 *
	 * @return mixed
	 * @since 1.7 add $requirements
	 *
	 * @since 1.0
	 */
	public function load_files( $dir, $requirements = array() ) {
		$files = scandir( forminator_pdf_addon_plugin_dir() . $dir );
		foreach ( $files as $file ) {
			$path = forminator_pdf_addon_plugin_dir() . $dir . '/' . $file;

			if ( $this->is_php( $file ) && is_file( $path ) ) {

				// check requirement.
				if ( ! empty( $requirements ) ) {
					if ( in_array( $file, array_keys( $requirements ), true ) ) {
						if ( ! $this->is_requirement_fulfilled( $requirements[ $file ] ) ) {
							continue;
						}
					}
				}
				// Get class name.
				$class_name = str_replace( '.php', '', $file );
				// Include file.
				include_once $path;

				// Init class.
				$object = $this->init( $class_name );

				$this->files[] = $object;
			}
		}

		return $this->files;
	}

	/**
	 * Check if PHP file
	 *
	 * @param $file
	 *
	 * @return bool
	 * @since 1.0
	 */
	public function is_php( $file ) {
		$check = substr( $file, - 4 );
		if ( '.php' === $check ) {
			return true;
		}

		return false;
	}

	/**
	 * Normalize class name
	 *
	 * @param $name
	 *
	 * @return mixed|string
	 * @since 1.0
	 */
	public function normalize( $name ) {
		$name = str_replace( '-', '_', $name );
		$name = ucwords( $name );

		return $name;
	}

	/**
	 * Init class
	 *
	 * @param $name
	 *
	 * @return mixed
	 * @since 1.0
	 */
	private function init( $name ) {
		$class = 'Forminator_' . $this->normalize( $name );

		if ( class_exists( $class ) ) {
			$object = new $class();

			return $object;
		}
	}

	/**
	 * Check if requirement fulfilled by system
	 *
	 * @param array $requirement
	 *
	 * @return bool
	 * @since 1.7
	 *
	 */
	private function is_requirement_fulfilled( $requirement ) {
		// check php version.
		if ( isset( $requirement['php'] ) ) {
			$version = $requirement['php'];
			if ( version_compare( PHP_VERSION, $version, 'lt' ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Add custom post status for Leads forms
	 */
	public static function add_custom_post_status() {
		register_post_status(
			'pdf_form',
			array(
				'public'                    => false,
				'internal'                  => true,
				'post_type'                 => array( 'forminator_forms' ),
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => false,
				'exclude_from_search'       => true,
			)
		);
	}

	public function enqueue_scripts() {
		wp_enqueue_script(
			'forminator-pdf-admin',
			forminator_pdf_addon_plugin_url() . 'build/admin.min.js',
			array( 'jquery' ),
			FORMINATOR_PDF_ADDON,
			false
		);
	}

	/**
	 * PDF admin script
	 *
	 * @param $data
	 *
	 * @return void
	 */
	public function forminator_pdf_admin_script( $data ) {
		$id           = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );
		$parent_model = forminator_parent_form( $id );
		if ( is_object( $parent_model ) ) {
			$form_name = '';
			if ( isset( $parent_model->settings['formName'] ) && ! empty( $parent_model->settings['formName'] ) ) {
				$form_name = $parent_model->settings['formName'];
			}
			$data['parentFormFields'] = forminator_parent_fields( $parent_model );
			$data['parent_form_id']   = intval( $parent_model->id );
			$data['parent_form_name'] = esc_html( $form_name );
		}
		$data['pdf_page_sizes'] = forminator_pdf_page_sizes();
		$data['hasSiteLogo']    = has_custom_logo();

		return $data;
	}
}