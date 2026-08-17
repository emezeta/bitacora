# Bitácora de Obra
## Registro de validación — 0.2.0-dev / schema 3

**Fecha de validación:** 17 de agosto de 2026  
**Rama:** `0.2.0-dev`  
**Commit validado:** `9a197ae9e6b134f6c17d250cc726a7baa255dd19`  
**Commit:** `Integrate installation profile seeders`  
**Perfil de instalación:** `construccion`  
**Schema de instalación:** `3`

---

## 1. Objetivo

El objetivo de esta prueba fue validar integralmente el instalador de Bitácora `0.2.0-dev` sobre una instalación WordPress realmente fresh y aislada.

La prueba debía comprobar que:

1. la primera activación crea la estructura correspondiente al perfil `construccion`;
2. se crean exactamente 5 secciones;
3. se crean exactamente 33 clases;
4. se crean las 8 páginas administradas por Bitácora;
5. el schema de instalación queda establecido en `3`;
6. una segunda ejecución del instalador no introduce ningún cambio;
7. no se duplican términos, metadatos ni páginas.

---

## 2. Código validado

La prueba utilizó exactamente el commit:

`9a197ae9e6b134f6c17d250cc726a7baa255dd19`

Asunto:

`Integrate installation profile seeders`

El paquete de prueba se generó directamente desde Git mediante `git archive`, evitando incluir cambios no commiteados o archivos ajenos al commit.

Archivo generado:

`/tmp/bitacora-de-obra-9a197ae.tar.gz`

SHA-256:

`f62e05f3d453236f0874e54848a24a134ed6873a64e319ac8b5c8f07f578ab48`

---

## 3. Entorno fresh

Sitio temporal utilizado:

`https://bita.dreamhosters.com`

Directorio:

`/home/bita020/bita.dreamhosters.com`

WordPress:

`7.0.4`

PHP:

`8.2.30`

Base de datos:

`bita_dreamhosters_com`

Servidor de base de datos:

`mysql.bita.dreamhosters.com`

La base es independiente de `koopita.angiru.uy`.

Antes de instalar Bitácora se comprobó:

- theme activo: `twentytwentyfive`;
- ausencia del directorio `bitacora-de-obra`;
- ausencia de `bitacora_section`;
- ausencia de `bitacora_class`;
- ausencia de CPT de Bitácora;
- ausencia de `obras_theme_install_schema`;
- únicamente las páginas estándar `Sample Page` y `Privacy Policy`.

---

## 4. Dependencias

Antes de activar Bitácora se instalaron y activaron:

- Advanced Custom Fields `6.8.7`;
- Classic Editor `1.7.0`.

El plugin `dreamhost-panel-login` ya estaba presente y activo como componente propio del hosting.

La instalación de las dependencias no creó ni modificó el schema de Bitácora:

`obras_theme_install_schema = inexistente`

---

## 5. Despliegue del commit

El theme fue extraído desde el archivo generado por `git archive`.

Antes de activarlo se verificó:

- WordPress reconocía `Bitácora de Obra`;
- estado del theme: `inactive`;
- theme activo: `twentytwentyfive`;
- schema: inexistente.

Por lo tanto, la creación de la estructura 0.2.0 se produjo exclusivamente durante la primera activación del theme.

---

## 6. Primera activación

La activación se realizó mediante:

`wp theme activate bitacora-de-obra`

Resultado:

- theme activo: `bitacora-de-obra`;
- schema: `3`.

### Secciones

Se crearon exactamente 5 términos de `bitacora_section`:

- Documentos;
- Materiales;
- Catálogos;
- Planos;
- Paisajismo.

Total:

`5`

### Clases

Se crearon exactamente 33 términos de `bitacora_class`.

Total:

`33`

Los IDs asignados fueron consecutivos del `7` al `39`.

### Páginas

Bitácora creó exactamente las 8 páginas administradas por el instalador:

| ID | Slug | Título |
|---:|---|---|
| 7 | `inicio` | Inicio |
| 8 | `entradas` | Entradas |
| 9 | `documentos` | Documentos |
| 10 | `materiales` | Materiales |
| 11 | `catalogos` | Catálogos |
| 12 | `planos` | Planos |
| 13 | `auxiliar` | Más secciones |
| 14 | `paisajismo` | Paisajismo |

Las páginas estándar de WordPress se conservaron:

- `Sample Page`;
- `Privacy Policy`.

Total de páginas existentes después de la instalación:

`10`

El instalador no eliminó contenido ajeno.

---

## 7. Estado antes de la segunda ejecución

Antes de ejecutar nuevamente `obras_theme_install()` se registró:

- secciones: `5`;
- clases: `33`;
- registros de termmeta asociados a `bitacora_section` y `bitacora_class`: `182`;
- páginas totales: `10`;
- schema: `3`.

También se tomó una fotografía detallada de:

- términos e IDs;
- termmeta;
- páginas e IDs;
- hash MD5 del contenido de las páginas;
- opciones administradas por el instalador.

---

## 8. Segunda ejecución

Se ejecutó explícitamente:

`obras_theme_install()`

El instalador devolvió:

### Secciones

- `created = 0`
- `existing = 5`
- `meta_added = 0`
- `errors = []`

### Clases

- `created = 0`
- `existing = 33`
- `meta_added = 0`
- `errors = []`

### Páginas

Se conservaron exactamente los mismos IDs:

- `inicio = 7`
- `entradas = 8`
- `documentos = 9`
- `materiales = 10`
- `catalogos = 11`
- `planos = 12`
- `auxiliar = 13`
- `paisajismo = 14`

### Resultado global

- `profile = construccion`
- `changed = false`
- `schema = 3`

---

## 9. Comparación antes / después

Después de la segunda ejecución se comprobó nuevamente:

- secciones: `5`;
- clases: `33`;
- termmeta asociado: `182`;
- páginas totales: `10`;
- schema: `3`.

Se compararon mediante `diff` las fotografías completas tomadas antes y después de la segunda ejecución.

Resultado:

`PASS: segunda ejecución = 0 cambios en el estado administrado.`

No cambiaron:

- IDs de términos;
- nombres o slugs;
- metadatos;
- IDs de páginas;
- títulos;
- slugs;
- estados de publicación;
- contenidos de páginas;
- opciones relevantes del instalador.

---

## 10. Resultado

La prueba integral fresh queda satisfactoriamente superada para el commit:

`9a197ae9e6b134f6c17d250cc726a7baa255dd19`

Queda comprobado que el instalador schema 3:

1. inicializa correctamente un WordPress nuevo;
2. aplica el perfil `construccion`;
3. crea exactamente 5 secciones;
4. crea exactamente 33 clases;
5. crea exactamente 8 páginas administradas;
6. conserva contenido estándar ajeno a Bitácora;
7. fija `obras_theme_install_schema` en `3`;
8. es idempotente;
9. no duplica términos;
10. no añade metadatos en ejecuciones posteriores;
11. no recrea ni modifica las páginas existentes;
12. devuelve `changed=false` cuando el estado ya está completamente inicializado.

La integración de los seeders del perfil `construccion` en el instalador puede considerarse validada sobre una instalación WordPress realmente fresh.
