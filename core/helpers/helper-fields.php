<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Help fields functions
 *
 * @package Forminator
 */

/**
 * Replace Stripe data
 *
 * @param $content
 * @param $custom_form
 * @param $entry
 *
 * @return string
 */
function forminator_pdf_replace_form_payment_data( $content, $custom_form, $entry ) {
	if ( ! function_exists( 'forminator_payment_data' ) ) {
		return $content;
	}
	$payment_meta = forminator_payment_data( $content, $custom_form, $entry );
	if ( ! empty( $payment_meta ) ) {
		$product_name = ! empty( $payment_meta['product_name'] ) ? $payment_meta['product_name'] : '';
		$replaces     = array(
			'{product_name}'   => $product_name,
			'{quantity}'       => ! empty( $payment_meta['quantity'] ) ? $payment_meta['quantity'] : 0,
			'{payment_type}'   => ! empty( $payment_meta['payment_type'] ) ? $payment_meta['payment_type'] : 'One time',
			'{payment_method}' => ! empty( $payment_meta['payment_method'] ) ? ucfirst( $payment_meta['payment_method'] ) : '',
			'{subtotal}'       => ! empty( $payment_meta['amount'] ) ? $payment_meta['amount'] : 0,
			'{tax}'            => ! empty( $payment_meta['tax'] ) ? $payment_meta['tax'] : 0.00,
			'{total_amount}'   => ! empty( $payment_meta['amount'] ) ? $payment_meta['amount'] : 0,
		);

		$content = str_replace( array_keys( $replaces ), array_values( $replaces ), $content );
	}

	return apply_filters( 'forminator_pdf_replace_form_payment_data', $content, $custom_form, $entry );
}

/**
 * PDF Header Logo.
 *
 * @param $custom_form
 * @param $pdf_settings
 * @param $entry
 * @param $form_fields
 *
 * @return string
 */
function forminator_pdf_header_logo( $custom_form, $pdf_settings, $entry, $form_fields ) {
	$logo_type = $pdf_settings['pdf_logo_type'] ?? 'text';
	if ( 'text' === $logo_type ) {
		$logo_text = forminator_pdf_replace_variables( $custom_form, $pdf_settings['pdf_logo_text'], $entry, false, $form_fields );

		return esc_html( $logo_text );
	} else {
		$image_logo = '';
		$image_type = $pdf_settings['pdf_logo_image_type'] ?? 'image_site_logo';
		switch ( $image_type ) {
			case 'image_site_logo':
				$custom_logo_id = get_theme_mod( 'custom_logo' );
				$image_array    = wp_get_attachment_image_src( $custom_logo_id, 'full' );
				if ( ! empty( $image_array[0] ) ) {
					$image_src = $image_array[0];
				}
				break;
			case 'image_upload_logo':
			case 'image_logo_url':
				if ( ! empty( $pdf_settings[ $image_type . '_value' ] ) ) {
					$image_src = $pdf_settings[ $image_type . '_value' ];
				}
				break;
		}
		if ( ! empty( $image_src ) ) {
			$image_name = basename( $image_src );
			$image_logo = '<a href="' . esc_url( $image_src ) . ' target="_blank">';
			$image_logo .= '<img src="' . esc_url( $image_src ) . '" alt="' . $image_name . '" height="60" />';
			$image_logo .= '</a>';
		}

		return $image_logo;
	}
}

/**
 * Render Rating field for PDF
 *
 * @param $rating_value
 * @param $rating_items
 *
 * @return string
 */
function forminator_render_pdf_rating_field( $rating_value, $rating_items ) {
	$rating_icons = array(
		'star'  => "&#xe903;",
		'heart' => "&#xe904;",
		'like'  => "&#xe905;",
		'smile' => "&#xe906;",
	);

	$rating_value = $rating_value ?? 0;
	$max_rating   = $rating_items['max_rating'] ?? 0;
	$icon         = $rating_items['icon'] ?? 'star';
	$suffix       = $rating_items['suffix'] ?? '';

	$output = '<div class="forminator-rating-field forminator-rating-size--' . esc_attr( $rating_items['size'] ) . '">';

	for ( $rating = 1; $rating <= $max_rating; $rating ++ ) {
		$class_name = 'forminator-rating-icon' . ( $rating <= $rating_value ? ' forminator-rating-active' : '' );
		$output     .= '<i class="' . esc_attr( $class_name ) . '" aria-hidden="true">' . esc_html( $rating_icons[ $icon ] ) . '</i>&nbsp;';
	}

	if ( $suffix ) {
		$output .= '<span class="forminator-rating-suffix">' . esc_html( '(' . $rating_value . '/' . $max_rating . ')' ) . '</span>';
	}

	$output .= '</div>';

	return $output;
}