# Bitácora de Obra — Theme autónomo
## Validation Record — 0.1.0-rc2

**Validation Date:** August 7, 2026
**Version:** `0.1.0-rc2`
**Status:** Validated RC candidate
**Type:** Standalone WordPress Block Theme

---

## 1. Objective

The objective of this phase was to transform **Bitácora de Obra**, originally implemented as a child theme of Twenty Twenty-Five, into a standalone theme that:

- does not depend on Twenty Twenty-Five;
- can be installed using a standard WordPress ZIP file;
- preserves the appearance and functionality of the live site;
- includes its own visual settings, templates, assets, and PHP logic;
- can automatically deploy the application’s minimal structure on a fresh WordPress installation;
- detects and notifies users of any necessary external dependencies.

The goal was achieved in `0.1.0-rc2`.

---

## 2. Validated Package

File:

`bitacora-de-obra-0.1.0-rc2.zip`

Approximate size:

`1.3 MB`

SHA-256:

`a3fadceccaa2c80bddb1e06b98d3f5d23ab7e7ee5d54992b5ff376dd4aac61ba`

This hash exactly matches the ZIP file used in the final fresh test.

---

## 3. Independence from Twenty Twenty-Five

The new theme:

- does not declare `Template: twentytwentyfive`;
- contains no references to `twentytwentyfive`;
- does not load `parent-style`;
- does not include or use `tt5-compat`;
- uses the same directory for both `template` and `stylesheet`;
- continues to function after Twenty Twenty-Five is physically removed from `wp-content/themes`.

WordPress confirmed:

- Theme: `Bitácora de Obra`
- Template: `bitacora-de-obra`
- Stylesheet: `bitacora-de-obra`
- Parent theme: none
- Block theme: yes

The test without TT5 installed resulted in:

- HTTP `200`
- Complete HTML
- No residual references to TT5
- no visible `Fatal errors`, `Parse errors`, `Warnings`, `Notices`, or `Deprecated` messages.

Its independence from Twenty Twenty-Five is demonstrated.

---

## 4. Theme Architecture

The standalone theme retains the Block Theme/FSE architecture but does not depend on a parent theme.

It includes, among others:

- `style.css`
- `functions.php`
- `theme.json`
- `screenshot.png`

### Templates

- `templates/index.html`
- `templates/page.html`
- `templates/single.html`

### Template parts

- `parts/header.html`
- `parts/footer.html`

### Assets

- Beiruti (internalized):
  `assets/fonts/beiruti/Beiruti-VariableFont_wght.woff2`
- `images/login_obras.png`

### CSS

- `css/custom.css`
- `css/landpage.css`
- `css/dashboardfe.css`

### PHP Logic

Includes the Bitácora functional modules within `inc/`, including:

- CPT
- ACF
- access restrictions
- author control
- administrative columns
- comments
- branding
- landing
- shortcodes
- auxiliary/Landscaping
- Jitsi room
- redirects
- admin dashboard
- authentication
- installation/bootstrap

`editor-cleanup.php` was not included because it was not loaded by the reference child theme either.

---

## 5. Installation Bootstrap

RC2 includes:

`inc/install.php`

Its purpose is to transform a fresh WordPress installation into the minimum structure required by Bitácora.

The routine is idempotent:

- it does not duplicate pages;
- it does not overwrite the content of existing pages;
- it does not replace a previously configured homepage;
- it does not override a custom site name;
- it can be run repeatedly without unduly altering the installation.

It was validated by running it twice in a row on an already configured installation: the number of pages and their IDs remained unchanged.

Current schema:

`obras_theme_install_schema = 2`

---

## 6. Automatically Created Pages

In a fresh installation, activating the theme creates the following pages if they do not already exist:

| Page | Slug | Content |
|---|---|---|
| Home | `home` | `[obras_dashboard]` |
| Posts | `posts` | `[obras_post_list]` |
| Documents | `documents` | `[obras_document_list]` |
| Materials | `materials` | `[obras_material_list]` |
| Catalogs | `catalogs` | `[obras_catalog_list]` |
| Plans | `plans` | `[projects_list_plans]` |
| More Sections | `auxiliary` | `[projects_aux_dashboard]` |
| Landscaping | `landscaping` | `[projects_list_aux section="general"]` |

In the fresh validation installation, they were automatically created with IDs 5 through 12.

`Home` was automatically set as the front page.

Result:

- `show_on_front = page`
- `page_on_front = 5`

The standard WordPress `Sample Page` was retained, confirming that the installer does not remove third-party content.

---

## 7. Site Name

In a fresh installation, if WordPress is still using the generic name:

`Just another WordPress site`

the installer replaces it with:

`Construction Blog`

It does not modify an existing custom name.

The generic description can also be removed when appropriate.

---

## 8. Dependencias externas

El theme detecta dos dependencias funcionales:

### Advanced Custom Fields

Plugin:

`advanced-custom-fields/acf.php`

### Classic Editor

Plugin:

`classic-editor/classic-editor.php`

El theme no instala ni activa plugins automáticamente.

Detecta tres estados:

