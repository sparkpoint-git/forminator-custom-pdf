<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Help core functions
 *
 * @package Forminator
 */

/**
 * Get parent field
 *
 * @param $parent_model
 *
 * @return array
 */
function forminator_parent_fields( $parent_model ) {
	if ( is_object( $parent_model ) ) {
		$fields = $parent_model->get_fields_grouped();
		if ( is_array( $fields ) && ! empty( $fields ) ) {
			return $fields;
		}
	}

	return array();
}

/**
 * Get parent module
 *
 * @param $id
 *
 * @return bool|object|void
 */
function forminator_parent_form( $id ) {
	$form_model = Forminator_Base_Form_Model::get_model( $id );
	if ( is_object( $form_model ) ) {
		if ( ! empty( $form_model->settings['parent_form_id'] ) ) {
			return Forminator_Base_Form_Model::get_model( $form_model->settings['parent_form_id'] );
		}
	}
}

/**
 * Get PDF page sizes
 * Keys are case-sensitive.
 *
 * @see https://mpdf.github.io/reference/mpdf-functions/construct.html
 *
 * @return array|WP_Error
 */
function forminator_pdf_page_sizes() {
	return array(
		'A4'          => esc_html__( 'A4', 'forminator-addons-pdf' ),
		'A4-L'        => esc_html__( 'A4 - Landscape', 'forminator-addons-pdf' ),
		'Letter'      => esc_html__( 'Letter', 'forminator-addons-pdf' ),
		'Letter-L'    => esc_html__( 'Letter - Landscape', 'forminator-addons-pdf' ),
		'Legal'       => esc_html__( 'Legal', 'forminator-addons-pdf' ),
		'Legal-L'     => esc_html__( 'Legal - Landscape', 'forminator-addons-pdf' ),
		'Executive'   => esc_html__( 'Executive', 'forminator-addons-pdf' ),
		'Executive-L' => esc_html__( 'Executive - Landscape', 'forminator-addons-pdf' ),
		'Folio'       => esc_html__( 'Folio', 'forminator-addons-pdf' ),
		'Folio-L'     => esc_html__( 'Folio - Landscape', 'forminator-addons-pdf' ),
		'Tabloid'     => esc_html__( 'Tabloid', 'forminator-addons-pdf' ),
		'Ledger'      => esc_html__( 'Ledger', 'forminator-addons-pdf' ),
	);
}

/**
 * Combine default and custom css then sanitize.
 *
 * @param string $default
 * @param array $settings
 *
 * @return string
 */
function forminator_prepare_pdf_css( $default, $settings ) {
	if (
		isset( $settings['use-custom-css'] ) &&
		filter_var( $settings['use-custom-css'], FILTER_VALIDATE_BOOLEAN ) &&
		! empty( $settings['custom_css'] )
	) {
		$default .= $settings['custom_css'];
	}

	return sanitize_textarea_field( $default );
}

/**
 * Replace variables in PDF fields content.
 *
 * @param $custom_form
 * @param $value
 * @param $entry
 * @param $form_fields
 * @param $show_label
 * @param $hide_empty
 *
 * @return array|string|string[]
 */
function forminator_pdf_replace_variables( $custom_form, $value, $entry, $form_fields = array(), $show_label = true, $hide_empty = true ) {
	if ( 'preview' === $entry->entry_id ) {
		return $value;
	}

	// Replace {site_name} with {site_title}
	$value = str_replace("{site_name}", "{site_title}", $value );

	// Replace field macros.
	preg_match_all( '/\{(.*?)\}/', $value, $field_macros );
	if ( ! empty( $field_macros[1] ) ) {
		foreach ( $field_macros[1] as $element_id ) {

			if ( ! isset( $form_fields[ $element_id ] ) ) {
				continue;
			}

			$replacement = forminator_pdf_get_field_value( $custom_form, $element_id, $form_fields[ $element_id ], $entry, $show_label, null, $hide_empty );
			if ( $replacement ) {
				$value = str_replace(
					'{' . $element_id . '}',
					$replacement,
					$value
				);
			}
		}

		$value = forminator_replace_form_data( $value, $custom_form, $entry, false, false, false, true );
		$value = forminator_replace_variables( $value, $entry->form_id, $entry );
		$value = forminator_pdf_replace_form_payment_data( $value, $custom_form, $entry );

		return $value;
	} else {

		return $value;
	}
}

