<?php
/**
 * Bitácora - Editor genérico de ítems de sección.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Resuelve la sección del contexto de edición.
 *
 * Prioridad:
 * 1. relación ya existente del bitacora_item;
 * 2. parámetro bitacora_section de la URL de creación.
 *
 * Sólo se acepta como contexto de creación una sección activa.
 */
function bitacora_get_item_editor_section( $post_id = 0 ) {

    $post_id = (int) $post_id;

    if ( $post_id ) {
        $section = bitacora_get_item_section( $post_id );

        if ( $section ) {
            return $section;
        }
    }

    if ( empty( $_GET['bitacora_section'] ) ) {
        return false;
    }

    $slug = sanitize_title(
        wp_unslash( $_GET['bitacora_section'] )
    );

    if ( ! $slug ) {
        return false;
    }

    $section = bitacora_get_section( $slug );

    if ( ! $section ) {
        return false;
    }

    if (
        'active' !== bitacora_get_section_meta(
            $section,
            'bitacora_section_state',
            ''
        )
    ) {
        return false;
    }

    return $section;
}


/**
 * Un bitacora_item nuevo sólo puede abrirse desde una sección válida.
 *
 * Ejemplo:
 *
 * post-new.php?post_type=bitacora_item&bitacora_section=materiales
 */
add_action(
    'load-post-new.php',
    'bitacora_require_item_section_context'
);

function bitacora_require_item_section_context() {

    $post_type = isset( $_GET['post_type'] )
        ? sanitize_key( wp_unslash( $_GET['post_type'] ) )
        : '';

    if ( 'bitacora_item' !== $post_type ) {
        return;
    }

    if ( bitacora_get_item_editor_section() ) {
        return;
    }

    wp_die(
        esc_html__(
            'Para crear un ítem debes ingresar desde una sección activa.',
            'bitacora'
        ),
        esc_html__( 'Sección requerida', 'bitacora' ),
        array(
            'response'  => 400,
            'back_link' => true,
        )
    );
}


/**
 * Fija la sección en el auto-draft generado por WordPress.
 *
 * De este modo el contexto deja de depender de la URL una vez
 * abierto el editor.
 */
add_action(
    'add_meta_boxes_bitacora_item',
    'bitacora_bind_new_item_section',
    1
);

function bitacora_bind_new_item_section( $post ) {

    if ( ! $post instanceof WP_Post ) {
        return;
    }

    if ( bitacora_get_item_section( $post->ID ) ) {
        return;
    }

    $section = bitacora_get_item_editor_section(
        $post->ID
    );

    if ( ! $section ) {
        return;
    }

    bitacora_set_item_section(
        $post->ID,
        $section
    );
}


/**
 * Agrega el metabox de contexto del ítem.
 */
add_action(
    'add_meta_boxes_bitacora_item',
    'bitacora_add_item_context_metabox',
    20
);

function bitacora_add_item_context_metabox( $post ) {

    add_meta_box(
        'bitacora-item-context',
        'Tipo',
        'bitacora_render_item_context_metabox',
        'bitacora_item',
        'normal',
        'high'
    );
}


/**
 * Renderiza sección y selector de clase.
 */
function bitacora_render_item_context_metabox( $post ) {

    wp_nonce_field(
        'bitacora_save_item_context',
        'bitacora_item_context_nonce'
    );

    $section = bitacora_get_item_editor_section(
        $post->ID
    );

    if ( ! $section ) {
        echo '<p>No existe una sección válida para este ítem.</p>';
        return;
    }

    $current_class = bitacora_get_item_class(
        $post->ID
    );

    $classes = bitacora_get_classes(
        array(
            'scope'    => 'section',
            'scope_id' => $section->slug,
            'state'    => 'active',
        )
    );

    $choices = array();

    foreach ( $classes as $class ) {
        $choices[ $class->slug ] = $class->name;
    }

    /*
     * Si un contenido existente conserva una clase no activa,
     * mantenerla visible para no perder información al editar.
     */
    if (
        $current_class
        && ! isset( $choices[ $current_class->slug ] )
    ) {
        $choices[ $current_class->slug ] =
            $current_class->name . ' (actual)';
    }

    echo '<select'
        . ' name="bitacora_item_class"'
        . ' id="bitacora_item_class"'
        . ' style="width:100%;">';

    echo '<option value="">Sin clasificar</option>';

    foreach ( $choices as $slug => $label ) {

        printf(
            '<option value="%1$s"%2$s>%3$s</option>',
            esc_attr( $slug ),
            selected(
                $current_class
                    ? $current_class->slug
                    : '',
                $slug,
                false
            ),
            esc_html( $label )
        );
    }

    echo '</select>';
}


