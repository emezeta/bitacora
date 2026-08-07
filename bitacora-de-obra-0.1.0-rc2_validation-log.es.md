# Bitácora de Obra — Theme autónomo
## Registro de validación — 0.1.0-rc2

**Fecha de validación:** 7 de agosto de 2026  
**Versión:** `0.1.0-rc2`  
**Estado:** candidato RC validado  
**Tipo:** WordPress Block Theme autónomo

---

## 1. Objetivo

El objetivo de esta etapa fue transformar **Bitácora de Obra**, originalmente implementada como child theme de Twenty Twenty-Five, en un theme autónomo que:

- no dependa de Twenty Twenty-Five;
- pueda instalarse mediante un ZIP estándar de WordPress;
- conserve la apariencia y funcionalidad del sitio operativo;
- incluya su propia configuración visual, templates, assets y lógica PHP;
- pueda desplegar automáticamente la estructura mínima de la aplicación en un WordPress fresh;
- detecte y notifique las dependencias externas necesarias.

El objetivo fue alcanzado en `0.1.0-rc2`.

---

## 2. Paquete validado

Archivo:

`/home/dosmilun/tmp/bitacora-de-obra-0.1.0-rc2.zip`

Tamaño aproximado:

`1.3 MB`

SHA-256:

`a3fadceccaa2c80bddb1e06b98d3f5d23ab7e7ee5d54992b5ff376dd4aac61ba`

Este hash identifica exactamente el ZIP utilizado en la prueba fresh final.

---

## 3. Autonomía respecto de Twenty Twenty-Five

El nuevo theme:

- no declara `Template: twentytwentyfive`;
- no contiene referencias a `twentytwentyfive`;
- no carga `parent-style`;
- no incluye ni utiliza `tt5-compat`;
- utiliza el mismo directorio como `template` y `stylesheet`;
- continúa funcionando después de retirar físicamente Twenty Twenty-Five de `wp-content/themes`.

WordPress confirmó:

- Theme: `Bitácora de Obra`
- Template: `bitacora-de-obra`
- Stylesheet: `bitacora-de-obra`
- Parent theme: ninguno
- Block theme: sí

La prueba sin TT5 instalado produjo:

- HTTP `200`
- HTML completo
- ninguna referencia residual a TT5
- ningún `Fatal error`, `Parse error`, `Warning`, `Notice` ni `Deprecated` visible.

La autonomía respecto de Twenty Twenty-Five queda demostrada.

---

## 4. Arquitectura del theme

El theme autónomo conserva arquitectura de Block Theme/FSE, pero sin depender de un parent.

Incluye, entre otros:

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

- Beiruti internalizada:
  `assets/fonts/beiruti/Beiruti-VariableFont_wght.woff2`
- `images/login_obras.png`

### CSS

- `css/custom.css`
- `css/landpage.css`
- `css/dashboardfe.css`

### Lógica PHP

Incluye los módulos funcionales de Bitácora dentro de `inc/`, entre ellos:

- CPT
- ACF
- restricciones de acceso
- control de autor
- columnas administrativas
- comentarios
- branding
- landing
- shortcodes
- auxiliar/Paisajismo
- sala Jitsi
- redirects
- dashboard administrativo
- autenticación
- instalación/bootstrap

`editor-cleanup.php` no fue incorporado porque tampoco era cargado por el child operativo utilizado como referencia.

---

## 5. Bootstrap de instalación

RC2 incorpora:

`inc/install.php`

Su objetivo es transformar una instalación fresh de WordPress en la estructura mínima requerida por Bitácora.

La rutina es idempotente:

- no duplica páginas;
- no sobrescribe contenido de páginas existentes;
- no reemplaza una portada previamente configurada;
- no pisa un nombre de sitio personalizado;
- puede ejecutarse repetidamente sin alterar indebidamente la instalación.

Se validó ejecutándola dos veces consecutivas sobre una instalación ya configurada: el número de páginas y sus IDs permanecieron sin cambios.

Schema actual:

`obras_theme_install_schema = 2`

---

## 6. Páginas creadas automáticamente

En una instalación fresh, al activar el theme se crean, si no existen:

| Página | Slug | Contenido |
|---|---|---|
| Inicio | `inicio` | `[obras_dashboard]` |
| Entradas | `entradas` | `[obras_lista_entradas]` |
| Documentos | `documentos` | `[obras_lista_documentos]` |
| Materiales | `materiales` | `[obras_lista_materiales]` |
| Catálogos | `catalogos` | `[obras_lista_catalogos]` |
| Planos | `planos` | `[obras_lista_planos]` |
| Más secciones | `auxiliar` | `[obras_aux_dashboard]` |
| Paisajismo | `paisajismo` | `[obras_lista_aux section="general"]` |

En la instalación fresh de validación fueron creadas automáticamente con IDs 5 a 12.

`Inicio` quedó configurada automáticamente como portada.

Resultado:

- `show_on_front = page`
- `page_on_front = 5`

La página estándar `Sample Page` de WordPress se conservó, confirmando que el instalador no elimina contenido ajeno.

---

## 7. Nombre del sitio

En una instalación fresh, si WordPress todavía utiliza el nombre genérico:

`Just another WordPress site`

el instalador lo sustituye por:

`Bitácora de Obra`

No modifica un nombre personalizado existente.

La descripción genérica también puede ser retirada cuando corresponde.

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

---

## 18. Estado del RC2

`0.1.0-rc2` queda validado como:

**Theme autónomo, instalable y reproducible de Bitácora de Obra.**

Queda demostrado que el ZIP puede instalarse sobre un WordPress fresh y:

1. activarse sin parent theme;
2. generar automáticamente la estructura mínima de Bitácora;
3. configurar la portada;
4. configurar prudentemente el nombre inicial del sitio;
5. detectar dependencias externas;
6. funcionar normalmente una vez activadas ACF y Classic Editor;
7. conservar la apariencia y comportamiento del entorno productivo;
8. aplicar correctamente las restricciones por roles.

---

## 19. Dependencias de despliegue

Para una instalación funcional completa se requiere:

- WordPress compatible con la versión probada;
- Advanced Custom Fields;
- Classic Editor;
- theme `Bitácora de Obra`.

Otros plugins utilizados por instalaciones concretas —por ejemplo Magic Login, WP Super Cache u otros servicios auxiliares— no forman parte del núcleo mínimo validado en este RC.

---

## 20. Candidato conservado

No modificar el archivo:

`bitacora-de-obra-0.1.0-rc2.zip`

sin cambiar número de versión.

Su identidad queda fijada por:

`SHA256 a3fadceccaa2c80bddb1e06b98d3f5d23ab7e7ee5d54992b5ff376dd4aac61ba`

Toda modificación posterior al código debe generar un nuevo candidato (`rc3`, versión final u otra versión según corresponda).

---

## Conclusión

La transición desde el child theme basado en Twenty Twenty-Five hacia un theme autónomo queda técnicamente conseguida.

RC2 no sólo elimina la dependencia del parent theme: incorpora además el bootstrap necesario para desplegar la estructura mínima de Bitácora en una instalación nueva de WordPress y proporciona diagnóstico explícito de sus dependencias funcionales.

El candidato está en condiciones de pasar a una etapa de cierre/promoción de versión, salvo que se decida realizar una ronda adicional de pruebas antes de etiquetar una versión estable.
