<?php
/**
 *  ACF: CAMPOS VÍA CÓDIGO (sin UI)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'obras_get_bitacora_class_choices' ) ) {
    function obras_get_bitacora_class_choices() {
        
        return array(
            ''            => 'Sin clasificar',
            'observacion' => 'Observación',
            'novedad'     => 'Novedad',
            'explicacion' => 'Explicación',
            'referencia'  => 'Referencia',
	    'tecnico'     => 'Dato técnico',
	    'audio'       => 'Audio',
	    'video'       => 'Video',
	    'fotos'       => 'Foto Imágen',
        );
    }
}

if ( ! function_exists( 'obras_get_post_class_label' ) ) {
    function obras_get_post_class_label( $post_id ) {
        $post_type = get_post_type( $post_id );
        $value     = '';
        $choices   = array();

        switch ( $post_type ) {
            case 'bitacora':
                $value   = get_post_meta( $post_id, 'clase_nota', true );
                $choices = obras_get_bitacora_class_choices();
                break;

        }

        if ( $value && isset( $choices[ $value ] ) ) {
            return $choices[ $value ];
        }

        return '';
    }
}

// ============================================================================
// === ACF: CAMPOS VÍA CÓDIGO (sin UI) ========================================
// ============================================================================

if ( function_exists( 'acf_add_local_field_group' ) ) :

    // Bitácora
    acf_add_local_field_group( array(
        'key' => 'group_bitacora_obras',
        'title' => 'Datos de la Nota',
        'fields' => array(
            array(
                'key' => 'field_clase_nota',
                'label' => 'Clase del contenido',
                'name' => 'clase_nota',
                'type' => 'select',
                'choices' => obras_get_bitacora_class_choices(),
                'allow_null' => 0,
                'return_format' => 'value',
            ),
            array(
                'key' => 'field_archivo',
                'label' => 'Adjuntar archivo',
                'name' => 'archivo_adjunto',
                'type' => 'file',
                'return_format' => 'array',
                'mime_types' => 'pdf,doc,docx,jpg,png',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'bitacora',
                ),
            ),
        ),
        'position' => 'normal',
        'label_placement' => 'top',
    ) );

    endif;


    // ============================================================================
    // === AYUDA CONTEXTUAL EN CAMPOS ACF DE ARCHIVO ==============================
    // ============================================================================

    add_action( 'admin_footer', 'obras_acf_file_field_help_tooltips' );
    function obras_acf_file_field_help_tooltips() {
        if ( ! is_admin() ) {
            return;
        }

        $screen = get_current_screen();
        if ( ! $screen ) {
            return;
        }

        if ( 'post' !== $screen->base ) {
            return;
        }

        $allowed_post_types = array(
            'bitacora',
        );

        if ( ! in_array( $screen->post_type, $allowed_post_types, true ) ) {
            return;
        }

        $file_help_map = array(
            'archivo_adjunto'   => 'Adjunta un archivo a esta publicación. No se inserta dentro del texto: quedará disponible como archivo adjunto al final.',
        );

        $media_help = 'Usa “Añadir medios” para insertar una foto o imagen dentro del texto. Usa “Adjuntar archivo” para dejar un archivo asociado a la publicación.';
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var fileHelpMap = <?php echo wp_json_encode( $file_help_map ); ?>;
            var mediaHelp   = <?php echo wp_json_encode( $media_help ); ?>;

            function closeAllHelps(exceptPopup, exceptTrigger) {
                document.querySelectorAll('.obras-help-popup').forEach(function(otherPopup) {
                    if (otherPopup !== exceptPopup) {
                        otherPopup.hidden = true;
                    }
                });

                document.querySelectorAll('.obras-help-trigger').forEach(function(otherTrigger) {
                    if (otherTrigger !== exceptTrigger) {
                        otherTrigger.setAttribute('aria-expanded', 'false');
                    }
                });
            }

            function buildHelpTrigger(text) {
                var trigger = document.createElement('button');
                trigger.type = 'button';
        trigger.className = 'obras-help-trigger';
        trigger.setAttribute('aria-expanded', 'false');
        trigger.setAttribute('title', 'Ayuda');
        trigger.textContent = '?';

        var popup = document.createElement('div');
        popup.className = 'obras-help-popup';
        popup.hidden = true;
        popup.innerHTML = text;

        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var isHidden = popup.hidden;
            closeAllHelps(popup, trigger);
            popup.hidden = !isHidden;
            trigger.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
        });

        return { trigger: trigger, popup: popup };
            }

            Object.keys(fileHelpMap).forEach(function(fieldName) {
                var field = document.querySelector('.acf-field[data-name="' + fieldName + '"]');
                if (!field) {
                    return;
                }

                var label = field.querySelector('.acf-label label');
                var labelWrap = field.querySelector('.acf-label');

                if (!label || !labelWrap) {
                    return;
                }

                if (field.querySelector('.obras-help-trigger')) {
                    return;
                }

                var help = buildHelpTrigger(fileHelpMap[fieldName]);

                label.style.display = 'inline-flex';
            label.style.alignItems = 'center';
            label.style.gap = '8px';

            label.appendChild(help.trigger);
            labelWrap.appendChild(help.popup);
            });

            var mediaButton = document.querySelector('.wp-media-buttons .insert-media');
            var mediaWrap   = document.querySelector('.wp-media-buttons');

            if (mediaButton && mediaWrap && !mediaWrap.querySelector('.obras-media-help-anchor')) {
                var anchor = document.createElement('span');
                anchor.className = 'obras-media-help-anchor';
        anchor.style.position = 'relative';
        anchor.style.display = 'inline-flex';
        anchor.style.alignItems = 'center';
        anchor.style.marginLeft = '8px';

        var mediaHelpParts = buildHelpTrigger(mediaHelp);

        anchor.appendChild(mediaHelpParts.trigger);
        anchor.appendChild(mediaHelpParts.popup);
        mediaWrap.appendChild(anchor);
            }

            document.addEventListener('click', function(e) {
                document.querySelectorAll('.obras-help-popup').forEach(function(popup) {
                    var parent = popup.parentElement ? popup.parentElement.closest('.acf-field, .obras-media-help-anchor') : null;
                    if (parent && !parent.contains(e.target)) {
                        popup.hidden = true;
                    }
                });

                document.querySelectorAll('.obras-help-trigger').forEach(function(trigger) {
                    var parent = trigger.closest('.acf-field, .obras-media-help-anchor');
                    if (parent && !parent.contains(e.target)) {
                        trigger.setAttribute('aria-expanded', 'false');
                    }
                });
            });
        });
        </script>

        <style>
        .acf-field[data-name="archivo_adjunto"] .acf-label,
        .obras-media-help-anchor {
            position: relative;
        }

        .obras-help-trigger {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border: 1px solid #2271b1;
            border-radius: 50%;
            background: #fff;
            color: #2271b1;
            font-weight: 700;
            font-size: 13px;
            line-height: 1;
            cursor: pointer;
            padding: 0;
        }

        .obras-help-trigger:hover,
        .obras-help-trigger:focus {
            background: #2271b1;
            color: #fff;
            outline: none;
        }

        .obras-help-popup {
            position: absolute;
            top: calc(100% + 10px);
            left: 0;
            z-index: 1000;
            max-width: 460px;
            min-width: 260px;
            padding: 12px 14px;
            background: #fff;
            border: 1px solid #ccd0d4;
            border-left: 4px solid #2271b1;
            border-radius: 6px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
            color: #1d2327;
            font-size: 13px;
            line-height: 1.45;
        }
        </style>
        <?php
    }