/**
 * Guarda la clase usando exclusivamente la API del modelo.
 */
add_action(
    'save_post_bitacora_item',
    'bitacora_save_item_context',
    20,
    3
);

function bitacora_save_item_context(
    $post_id,
    $post,
    $update
) {

    if (
        defined( 'DOING_AUTOSAVE' )
        && DOING_AUTOSAVE
    ) {
        return;
    }

    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    if (
        ! isset( $_POST['bitacora_item_context_nonce'] )
        || ! wp_verify_nonce(
            sanitize_text_field(
                wp_unslash(
                    $_POST['bitacora_item_context_nonce']
                )
            ),
            'bitacora_save_item_context'
        )
    ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $class_slug = isset( $_POST['bitacora_item_class'] )
        ? sanitize_title(
            wp_unslash(
                $_POST['bitacora_item_class']
            )
        )
        : '';

    /*
     * El setter verifica:
     * - 0..1 clase;
     * - existencia de sección;
     * - scope=section;
     * - scope_id compatible con la sección.
     */
    bitacora_set_item_class(
        $post_id,
        $class_slug
    );
}


// ============================================================================
// === FEATURES DEL ÍTEM ======================================================
// ============================================================================

/**
 * Campos físicos genéricos de bitacora_item.
 *
 * La presencia visible de cada campo depende de las features
 * configuradas en la sección.
 */
add_action( 'acf/init', 'bitacora_register_item_feature_fields' );

function bitacora_register_item_feature_fields() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group(
        array(
            'key'   => 'group_bitacora_item_features',
            'title' => 'Más datos',

            'fields' => array(
                array(
                    'key'     => 'field_bitacora_item_type_placeholder',
                    'label'   => 'Tipo',
                    'name'    => 'bitacora_item_type_placeholder',
                    'type'    => 'message',
                    'message' => '',
                ),

                array(
                    'key'           => 'field_bitacora_item_file',
                    'label'         => 'Archivo de referencia',
                    'name'          => 'bitacora_item_file',
                    'type'          => 'file',
                    'return_format' => 'id',
                    'required'      => 0,
                ),

                array(
                    'key'         => 'field_bitacora_item_location',
                    'label'       => 'Ubicación',
                    'name'        => 'bitacora_item_location',
                    'type'        => 'text',
                    'required'    => 0,
                    'placeholder' => 'Ej.: está en el depósito del fondo…',
                ),
            ),

            'location' => array(
                array(
                    array(
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => 'bitacora_item',
                    ),
                ),
            ),

            'position'        => 'normal',
            'label_placement' => 'top',
        )
    );
}


/**
 * Integra visualmente el selector de Tipo dentro de "Más datos".
 *
 * El metabox propio continúa siendo responsable del control y nonce;
 * ACF sólo proporciona el contenedor visual.
 */
add_action(
    'acf/input/admin_footer',
    'bitacora_merge_item_context_into_more_data'
);

