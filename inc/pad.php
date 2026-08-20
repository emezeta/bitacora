<?php
/**
 * Bitácora - Pad embebido de prueba
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'obras_pad_editor', 'obras_pad_editor_shortcode' );
function obras_pad_editor_shortcode() {
    ob_start();
    ?>
    <div class="obras-pad-test">
        <iframe
            name="embed_readwrite"
            src="https://pad.cryptler.com/p/bitacora?showControls=true&showChat=false&showLineNumbers=false&useMonospaceFont=false&lang=es"
            frameborder="0">
        </iframe>
    </div>
    <?php
    return ob_get_clean();
}