/**
 * Check if field value is actually empty.
 *
 * @param array $field
 *
 * @return bool
 * @since 1.0
 *
 */
function forminator_pdf_is_value_empty( $field ) {
	$value = isset( $field['value'] ) ? $field['value'] : '';
	$type  = isset( $field['type'] ) ? $field['type'] : '';

	// Process fields.
	if ( is_array( $value ) ) {
		foreach ( $value as $key => $val ) {
			// Check key of the field first
			switch ( $type ) {
				case 'address':
				case 'time':
					if ( ! empty( $val ) ) {
						return false;
					} else {
						continue 2;
					}
				case 'name':
					if (
						! empty( $val ) &&
						'prefix' !== $key
					) {
						return false;
					}

					break;
				case 'date':
					if (
						! empty( $val ) &&
						( 'year' !== $key && 'format' !== $key )
					) {
						return false;
					}

					break;
				case 'currency':
				case 'calculation':
					if (
						! empty( $val ) &&
						'formatting_result' !== $key
					) {
						return false;
					}

					break;
				case 'group':

					break;
				default:
					return false;
			}
		}

		return true;
	} elseif ( '' === wp_strip_all_tags( $value, true ) ) {
		return true;
	}

	return false;
}

/**
 * Get field value depending on field type or value type
 *
 * @param $custom_form
 * @param $element_id
 * @param $field
 * @param $entry
 * @param $show_label
 * @param $exclusions
 * @param $hide_empty
 *
 * @return mixed|string
 * @since 1.0
 *
 */
function forminator_pdf_get_field_value( $custom_form, $element_id, $field, $entry, $show_label, $exclusions = null, $hide_empty = true ) {
	$html = '';

	// Grouping
	if ( 'group' === $field['type'] && 'preview' !== $entry->entry_id ) {
		$group_fields = $custom_form->get_grouped_fields( $element_id );

		// Remove the excluded fields.
		if ( ! is_null( $exclusions ) ) {
			foreach ( $group_fields as $index => $grp_field ) {
				if ( in_array( $grp_field->slug, $exclusions[1] ) ) {
					unset( $group_fields[ $index ] );
				}
			}
		}

		$html = forminator_prepare_formatted_form_entry( $custom_form, $entry, true, $group_fields, '', $show_label, 'ul', true );

		if (
			( $hide_empty && '' !== wp_strip_all_tags( $html ) ) ||
			! $hide_empty
		) {
			$html .= '<hr>';
		}

		$original_keys = wp_list_pluck( $group_fields, 'slug' );
		$repeater_keys = forminator_get_cloned_field_keys( $entry, $original_keys );

		foreach ( $repeater_keys as $repeater_slug ) {
			$html .= forminator_prepare_formatted_form_entry( $custom_form, $entry, true, $group_fields, $repeater_slug, $show_label, 'ul', true );

			if (
				( $hide_empty && '' !== wp_strip_all_tags( $html ) ) ||
				! $hide_empty
			) {
				$html .= '<hr>';
			}
		}
	} elseif ( 'section' === $field['type'] ) {
		$form_field = $custom_form->get_field( $element_id );
		$html       .= $form_field['section_title'] ?? '';
	} elseif ( 'html' === $field['type'] ) {
		$form_field = $custom_form->get_field( $element_id );
		$variations = $form_field['variations'] ?? '';
		$html       .= forminator_replace_variables( $variations, $custom_form->id );
	} else {
		if ( ! isset( $field['value'] ) ) {
			return ' ';
		}

		if ( is_array( $field['value'] ) ) {

			switch ( $field['type'] ) {
				case 'name':
				case 'address':
					$html = forminator_pdf_show_array_value( $field['value'], ' ' );

					break;
				case 'date':
				case 'time':
				case 'postdata':
				case 'calculation':
					$html = Forminator_Form_Entry_Model::meta_value_to_string( $field['type'], $field['value'], true );

					break;
				case 'stripe':
				case 'paypal':
					$html .= '<ul>';

					foreach ( $field['value'] as $key => $val ) {
						$key  = str_replace( '_', ' ', $key );
						$key  = ucwords( $key );
						$html .= '<li>' . esc_html( $key ) . ': ' . esc_html( $val ) . '</li>';
					}

					$html .= '</ul>';

					break;
				case 'signature':
					$html = '<img src="' . esc_url( $field['value']['file']['file_url'] ) . '" alt="' . esc_attr( basename( $field['value']['file']['file_url'] ) ) . '" class="pdf-signature" />';
					break;
				default:
					$html = forminator_pdf_show_array_value( $field['value'] );

					break;
			}

		} else {
			$html = $field['value'];
		}
	}

	return $html;
}

