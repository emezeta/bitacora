<?php
/**
 * Plantilla de comentarios de Bitácora.
 */

if ( post_password_required() ) {
    return;
}
?>

<div id="comments" class="comments-area">

    <?php if ( have_comments() ) : ?>

        <h2 class="comments-title">
            <?php
            $comment_count = get_comments_number();

            printf(
                esc_html(
                    _n(
                        '%s respuesta',
                        '%s respuestas',
                        $comment_count,
                        'bitacora-de-obra'
                    )
                ),
                esc_html( number_format_i18n( $comment_count ) )
            );
            ?>
        </h2>

        <ol class="comment-list">
            <?php
            wp_list_comments(
                array(
                    'style'       => 'ol',
                    'short_ping'  => true,
                    'avatar_size' => 48,
                )
            );
            ?>
        </ol>

        <?php the_comments_navigation(); ?>

    <?php endif; ?>

    <?php if ( ! comments_open() && get_comments_number() ) : ?>
        <p class="no-comments">Los comentarios están cerrados.</p>
    <?php endif; ?>

    <?php comment_form(); ?>

</div>
