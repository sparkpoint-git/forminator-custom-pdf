<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Payment
 *
 * @since 1.1
 */
class Forminator_Payment extends Forminator_PDF_Field {

	/**
	 * @var string
	 */
	public $name = '';

	/**
	 * @var string
	 */
	public $slug = 'payment';

	/**
	 * @var string
	 */
	public $type = 'payment';

	/**
	 * @var int
	 */
	public $position = 29;

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
	public $icon = 'sui-icon forminator-icon-currency';

	/**
	 * Forminator_Payment constructor.
	 *
	 * @since 1.1
	 */
	public function __construct() {
		parent::__construct();

		$this->name = esc_html__( 'Payment', 'forminator-addons-pdf' );
	}

	/**
	 * Field defaults
	 *
	 * @return array
	 * @since 1.1
	 */
	public function defaults() {
		return array(
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
			'total_amount_label'   => sprintf( esc_html__( 'Total Paid %s', 'forminator-addons-pdf' ), '{payment_currency}' ),
		);
	}

	/**
	 * Autofill Setting
	 *
	 * @param array $settings
	 *
	 * @return array
	 * @since 1.1
	 *
	 */
	public function autofill_settings( $settings = array() ) {
		// Unsupported Autofill.
		return $settings;
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
	 * @since 1.1
	 */
	public static function markup( $custom_form, $pdf_field, $form_id, $pdf_settings, $entry, $form_fields ) {
		$colspan     = $pdf_field['cols'];
		$field_class = $pdf_field['custom-class'] ?? '';
		$layout      = $pdf_settings['pdf_layout'] ?? 'table';

		if ( ! $custom_form->has_stripe_or_paypal() ) {
			return;
		}

		if ( 'table' === $layout ) { ?>
			<td colspan="<?php echo esc_attr( $colspan ); ?>" class="<?php echo esc_attr( $field_class ); ?>">
				<table class="forminator-payment-table content-container">
					<tbody>
					<tr>
						<?php if ( ! empty( $pdf_field['product_name'] ) ) { ?>
							<td width="50%" style="padding: 5px 10px;">
								<table>
									<thead>
									<tr class="forminator-row-heading row-heading">
										<td>
											<strong><?php echo ! empty( $pdf_field['product_label'] ) ? esc_html( $pdf_field['product_label'] ) : ''; ?></strong>
										</td>
									</tr>
									</thead>
									<tbody>
									<tr class="forminator-row-content">
										<td>
											<?php echo wp_kses_post( forminator_pdf_replace_variables( $custom_form, $pdf_field['product_value'], $entry ) ); ?>
										</td>
									</tr>
									</tbody>
								</table>
							</td>
						<?php } ?>
						<td style="padding: 5px 10px;">
							<?php echo self::pdf_payment_entries_main_content( $pdf_field, $custom_form, $entry, $layout ); ?>
						</td>
					</tr>
					</tbody>
				</table>
			</td>
		<?php } else { ?>
			<div class="<?php echo esc_attr( $field_class ); ?>">
				<div class="forminator-payment-table content-container">
					<div>
						<?php if ( ! empty( $pdf_field['product_name'] ) ) { ?>
							<div class="forminator-payment-field forminator-field-float">
								<div class="forminator-field-label">
									<strong><?php echo ! empty( $pdf_field['product_label'] ) ? esc_html( $pdf_field['product_label'] ) : ''; ?></strong>
								</div>
								<div class="forminator-field-content">
									<?php echo wp_kses_post( forminator_pdf_replace_variables( $custom_form, $pdf_field['product_value'], $entry ) ); ?>
								</div>
							</div>
						<?php } ?>
						<div class="forminator-payment-main-content">
							<?php echo self::pdf_payment_entries_main_content( $pdf_field, $custom_form, $entry, $layout ); ?>
						</div>
					</div>
				</div>
			</div>
		<?php }
		echo self::pdf_payment_entries_sub_content( $pdf_field, $custom_form, $entry, $layout );
	}

	/**
	 * Get payment data
	 *
	 * @param $key string will fetch key with main or sub
	 *
	 * @return array
	 */
	public static function get_pdf_payment_data( $key = 'main' ) {
		$payment_data = array(
			'main' => array(
				'payment_amount',
				'quantity',
				'payment_type',
				'payment_method',
				'payment_status',
				'transaction_id',
			),
			'sub'  => array(
				'subtotal',
				'tax',
				'total_amount',
			)
		);

		return $payment_data[ $key ];
	}

	/**
	 * PDF Main Entries Content
	 *
	 * @param $pdf_field
	 * @param $custom_form
	 * @param $entry
	 *
	 * @return string
	 */
	public static function pdf_payment_entries_main_content( $pdf_field, $custom_form, $entry, $layout = 'table' ) {
		$html          = '';
		$payment_data  = self::get_pdf_payment_data( 'main' );
		$elementsRow   = 2;
		$elementsArray = array_chunk( $payment_data, $elementsRow, true );

		if ( 'table' === $layout ) {
			$html .= '<table style="margin-bottom:-20px;">';

			foreach ( $elementsArray as $elements ) {
				$html .= '<thead><tr class="forminator-row-heading row-heading">';

				foreach ( $elements as $value ) {
					if ( ! empty( $pdf_field[ $value ] ) ) {
						$field_label = $pdf_field[ $value . '_label' ] ?? '';
						$html        .= '<td><strong>' . esc_html( forminator_pdf_replace_variables( $custom_form, $field_label, $entry ) ). '</strong></td>';
					}
				}

				$html .= '</tr></thead>';
				$html .= '<tbody><tr class="forminator-row-content">';

				foreach ( $elements as $value ) {
					if ( ! empty( $pdf_field[ $value ] ) ) {
						$html .= '<td width="50%" style="padding-bottom:25px;">' . esc_html( forminator_pdf_replace_variables( $custom_form, '{' . $value . '}', $entry ) ) . '</td>';
					}
				}

				$html .= '</tr></tbody>';
			}

			$html .= '</table>';
		} else {
			$html .= '<div class="forminator-payment-content">';

			foreach ( $elementsArray as $elements ) {
				$html .= '<div class="forminator-row-heading row-heading">';

				foreach ( $elements as $value ) {
					if ( ! empty( $pdf_field[ $value ] ) ) {
						$field_label = $pdf_field[ $value . '_label' ] ?? '';
						$html        .= '<div class="forminator-field-label forminator-field-float" style="width: 43%;"><strong>' . esc_html( forminator_pdf_replace_variables( $custom_form, $field_label, $entry ) ) . '</strong></div>';
					}
				}

				$html .= '</div>';
				$html .= '<div class="forminator-row-content">';

				foreach ( $elements as $value ) {
					if ( ! empty( $pdf_field[ $value ] ) ) {
						$html .= '<div class="forminator-field-content forminator-field-float" style="width: 43%;">' . esc_html( forminator_pdf_replace_variables( $custom_form, '{' . $value . '}', $entry ) ) . '</div>';
					}
				}

				$html .= '</div>';
			}

			$html .= '</div>';
		}

		return $html;
	}

	/**
	 * PDF Sub Entries Content
	 *
	 * @param $pdf_field
	 * @param $custom_form
	 * @param $entry
	 *
	 * @return string
	 */
	public static function pdf_payment_entries_sub_content( $pdf_field, $custom_form, $entry, $layout = 'table' ) {
		$html         = '';
		$payment_data = self::get_pdf_payment_data( 'sub' );
		$field_class  = $pdf_field['custom-class'] ?? '';

		if ( 'table' === $layout ) {
			$html         = '</tr>';
			$html         .= '<tr width="100">';
			$colspan      = $pdf_field['cols'];
			$html         .= '<td colspan="' . $colspan . '" class="' . esc_attr( $field_class ) . '">';
			$html         .= '<table class="forminator-row-content total-container">';
			foreach ( $payment_data as $value ) {
				if ( ! empty( $pdf_field[ $value ] ) ) {
					$field_label   = $pdf_field[ $value . '_label' ] ?? '';
					$content_label = esc_html( forminator_pdf_replace_variables( $custom_form, $field_label, $entry ) );
					$content_value = esc_html( forminator_pdf_replace_variables( $custom_form, '{' . $value . '}', $entry ) );
					$html          .= '<tr><td width="70%" class="table-title"><strong style="color:#555555;text-transform:uppercase;">' . $content_label . '</strong></td>';
					$html          .= '<td><strong>' . $content_value . '</strong></td></tr>';
				}
			}
			$html .= '</table></td>';
		} else {
			$html .= '<div class="' . esc_attr( $field_class ) . '">';
			$html .= '<div class="forminator-row-content total-container">';
			foreach ( $payment_data as $value ) {
				if ( ! empty( $pdf_field[ $value ] ) ) {
					$field_label   = $pdf_field[ $value . '_label' ] ?? '';
					$content_label = esc_html( forminator_pdf_replace_variables( $custom_form, $field_label, $entry ) );
					$content_value = esc_html( forminator_pdf_replace_variables( $custom_form, '{' . $value . '}', $entry ) );
					$html          .= '<div>';
					$html          .= '<div class="forminator-total-container-field forminator-field-float forminator-field-content" style="width: 60%;"><strong style="color:#555555;text-transform:uppercase;">' . $content_label . '</strong></div>';
					$html          .= '<div class="forminator-total-container-field forminator-field-content"><strong>' . $content_value . '</strong></div>';
					$html          .= '</div>';
				}
			}
			$html .= '</div></div>';
		}

		return $html;
	}
}