/**
 * Map field values.
 *
 * @param array $entry_values The array value of a field.
 * @param array $form_fields
 *
 * @return mixed
 * @since 2.0
 *
 */
function forminator_pdf_map_field_values( $entry_values, $form_fields ) {
	$fields = array();

	foreach ( $form_fields as $field ) {

		// Set the type.
		$fields[ $field->slug ]['type'] = $field->type;

		// Set the labels.
		switch ( $field->type ) {
			case 'address':
				$address_label = '';
				$address_array = array(
					'street_address',
					'address_line',
					'address_city',
					'address_state',
					'address_zip',
					'address_country',
				);

				foreach ( $address_array as $v ) {
					if ( filter_var( $field->$v, FILTER_VALIDATE_BOOLEAN ) ) {
						$label = $v . '_label';
						$label = $field->$label;

						if ( ! empty( $label ) ) {
							$address_label .= $label;

							if ( 'address_country' !== $v ) {
								$address_label .= ' | ';
							}
						}
					}
				}

				$fields[ $field->slug ]['label'] = $address_label;

				break;
			case 'postdata':
				$fields[ $field->slug ]['label'] = esc_html__( 'Postdata', 'forminator-addons-pdf' );

				break;
			case 'paypal':
				$fields[ $field->slug ]['label'] = esc_html__( 'Paypal', 'forminator-addons-pdf' );

				break;
			case 'group':
				$fields[ $field->slug ]['label'] = $field->field_label;

				break;
		}

		// Add values to fields except for empty and repeaters.
		foreach ( $entry_values as $key => $value ) {
			if ( empty( $fields[ $field->slug ]['label'] ) ) {
				$fields[ $field->slug ]['label'] = $field->field_label;
			}
			if ( $key !== $field->slug ) {
				continue;
			}

			$fields[ $field->slug ]['value'] = $value['value'];

		}

	}

	return $fields;
}

/**
 * Loop through array values.
 *
 * @param array $value The array value of a field.
 * @param string $separator
 *
 * @return mixed
 * @since 2.0
 *
 */
function forminator_pdf_show_array_value( $value, $separator = '<br>' ) {
	$html = '';

	foreach ( $value as $k => $v ) {
		if ( is_array( $v ) ) {
			forminator_pdf_show_array_value( $v, $separator );
		} else {
			$html .= wp_kses_post( $v ) . $separator;
		}
	}

	return $html;
}

/**
 * Field label
 *
 * @param $field
 *
 * @return mixed
 */
function forminator_pdf_field( $field ) {

	$form_field = Forminator_Core::sanitize_array( $field );

	if ( empty( $form_field['field_label'] ) ) {
		$form_field['field_label'] = ucfirst( esc_html( $form_field['type'] ) );
	}

	return $form_field;
}

/**
 * Check if MPDF required extensions are enabled.
 *
 * @return bool
 */
function forminator_pdf_extensions_enabled() {
	return extension_loaded( 'mbstring' ) && extension_loaded( 'gd' );
}
