<?php
/**
 * Bitácora de Obra - Landing Page.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ============================================================================
// === LANDING PAGE PARA NO-LOGUEADOS =========================================
// ============================================================================

add_shortcode( 'obras_landing_page', 'obras_render_landing_page' );
function obras_render_landing_page() {
    if ( is_user_logged_in() ) {
        return do_shortcode( '[obras_dashboard]' );
    }

    $logo = get_theme_file_uri( '/images/login_obras.png' );
    ob_start();
    ?>
    <div class="obras-landing">
        <div class="obras-landing-logo">
            <img src="<?php echo esc_url( $logo ); ?>" alt="Obras Angirü">
        </div>

        <h1>Bitácora de Obra</h1>

        <p class="subtitle">
            Gestión simplificada de proyectos en construcción.<br>
            Documentación, materiales y seguimiento en un solo lugar.
        </p>

        <div class="obras-landing-buttons">
            <a href="<?php echo esc_url( wp_login_url() ); ?>" class="obras-landing-btn login">🔐 Acceder</a>
            <a href="<?php echo esc_url( wp_registration_url() ); ?>" class="obras-landing-btn register">📝 Registrarse</a>
        </div>

        <div class="obras-landing-features">
            <div class="obras-feature obras-feature-locked" tabindex="0" role="button" aria-label="Notas">
                <span class="icon">✍️</span>
                <h3>Notas rápidas</h3>
                <p>Registra actividades y novedades de obra en minutos</p>
            </div>

            <div class="obras-feature obras-feature-locked" tabindex="0" role="button" aria-label="Documentos">
                <span class="icon">📄</span>
                <h3>Documentos</h3>
                <p>Accedé a normas, descripciones, instructivos, permisos cuando los necesites</p>
            </div>

            <div class="obras-feature obras-feature-locked" tabindex="0" role="button" aria-label="Materiales">
                <span class="icon">🧰</span>
                <h3>Materiales</h3>
                <p>Seguimiento de recursos y ubicación en la obra</p>
            </div>

            <div class="obras-feature obras-feature-locked" tabindex="0" role="button" aria-label="Catálogos">
                <span class="icon">📚</span>
                <h3>Catálogos</h3>
                <p>Consultá catálogos de productos y materiales</p>
            </div>

            <div class="obras-feature obras-feature-locked" tabindex="0" role="button" aria-label="Planos">
                <span class="icon">📐</span>
                <h3>Planos</h3>
                <p>Visualizá planos en PDF e imágenes de la obra</p>
            </div>

            <div class="obras-feature obras-feature-alien obras-feature-locked" tabindex="0" role="button" aria-label="Acceso restringido">
                <span class="icon">🛸</span>
                <h3>Más secciones</h3>
                <p>Tareas, ideas y otros espacios complementarios</p>
            </div>
        </div>

        <div class="obras-landing-footer">
            <p>🏗️ Obras Angirü - Gestión de proyectos en construcción</p>
            <p style="margin-top:10px; font-size:1em;">Acceso exclusivo para miembros del proyecto</p>
        </div>
    </div>

    <div id="obras-landing-notice" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:99999; align-items:center; justify-content:center;">
        <div style="background:#fff; max-width:420px; width:calc(100% - 32px); padding:24px; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,.2); text-align:center;">
            <p style="margin:0 0 18px; font-size:1.05rem; line-height:1.5;">
                Para usar esta función, primero deberías ingresar o registrarte.
            </p>

            <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
                <a href="<?php echo esc_url( wp_login_url() ); ?>" style="display:inline-block; padding:12px 18px; background:#2271b1; color:#fff; text-decoration:none; border-radius:8px; font-weight:600;">Acceder</a>
                <a href="<?php echo esc_url( wp_registration_url() ); ?>" style="display:inline-block; padding:12px 18px; background:#6c757d; color:#fff; text-decoration:none; border-radius:8px; font-weight:600;">Registrarse</a>
                <button type="button" id="obras-landing-notice-close" style="padding:12px 18px; border:1px solid #ccc; background:#fff; border-radius:8px; cursor:pointer;">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var cards = document.querySelectorAll('.obras-feature-locked');
        var modal = document.getElementById('obras-landing-notice');
        var close = document.getElementById('obras-landing-notice-close');

        function openModal() {
            if (modal) {
                modal.style.display = 'flex';
            }
        }

        function closeModal() {
            if (modal) {
                modal.style.display = 'none';
            }
        }

        cards.forEach(function(card) {
            card.addEventListener('click', openModal);
            card.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    openModal();
                }
            });
        });

        if (close) {
            close.addEventListener('click', closeModal);
        }

        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal();
                }
            });
        }
    });
    </script>
    <?php
    return ob_get_clean();
}

add_filter( 'the_content', 'obras_landing_content_filter' );
function obras_landing_content_filter( $content ) {
    if ( is_front_page() && ! is_user_logged_in() ) {
        return do_shortcode( '[obras_landing_page]' );
    }

    return $content;
}
