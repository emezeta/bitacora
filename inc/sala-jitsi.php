<?php
/**
 * Shortcode: Sala virtual Jitsi / 8x8 JaaS
 */

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('obras_sala_virtual', function ($atts) {

    if (!is_user_logged_in()) {
        return '<p>Debes iniciar sesión para acceder a la sala virtual.</p>';
    }

    $atts = shortcode_atts([
        'height' => '78vh',
    ], $atts, 'obras_sala_virtual');

    $app_id = 'vpaas-magic-cookie-fa6e54802915479c9f99cfd352128225';
    $room   = 'SampleAppSensibleLifetimesRingAllTheTime';

    $room_name = $app_id . '/' . $room;
    $height = sanitize_text_field($atts['height']);

    $container_id = 'obras-jaas-container-' . wp_generate_uuid4();

    wp_enqueue_script(
        'obras-jaas-external-api',
        'https://8x8.vc/' . $app_id . '/external_api.js',
        [],
        null,
        true
    );

    $js = "
    (function() {
        function iniciarSalaJitsi() {
            if (typeof JitsiMeetExternalAPI === 'undefined') {
                setTimeout(iniciarSalaJitsi, 300);
                return;
            }

            var container = document.getElementById(" . wp_json_encode($container_id) . ");

            if (!container || container.dataset.jitsiStarted === '1') {
                return;
            }

            container.dataset.jitsiStarted = '1';

            new JitsiMeetExternalAPI('8x8.vc', {
                roomName: " . wp_json_encode($room_name) . ",
                parentNode: container,
                width: '100%',
                height: '100%',
                configOverwrite: {
                    startWithAudioMuted: true,
                    startWithVideoMuted: true
                },
                interfaceConfigOverwrite: {
                    MOBILE_APP_PROMO: false
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', iniciarSalaJitsi);
        } else {
            iniciarSalaJitsi();
        }
    })();
    ";

    wp_add_inline_script('obras-jaas-external-api', $js, 'after');

    ob_start();
    ?>
    <div class="obras-sala-virtual">
        <div
            id="<?php echo esc_attr($container_id); ?>"
            style="width:100%; height:<?php echo esc_attr($height); ?>; min-height:520px; background:#f2f2f2;"
        ></div>
    </div>
    <?php

    return ob_get_clean();
});