- instalado y activo;
- instalado pero inactivo;
- no instalado.

Si existe un problema, muestra un aviso administrativo a usuarios con permisos para activar plugins.

La información queda además registrada en:

`obras_theme_dependency_status`

### Prueba fresh

Con ambos plugins físicamente instalados pero inactivos, RC2 detectó correctamente:

- Advanced Custom Fields: `inactive`
- Classic Editor: `inactive`

Después de activarlos:

`obras_theme_dependency_status = []`

El aviso administrativo desapareció automáticamente al recargar `/wp-admin/`.

---

## 9. CPT registrados

El theme registró correctamente:

- `bitacora` — Notas
- `documento_obra` — Documentos
- `material_obra` — Materiales
- `catalogo_obra` — Catálogos
- `plano_obra` — Planos
- `aux_section` — Ideas/Paisajismo

---

## 10. Shortcodes registrados

Se comprobó la disponibilidad de:

- `[obras_dashboard]`
- `[obras_lista_entradas]`
- `[obras_lista_documentos]`
- `[obras_lista_materiales]`
- `[obras_lista_catalogos]`
- `[obras_lista_planos]`
- `[obras_menu_logout]`
- `[obras_landing_page]`
- `[obras_pad_editor]`
- `[obras_lista_aux]`
- `[obras_aux_dashboard]`
- `[obras_sala_virtual]`

---

## 11. Regresión visual

La instalación temporal reprodujo el aspecto esperado de Bitácora.

Se verificaron:

- portada/dashboard;
- secciones principales;
- listados vacíos;
- singles;
- tipografía;
- estructura general;
- header y footer;
- CSS propio;
- recursos gráficos.

El theme continúa siendo un Block Theme, pero toda la configuración utilizada proviene del propio `bitacora-de-obra`.

Se comprobó que la base de datos fresh no contenía:

- `wp_template`
- `wp_template_part`
- `wp_global_styles`
- términos FSE asociados a themes anteriores

Por lo tanto, la apariencia observada no provenía de overrides almacenados de Twenty Twenty-Five.

---

## 12. Editor y ACF

Con Advanced Custom Fields y Classic Editor activos, el editor se observó visualmente equivalente al entorno productivo.

Se probaron satisfactoriamente:

- creación de Notas;
- creación de Documentos;
- Classic Editor;
- campos ACF;
- adjuntos;
- imágenes;
- contenido enriquecido;
- autor;
- fecha de publicación;
- publicación;
- edición posterior.

No se detectaron regresiones funcionales ni visuales relevantes.

---

## 13. Listados y singles

Se crearon ndmcp de prueba y se verificó:

- aparición correcta en listados;
- apertura del single;
- contenido;
- imágenes;
- adjuntos;
- edición;
- autor;
- fechas.

Las rutas principales respondieron correctamente:

- `/`
- `/entradas/`
- `/documentos/`
- `/materiales/`
- `/catalogos/`
- `/planos/`
- `/auxiliar/`
- `/paisajismo/`

Todas produjeron HTTP `200`.

`/inicio/` es canonicalizada por WordPress hacia `/` al ser la página configurada como portada.

---

## 14. Roles y acceso administrativo

Se verificó la lógica de acceso:

### Supervisor / Administrator

Puede acceder normalmente a `/wp-admin/`, incluso escribiendo directamente la URL.

### Author

Comportamiento esperado de restricción confirmado.

### Subscriber

Comportamiento esperado de restricción confirmado.

La lógica de `admin-access.php` continúa funcionando correctamente en el theme autónomo.

---

## 15. Frontend final

Con ACF y Classic Editor activados:

- HTTP `200`
- HTML: aproximadamente `44145 bytes`
- sin errores PHP visibles

No aparecieron:

- `Fatal error`
- `Parse error`
- `Warning`
- `Notice`
- `Deprecated`

---

## 16. Miniatura del theme

Se agregó:

`screenshot.png`

Características:

- PNG
- 1200 × 900
- aproximadamente 816 KB

WordPress la reconoce correctamente como miniatura del theme en Apariencia → Temas.

---

## 17. Auditoría PHP

Todos los archivos PHP incluidos en RC2 pasaron `php -l` sin errores de sintaxis.

También se verificó nuevamente la ausencia de referencias a:

- `twentytwentyfive`
- `parent-style`
- `tt5-compat`

Resultado: limpio.

----

## 8. External Dependencies

The theme detects two functional dependencies:

### Advanced Custom Fields

Plugin:

`advanced-custom-fields/acf.php`

### Classic Editor

Plugin:

`classic-editor/classic-editor.php`

The theme does not automatically install or activate plugins.

It detects three states:

- installed and active;
- installed but inactive;
- not installed.

If there is a problem, it displays an administrative notice to users with permissions to activate plugins.

The information is also logged in:

`obras_theme_dependency_status`

### Fresh Test

With both plugins physically installed but inactive, RC2 correctly detected:

- Advanced Custom Fields: `inactive`
- Classic Editor: `inactive`

After activating them:

`obras_theme_dependency_status = []`

The administrative notice disappeared automatically upon reloading `/wp-admin/`.