function bitacora_merge_item_context_into_more_data() {

    $screen = get_current_screen();

    if (
        ! $screen
        || 'bitacora_item' !== $screen->post_type
    ) {
        return;
    }
    ?>
    <script>
    (function($) {
        $(function() {
            var $postbox = $('#bitacora-item-context');

            var $target = $(
                '.acf-field[data-key="field_bitacora_item_type_placeholder"] > .acf-input'
            );

            if (! $postbox.length || ! $target.length) {
                return;
            }

            var $source = $postbox.children('.inside');

            if (! $source.length) {
                return;
            }

            $target.empty().append(
                $source.children()
            );

            $postbox.remove();
        });
    })(jQuery);
    </script>
    <?php
}


/**
 * Ayudas contextuales del editor.
 *
 * Se muestran mediante botones accesibles por clic/teclado.
 * No dependen de hover y no modifican el almacenamiento de datos.
 */
add_action(
    'acf/input/admin_footer',
    'bitacora_render_item_help_ui',
    20
);

function bitacora_render_item_help_ui() {

    $screen = get_current_screen();

    if (
        ! $screen
        || 'bitacora_item' !== $screen->post_type
    ) {
        return;
    }
    ?>
    <style>
        .bitacora-help-enabled > .acf-label > label {
            display: inline-block;
        }

        .bitacora-help-toggle {
            display: inline-block;
            margin: 0 0 0 4px;
            padding: 0 3px;
            border: 0;
            background: transparent;
            box-shadow: none;
            color: #2271b1;
            font: inherit;
            font-weight: 700;
            line-height: inherit;
            vertical-align: baseline;
            cursor: pointer;
        }

        .bitacora-help-toggle:hover {
            text-decoration: underline;
        }

        .bitacora-help-toggle:focus {
            outline: 1px dotted currentColor;
            outline-offset: 1px;
        }

        .bitacora-help-text {
            margin: 8px 0 4px;
            max-width: 48em;
            font-size: 13px;
            line-height: 1.5;
        }
    </style>

    <script>
    (function($) {

        var helpTexts = {
            field_bitacora_item_type_placeholder:
                'Elegí la opción que mejor describe este contenido. Servirá para organizarlo y encontrarlo más fácilmente.',

            field_bitacora_item_file:
                'Usalo para guardar un archivo que documenta o complementa este contenido.'
        };

        function addHelp() {

            Object.keys(helpTexts).forEach(function(key) {

                var $field = $(
                    '.acf-field[data-key="' + key + '"]'
                );

                if (! $field.length) {
                    return;
                }

                if ($field.hasClass('bitacora-help-enabled')) {
                    return;
                }

                var $label = $field
                    .children('.acf-label')
                    .find('label')
                    .first();

                if (! $label.length) {
                    return;
                }

                var helpId = 'bitacora-help-' + key;

                var $button = $('<button>', {
                    type: 'button',
                    class: 'bitacora-help-toggle',
                    'aria-label': 'Mostrar ayuda',
                    'aria-expanded': 'false',
                    'aria-controls': helpId,
                    text: '?'
                });

                var $help = $('<div>', {
                    id: helpId,
                    class: 'bitacora-help-text',
                    hidden: true,
                    text: helpTexts[key]
                });

                $label.after($button);

                $field
                    .children('.acf-label')
                    .append($help);

                $field.addClass('bitacora-help-enabled');

                $button.on('click', function() {

                    var expanded =
                        $(this).attr('aria-expanded') === 'true';

                    $(this).attr(
                        'aria-expanded',
                        expanded ? 'false' : 'true'
                    );

                    $help.prop('hidden', expanded);
                });

                $button.on('keydown', function(event) {

                    if (event.key !== 'Escape') {
                        return;
                    }

                    $(this).attr(
                        'aria-expanded',
                        'false'
                    );

                    $help.prop('hidden', true);
                });
            });
        }

        $(addHelp);

        if (
            typeof acf !== 'undefined'
            && acf.addAction
        ) {
            acf.addAction('append', addHelp);
        }

    })(jQuery);
    </script>
    <?php
}


/**
 * Devuelve la sección correspondiente al post que ACF está editando.
 */