---

## 9. Registered CPTs

The theme correctly registered:

- `bitacora` — Notes
- `documento_obra` — Documents
- `material_obra` — Materials
- `catalogo_obra` — Catalogs
- `plano_obra` — Blueprints
- `aux_section` — Ideas/Landscaping

---

## 10. Registered Shortcodes

The following were checked for availability:

- `[obras_dashboard]`
- `[obras_list_posts]`
- `[obras_list_documents]`
- `[obras_list_materials]`
- `[obras_list_catalogs]`
- `[obras_lista_planos]`
- `[obras_menu_logout]`
- `[obras_landing_page]`
- `[obras_pad_editor]`
- `[obras_lista_aux]`
- `[obras_aux_dashboard]`
- `[obras_sala_virtual]`

---

## 11. Visual Regression

The temporary installation reproduced the expected appearance of Bitácora.

The following were verified:

- homepage/dashboard;
- main sections;
- empty lists;
- single posts;
- typography;
- overall structure;
- header and footer;
- custom CSS;
- graphic resources.

The theme remains a Block Theme, but all configuration settings used come from `bitacora-de-obra` itself.

It was verified that the fresh database did not contain:

- `wp_template`
- `wp_template_part`
- `wp_global_styles`
- FSE terms associated with previous themes

Therefore, the observed appearance did not stem from stored Twenty Twenty-Five overrides.

---

## 12. Editor and ACF

With Advanced Custom Fields and the Classic Editor enabled, the editor appeared visually equivalent to the production environment.

The following were successfully tested:

- creating Notes;
- creating Documents;
- Classic Editor;
- ACF fields;
- attachments;
- images;
- rich content;
- author;
- publication date;
- publication;
- subsequent editing.

No relevant functional or visual regressions were detected.

---

## 13. Lists and Single Posts

Test ndmcp entries were created, and the following were verified:

- correct display in lists;
- opening the single post;
- content;
- images;
- attachments;
- editing;
- author;
- dates.

The main URLs responded correctly:

- `/`
- `/posts/`
- `/documents/`
- `/materials/`
- `/catalogs/`
- `/plans/`
- `/auxiliary/`
- `/landscaping/`

All returned an HTTP `200` status code.

`/home/` is canonicalized by WordPress to `/` since the page is configured as the homepage.

---

## 14. Roles and Administrative Access

Access logic was verified:

### Supervisor / Administrator

Can access `/wp-admin/` normally, even by typing the URL directly.

### Author

Expected restriction behavior confirmed.

### Subscriber

Expected restriction behavior confirmed.

The logic in `admin-access.php` continues to function correctly in the standalone theme.

---

## 15. Final Frontend

With ACF and Classic Editor enabled:

- HTTP `200`
- HTML: approximately `44,145 bytes`
- No visible PHP errors

The following did not appear:

- `Fatal error`
- `Parse error`
- `Warning`
- `Notice`
- `Deprecated`

---

## 16. Theme Thumbnail

Added:

`screenshot.png`

Characteristics:

- PNG
- 1200 × 900
- approximately 816 KB

WordPress correctly recognizes it as a theme thumbnail in Appearance → Themes.

---

## 17. PHP Audit

All PHP files included in RC2 passed `php -l` without any syntax errors.

We also rechecked for the absence of references to:

- `twentytwentyfive`
- `parent-style`
- `tt5-compat`

Result: clean.

---

## 18. RC2 Status

`0.1.0-rc2` has been validated as:

**A standalone, installable, and reproducible Construction Log theme.**

It has been demonstrated that the ZIP file can be installed on a fresh WordPress installation and:

1. activated without a parent theme;
2. automatically generate the minimum Bitácora structure;
3. configure the homepage;
4. prudently configure the initial site name;
5. detect external dependencies;
6. function normally once ACF and Classic Editor are activated;
7. preserve the appearance and behavior of the production environment;
8. correctly apply role-based restrictions.

---

## 19. Deployment Dependencies

A fully functional installation requires:

- WordPress compatible with the tested version;
- Advanced Custom Fields;
- Classic Editor;
- the `Bitácora de Obra` theme.

Other plugins used by specific installations—such as Magic Login, WP Super Cache, or other auxiliary services—are not part of the minimum core validated in this RC.

---

## 20. Release Candidate Retained

Do not modify the file:

`bitacora-de-obra-0.1.0-rc2.zip`

without changing the version number.

Its identity is defined by:

`SHA256 a3fadceccaa2c80bddb1e06b98d3f5d23ab7e7ee5d54992b5ff376dd4aac61ba`

Any subsequent code changes must generate a new release candidate (`rc3`, final version, or another version as appropriate).

---

## Conclusion

The transition from the Twenty Twenty-Five-based child theme to a standalone theme has been technically accomplished.

RC2 not only eliminates the dependency on the parent theme but also incorporates the necessary bootstrap to deploy the minimal Bitácora structure on a new WordPress installation and provides explicit diagnostics of its functional dependencies.

The release candidate is ready to move to the release/promotion stage, unless it is decided to conduct an additional round of testing before tagging a stable version.