function bitacora_get_acf_item_section() {

    $post_id = 0;

    if ( isset( $_GET['post'] ) ) {
        $post_id = absint( $_GET['post'] );
    } elseif ( isset( $_POST['post_ID'] ) ) {
        $post_id = absint( $_POST['post_ID'] );
    }

    return bitacora_get_item_editor_section( $post_id );
}


/**
 * Mostrar el campo Archivo sólo cuando la sección tenga feature=file.
 */
add_filter(
    'acf/prepare_field/key=field_bitacora_item_file',
    'bitacora_prepare_item_file_field'
);

function bitacora_prepare_item_file_field( $field ) {

    $section = bitacora_get_acf_item_section();

    if (
        ! $section
        || ! bitacora_section_has_feature( $section, 'file' )
    ) {
        return false;
    }

    return $field;
}


/**
 * Mostrar Ubicación sólo cuando la sección tenga feature=location.
 */
add_filter(
    'acf/prepare_field/key=field_bitacora_item_location',
    'bitacora_prepare_item_location_field'
);

function bitacora_prepare_item_location_field( $field ) {

    $section = bitacora_get_acf_item_section();

    if (
        ! $section
        || ! bitacora_section_has_feature( $section, 'location' )
    ) {
        return false;
    }

    return $field;
}


/**
 * La imagen destacada es soporte físico del CPT, pero sólo debe
 * aparecer cuando la sección tenga feature=thumbnail.
 */
add_action(
    'add_meta_boxes_bitacora_item',
    'bitacora_configure_item_thumbnail_metabox',
    30
);

function bitacora_configure_item_thumbnail_metabox( $post ) {

    $section = bitacora_get_item_editor_section(
        $post instanceof WP_Post ? $post->ID : 0
    );

    if (
        $section
        && bitacora_section_has_feature(
            $section,
            'thumbnail'
        )
    ) {
        return;
    }

    remove_meta_box(
        'postimagediv',
        'bitacora_item',
        'side'
    );
}


// ============================================================================
// === ETIQUETAS CONTEXTUALES DEL EDITOR ======================================
// ============================================================================

/**
 * Adapta las etiquetas visibles del editor al contexto de la sección.
 *
 * El CPT físico sigue siendo bitacora_item. Sólo cambia su presentación
 * durante la edición de un contenido concreto.
 */
add_action(
    'load-post-new.php',
    'bitacora_customize_item_editor_labels',
    20
);

add_action(
    'load-post.php',
    'bitacora_customize_item_editor_labels',
    20
);

function bitacora_customize_item_editor_labels() {

    $screen = get_current_screen();

    if (
        ! $screen
        || 'bitacora_item' !== $screen->post_type
    ) {
        return;
    }

    $post_id = isset( $_GET['post'] )
        ? absint( $_GET['post'] )
        : 0;

    $section = bitacora_get_item_editor_section(
        $post_id
    );

    if ( ! $section ) {
        return;
    }

    $post_type = get_post_type_object(
        'bitacora_item'
    );

    if (
        ! $post_type
        || empty( $post_type->labels )
    ) {
        return;
    }

    $singular = bitacora_get_section_meta(
        $section,
        'bitacora_section_singular',
        $section->name
    );

    $plural = bitacora_get_section_meta(
        $section,
        'bitacora_section_plural',
        $section->name
    );

    $new_label = bitacora_get_section_meta(
        $section,
        'bitacora_section_new_label',
        ''
    );

    $singular_lower = strtolower(
        (string) $singular
    );

    if ( '' === $new_label ) {
        $new_label = 'Nuevo ' . $singular_lower;
    }

    $post_type->labels->add_new_item =
        $new_label;

    $post_type->labels->new_item =
        $new_label;

    $post_type->labels->edit_item =
        'Editar ' . $singular_lower;

    $post_type->labels->view_item =
        'Ver ' . $singular_lower;

    $post_type->labels->singular_name =
        $singular;

    $post_type->labels->name_admin_bar =
        $singular;

    $post_type->labels->all_items =
        $plural;
}
