# Bitácora — Contexto Maestro de desarrollo

**Revisión documental:** 2026-10-01
**Estado técnico de referencia:** 2026-10-01
**Rama:** `0.3.0-dev`  
**Repo canónico:** `/home/dosmilun/repos/bitacora`  
**HEAD verificado:** `2d6032a Add documental backup download`

> Este documento describe el **estado vigente** del proyecto y debe servir como referencia
> de continuidad entre chats y estaciones de trabajo. No pretende narrar toda la historia.
> Cuando una idea anterior sea importante para evitar regresiones conceptuales, se conserva
> explícitamente como **SUPERADA**, **DEUDA ACTUAL** o **PENDIENTE**.

---

# 1. Autoridad y mantenimiento de este documento

Orden de autoridad:

```text
1. código + pruebas reales
2. este CONTEXTO-MAESTRO versionado
3. conversations.json / chats históricos para arqueología
4. memoria de ChatGPT como ayuda, no como autoridad
```

Estados usados:

```text
CERRADO
    decisión o implementación verificada

ESTADO ACTUAL
    comportamiento real del código/UI hoy

DECIDIDO / PENDIENTE DE IMPLEMENTACIÓN
    decisión conceptual cerrada; falta llevarla al código

PENDIENTE DE ANÁLISIS
    problema identificado cuya solución todavía no está cerrada

DEUDA ACTUAL
    código o UI existente que responde a un modelo anterior y debe realinearse

OBSERVACIÓN
    hallazgo útil, no necesariamente requisito

SUPERADO
    hipótesis, mecanismo o terminología reemplazada por una decisión posterior
```

Regla de mantenimiento:

```text
- describir una sola vez cada asunto
- diferenciar siempre MODELO OBJETIVO de ESTADO ACTUAL
- mantener un único registro de pendientes
- no reabrir decisiones cerradas por pérdida de contexto
```

---

# 2. Alcance del producto

Bitácora es una mini-app sobre WordPress para registrar y consultar memoria cotidiana de
una actividad o proceso:

```text
- anotaciones
- ideas
- novedades
- decisiones
- documentos
- fotografías, videos y adjuntos
- referencias
- información localizada
- comentarios
- seguimiento informal
```

Puede utilizarse en una obra, un evento, un centro educativo, un depósito u otros contextos.

Principio rector:

> **Bitácora debe ser simple, predecible y usable por personas con baja alfabetización
> digital. Debe conservar memoria de trabajo sin transformarse en un sistema generalista.**

No pretende convertirse en:

```text
ERP
sistema contable
CRM
sistema académico
inventario integral
gestor de proyectos generalista
workflow engine
sistema documental corporativo complejo
```

---

# 3. Rebranding — CERRADO

El rebranding está terminado:

```text
"Bitácora de Obra" → "Bitácora"
```

Incluyó logo y sanitización del código heredado relacionado con el antiguo dominio de obra.

No queda un pendiente de “rebranding”. La edición futura de nombres visibles dentro de una
instancia es otro problema y no debe confundirse con éste.

---

# 4. Vocabulario arquitectónico vigente

Esta terminología debe usarse con rigor.

## 4.1 Bitácora Engine

**Bitácora Engine** es el núcleo del producto/software.

Responsabilidades:

```text
- conocer y administrar Perfiles Bitácora
- mantener la biblioteca de Perfiles
- crear una instancia a partir de un Perfil
- garantizar los servicios normales de una instancia
- cerrar una instancia por orden del Supervisor
- quedar en estado neutro cuando no existe instancia
- exponer, cuando se implemente, la frontera/API para productores de Perfiles
```

Evitar usar `core` para denominar al software cuando pueda confundirse con la sección
estructural obligatoria.

## 4.2 Bitácora core section / sección Bitácora

La **Bitácora core section** es la sección estructural obligatoria que Bitácora Engine
aporta a toda instancia.

En lenguaje de interfaz puede llamarse simplemente **sección Bitácora**.

Identidad estructural vigente:

```text
name default: Bitácora
slug técnico: notas
role: core
area: main
state: active
order: 0
```

Defaults actuales:

```text
feature_file: true
feature_location: false
feature_comments: true
```

`feature_thumbnail` fue retirado definitivamente.

## 4.3 Perfil Bitácora

Un **Perfil Bitácora** es una definición que ya atravesó satisfactoriamente la frontera de
adopción del Engine y puede ser usada como base para crear una instancia.

Regla conceptual fuerte:

> **Para Bitácora Engine, “Perfil válido” es redundante. O es un Perfil Bitácora o no es
> un Perfil.**

El Engine no conoce ni administra:

```text
Perfil incompleto
Perfil inválido
Perfil en edición
Perfil pendiente
Perfil casi listo
```

Esos conceptos pertenecen, si resultan útiles, al productor de definiciones.

Un Perfil contiene estructura inicial, no historia runtime.

## 4.4 Instancia

Una **instancia** es una Bitácora concreta en operación.

No hace falta decir “instancia materializada”: si existe una instancia, ya existe el runtime
que la constituye.

Una instancia contiene:

```text
bitacora_section
bitacora_class
páginas
bitacora_item
comentarios
adjuntos / archivos físicos
IDs WordPress
relaciones
estado operativo
```

Estados operativos actualmente concebidos:

```text
EN USO
EN PAUSA
```

La instalación puede encontrarse:

```text
sin instancia
con una instancia
```

No se prevé operar más de una instancia simultáneamente en una instalación.

## 4.5 Template / Plantilla

Una **Template** o **Plantilla** es un objeto de autoría perteneciente a un productor
externo u opcional —por ejemplo, un futuro Constructor de Templates.

Puede estar:

```text
incompleta
en edición
con errores
lista para promover
duplicada respecto de un Perfil existente
```

Nada de eso pertenece al dominio de Bitácora Engine.

Relación conceptual:

```text
PROMOCIÓN iniciada por productor
    Template → candidato → frontera del Engine

CREACIÓN iniciada por Engine
    leer Template externo → candidato → validación/adopción

ACCEPT
    → nace Perfil Bitácora
    → puede originar una Instancia
```

No existe un Perfil Bitácora fuera del Engine. Fuera de Bitácora existen Templates.

---

# 5. Constructor de Templates como capacidad opcional

La construcción de Perfiles deja de considerarse una responsabilidad esencial de Bitácora.

Modelo objetivo:

```text
BITÁCORA ENGINE
    Perfil → Instancia

CONSTRUCTOR OPCIONAL
    Template → candidato → promoción → Perfil
```

El Constructor puede implementarse como plugin/addon/módulo opcional.

Bitácora debe funcionar perfectamente con una biblioteca de Perfiles predefinidos sin
tener instalado ningún Constructor.

La antigua creación y edición progresiva de Perfiles `stored` dentro del Engine fue
retirada después de cerrar la frontera contractual.

El Engine ya no contiene una UX de autoría de “Perfil en construcción”. Esa responsabilidad
queda reservada para un futuro productor/Constructor de Templates.

Estado verificado al 2026-09-29:

```text
069acbc  Remove legacy profile creation
91f0861  Remove legacy profile editing
b4a5a4d  Enforce accepted profile boundary
740ad60  Simplify profile availability semantics
c025660  Remove legacy profile editor remnants
```

La forma física futura del Constructor sigue pendiente de análisis:

```text
- plugin
- addon
- módulo opcional
- otra integración externa que use la frontera del Engine
```

Esa decisión no condiciona el funcionamiento normal de Bitácora Engine.

---

## Gestión común de Profiles y estructura de instancia

**Decisión 2026-10-10:**

La futura capacidad opcional de gestión debe reutilizar un mismo editor
estructural para dos fuentes distintas:

```text
Template / definición de Profile
        ↓
editor estructural
        ↓
definición reutilizable

Instancia viva
        ↓
extraer esqueleto
        ↓
editor estructural
        ↓
propuesta estructural
```

El editor pertenece al productor/addon; Bitácora Engine conserva las
operaciones autoritativas y de seguridad:

```text
- validar definición
- adoptar candidato como nuevo Profile
- extraer estructura de una instancia
- verificar estructura base
- aplicar/reconciliar una propuesta sobre una instancia
- verificar estructura resultante
- recuperar PRE ante fallo de aplicación
```

El addon no debe escribir directamente términos, termmeta, páginas ni otras
representaciones internas de WordPress. Trabaja con la definición estructural;
el Engine garantiza su traducción al runtime.

La forma de empaquetado físico puede resolverse al implementar, pero la
responsabilidad funcional queda ubicada fuera del Engine normal de uso.

---

# 6. Contrato de frontera Template → Perfil

Este bloque es una decisión arquitectónica central.

## C01 — Adopción atómica de candidatos

Bitácora Engine sólo conoce y administra **Perfiles Bitácora**.

Durante una operación de adopción, lo procesado es un **candidato**, nunca un Perfil.

La adopción puede haber sido iniciada externamente por **PROMOCIÓN** o internamente por
**CREACIÓN**, pero en ambos casos la aceptación final pertenece al Engine.

Resultado binario:

```text
accepted = true
    → nace un Perfil Bitácora

accepted = false
    → no nace ningún Perfil
```

Cualquier anomalía aborta completamente la operación:

```text
- estructura inválida
- invariante incumplida
- feature desconocida/incompatible
- referencia interna inválida
- presunción de duplicado
- fallo de identidad
- fallo de persistencia
- fallo de resolución final
- cualquier inconsistencia detectada por el Engine
```

Ante `accepted=false` debe quedar garantizado:

```text
no Perfil
no UUID público
no entrada en catálogo
no “Perfil incompleto”
no “Perfil inválido”
no “Perfil pendiente”
no residuo persistente recuperable como Perfil
no cambio en la instancia actualmente en producción
```

Si internamente la implementación necesita recursos provisionales, debe limpiarlos antes de
devolver el rechazo.

El rechazo puede devolver información efímera y estructurada:

```text
accepted = false
errors:
    code
    path/context
    data
```

Bitácora Engine no presenta esos diagnósticos al operador. El productor decide cómo
explicarlos y qué correcciones sugerir.

Invariante observable:

```text
biblioteca antes de promoción fallida
=
biblioteca después de promoción fallida
```

## C02 — Identidad y presunción de duplicación

### Identidad técnica

El UUID v4 lo genera exclusivamente Bitácora Engine.

El productor no puede elegirlo, imponerlo, reservarlo ni transferirlo como identidad
canónica.

La identidad Bitácora empieza a existir únicamente cuando la promoción termina con éxito.

```text
candidato
    → no tiene identidad Bitácora

Perfil aceptado
    → UUID Bitácora
```

### Identidad humana

El `label` del candidato pasa a ser la identidad humana/nombre del Perfil aceptado.

### Duplicación, nombre y similitud

Se distinguen tres conceptos diferentes.

#### Duplicado exacto

Un candidato se considera duplicado solamente cuando su definición estructural
canónica es funcionalmente idéntica a la de un Profile existente.

```text
misma definición completa
    → REJECT
```

La comparación debe abarcar todas las propiedades pertenecientes al contrato
vigente del Profile. Si existe una diferencia estructural válida, no se trata
de un duplicado exacto.

#### Nombre de Profile

El `label` es la identidad humana del Profile y debe ser único dentro de la
biblioteca.

```text
mismo label + definición diferente
    → no es duplicado
    → exigir otro nombre antes de continuar
```

No deben existir dos Profiles distintos que resulten indistinguibles para el
operador por compartir el mismo nombre.

La normalización del `label` mantiene:

```text
trim
comparación case-insensitive
normalización consistente con persistencia
```

#### Similitud

Una definición diferente puede resultar muy parecida a un Profile existente.

Como criterio inicial de similitud se conserva la señal ya utilizada
históricamente:

```text
misma cantidad
+
mismos nombres de secciones complementarias
ignorando orden y mayúsculas/minúsculas
```

La Bitácora core section (`notas`) queda excluida de esa comparación.

Los acentos no se eliminan:

```text
Catalogos != Catálogos
```

La similitud **no provoca REJECT**.

Debe producir solamente una advertencia al productor/operador, por ejemplo:

```text
Existe un Profile similar al que se está guardando: <Nombre>.

Se sugiere verificar o comparar ambos Profiles antes de continuar.
```

La comparación ofrecida por la interfaz puede ser deliberadamente light:
su objetivo es permitir percibir la similitud, no sustituir una revisión
exhaustiva por parte del Supervisor o Administrador.

Resumen:

```text
duplicado exacto
    → REJECT

mismo nombre
    → exigir otro label

similar
    → ADVERTIR
    → operador decide

diferente
    → puede ser un nuevo Profile
```

Una Template puede seguir existiendo y exportándose con independencia del
resultado de estas comprobaciones.

## C03 — Invocación de la adopción

Bitácora Engine no participa del ciclo de construcción de Templates.

No ofrece como responsabilidad del Engine:

```text
crear borrador de Perfil
guardar Perfil incompleto
actualizar Perfil incompleto
decir qué le falta a una Template durante su edición
administrar el workflow del Constructor
```

Existen dos formas de iniciar la adopción de una definición externa:

```text
PROMOCIÓN
    iniciada por un productor

CREACIÓN
    iniciada por Bitácora Engine
    cuyo primer paso es leer un Template desde un medio externo
```

En ambos casos el Engine recibe o construye un candidato autosuficiente, aplica la misma
validación autoritativa y resuelve de forma atómica:

```text
REJECT | ACCEPT
              ↓
          Perfil Bitácora
```

PROMOCIÓN y CREACIÓN difieren en quién inicia la operación, no en la naturaleza del Perfil
resultante ni en las garantías de adopción.

El Constructor puede hacer todas las validaciones preliminares y amigables que quiera, pero
la verificación autoritativa del Engine se realiza durante la adopción.

El candidato no debe depender del estado privado del productor.

No debe exigir al Engine conocer:

```text
template_id
draft_id
paso del wizard
estado del editor
IDs internos del Constructor
```

Tampoco puede imponer:

```text
profile_id
UUID
source
enabled
available
in_use
editable
deletable
estado de dominio
```

## C04 — Esquema e invariantes del candidato

Forma conceptual:

```text
candidate
    label

    definition
        core_overrides
        sections
        classes
```

### `core_overrides`

La Bitácora core section **siempre existe** y la aporta el Engine.

`core_overrides` no crea esa sección. Sólo puede modificar los atributos que el contrato
permita.

Propiedades estructurales reservadas al Engine:

```text
slug = notas
role = core
area = main
state = active
order = 0
existencia obligatoria
```

Atributos que el Perfil puede configurar:

```text
name
singular
plural
subtitle
new_label

feature_file
feature_location
feature_comments
```

Por ejemplo, el nombre visible puede seguir siendo `Bitácora` o cambiar a `Notas`,
`Entradas` u otro texto elegido por quien construye la Template.

Los Tipos de la Bitácora core section no forman parte de `core_overrides`; viven en
`classes` y se vinculan mediante:

```text
scope = section
scope_id = notas
```

### Secciones complementarias

```text
sections = 0..N
```

Cero secciones complementarias es válido.

Si existen, cada una debe estar completa al cruzar la frontera y debe satisfacer las
invariantes contractuales. Como mínimo:

```text
name no vacío
slug técnico válido
slug no duplicado
area = main | more
```

La ausencia de `area` no produce un “Perfil incompleto”: produce `REJECT`.

### Tipos

```text
classes = 0..N
```

Cero Tipos es válido.

Si existen, cada uno debe poseer al menos:

```text
name
slug
scope = section
scope_id válido
```

`scope_id` debe apuntar a la Bitácora core section (`notas`) o a una sección
complementaria existente.

Los slugs de Tipos deben ser únicos según las reglas del Engine.

### Regla de completitud

No existe gradación de madurez dentro del Engine:

```text
cumple el contrato
    → puede continuar la promoción

no lo cumple
    → REJECT
```

---

# 7. API / frontera del Engine — ESTADO ACTUAL

La frontera gruesa Candidate → Profile ya está implementada en PHP.

Entradas principales:

```text
bitacora_adopt_profile_candidate()
    adopción autoritativa común

bitacora_promote_profile_candidate()
    entrada de PROMOCIÓN

bitacora_load_profile_template()
    lectura de Template externo soportado actualmente

bitacora_create_profile_from_template()
    entrada de CREACIÓN
    Template → candidato → misma adopción autoritativa
```

La frontera mantiene el principio:

```text
productor construye TODO
        ↓
entrega UNA definición candidata
        ↓
Engine valida/adopta TODO
        ↓
REJECT | ACCEPT
```

No existe API de edición incremental de Profile dentro del Engine.

El candidato no puede imponer identidad Bitácora ni estado operacional. La persistencia,
UUID, resolución y entrada en biblioteca pertenecen exclusivamente al Engine.

La API PHP actual constituye la frontera interna/autoritativa ya implementada. Si un futuro
Constructor externo necesita otro transporte —hook WordPress, endpoint u otra integración—,
éste deberá ser una envoltura fina sobre estas mismas operaciones y no un segundo pipeline de
adopción.

El productor no necesita conocer:

```text
CPT bitacora_profile
post_meta
post_status
rutas bundled
opciones internas
IDs WordPress
```

No diseñar una API del tipo:

```text
add_section()
delete_class()
update_feature()
```

Eso volvería a introducir el Constructor dentro del Engine.

---

# 8. Implementación del contrato Candidate → Profile — CERRADA 2026-09-29

El contrato C01–C04 dejó de ser solamente arquitectura objetivo y posee implementación
verificada.

Pipeline incorporado:

```text
ba4639f  Extract neutral profile definition validation
b8763e5  Add profile candidate validation
c30aee5  Add profile duplicate detection
b6f51d2  Normalize profile labels before duplicate check
2ff8dd5  Limit profile library to accepted profiles
b1718d4  Add authoritative profile adoption
129e0b3  Add profile promotion entrypoint
a629b11  Add profile creation from templates
17f0f73  Make profile adoption atomic
```

Funciones principales:

```text
inc/profiles.php
    bitacora_validate_profile_definition()
    bitacora_validate_profile_candidate()
    bitacora_load_profile()
    bitacora_validate_profile()

inc/profile-model.php
    bitacora_prepare_profile_definition()
    bitacora_check_profile_candidate_duplicate()
    bitacora_adopt_profile_candidate()
    bitacora_promote_profile_candidate()
    bitacora_load_profile_template()
    bitacora_create_profile_from_template()
    bitacora_get_profile_library()
    bitacora_profile_library_contains()
```

Garantías verificadas:

```text
- PROMOCIÓN y CREACIÓN convergen en la misma adopción
- candidato rechazado no produce Profile
- UUID v4 se genera sólo dentro del Engine
- duplicados se rechazan antes de la aceptación
- duplicate check se repite dentro de lock/transacción
- fallo de persistencia provoca rollback
- Profile accepted stored termina como publish
- la Library contiene sólo bundled + stored publish resolubles
- drafts/residuos legacy no pertenecen a Library
- configure/change requieren pertenencia a Library
```

Atomicidad de stored adoption:

```text
GET_LOCK('bitacora_profile_adoption', 10)
START TRANSACTION
recheck duplicados
generar UUID
insertar draft provisional
persistir meta
resolver
publicar
verificar Library
COMMIT
release lock
```

Se probó además una falla inducida de persistencia y adopción concurrente: el resultado
observable fue un único ACCEPT y el otro intento rechazado como duplicado, sin Profile
residual.

## Retiro del modelo legacy de autoría dentro del Engine

Secuencia:

```text
069acbc  Remove legacy profile creation
91f0861  Remove legacy profile editing
b4a5a4d  Enforce accepted profile boundary
740ad60  Simplify profile availability semantics
c025660  Remove legacy profile editor remnants
2291c2c  Fix accepted profile adoption flow
```

Consecuencias:

```text
- no creación administrativa de Perfil incompleto
- no edición incremental de Profile stored
- no estado complete
- no estado enabled
- no estado available como dominio
- no estado editable
- no estado incomplete / EN CONSTRUCCIÓN
```

`DISPONIBLE` puede seguir apareciendo en UI como indicación contextual de que un Profile
aceptado, válido y fuera de uso puede elegirse. No es un estado persistido del dominio.

Existe todavía en KOOPITA un registro histórico:

```text
post ID 160
Perfil_pruebas00
post_status=draft
UUID=fadfd407-b74a-46cf-a5f7-6dc7bb6ce4e7
```

Se conserva físicamente como residuo de pruebas, pero quedó verificado que:

```text
bitacora_profile_library_contains()         = false
bitacora_load_profile()                     = false
bitacora_get_stored_profile_definition()    = false
catálogo                                    = 0 coincidencias
```

No es un Perfil para el Engine.

## Pruebas end-to-end Candidate → Profile — CERRADAS 2026-09-29

La primera prueba integral de CREACIÓN descubrió una incompatibilidad entre la frontera
accepted-only y la secuencia interna de adopción:

```text
draft provisional
→ guardar UUID y definition
→ bitacora_load_profile()
→ publish
```

Desde P3A, `bitacora_load_profile()` sólo reconoce Profiles stored `publish`, por lo que
intentar resolver el registro provisional `draft` producía:

```text
accepted=false
bitacora_profile_not_resolvable
```

El rollback funcionó correctamente y no dejó Profile residual.

La resolución prematura del `draft` era redundante, porque después de publicar la adopción
ya realiza una verificación autoritativa mediante la Library accepted-only.

Se retiró ese paso en:

```text
2291c2c  Fix accepted profile adoption flow
```

La secuencia efectiva queda:

```text
validar candidato
→ adquirir lock
→ START TRANSACTION
→ revalidar duplicados
→ generar UUID
→ crear registro provisional draft
→ persistir UUID y definition
→ publish
→ resolver/verificar desde Library
→ COMMIT
→ release lock
```

### CREACIÓN desde Template

Prueba completa verificada en KOOPITA:

```text
Template temporal
→ bitacora_load_profile_template()
→ Candidate válido
→ bitacora_create_profile_from_template()
→ adopción autoritativa
→ ACCEPT
→ UUID v4
→ stored publish
→ Library
→ catálogo
→ DELETE
```

Durante la prueba:

```text
Library: 4 → 5
catálogo: 4 → 5
source=stored
valid=yes
in_use=no
deletable=yes
```

Después de DELETE:

```text
Library=4
catálogo=4
stored publish=0
Eventos continúa configurado
sin residuo del Profile temporal
```

### PROMOCIÓN directa

Se verificaron tres casos sobre `bitacora_promote_profile_candidate()`:

```text
A. candidato nuevo
   → ACCEPT
   → UUID v4
   → stored accepted

B. mismo label, ignorando case y espacios exteriores
   → REJECT
   → bitacora_profile_duplicate
   → reason=label

C. otro label, mismas secciones complementarias
   con distinto orden, case, espacios, slugs y areas
   → REJECT
   → bitacora_profile_duplicate
   → reason=sections
```

Después de ambos REJECT:

```text
Library permaneció en 5
catálogo permaneció en 5
stored publish permaneció en 1
instancia Eventos no cambió
```

Luego se eliminó el único Profile aceptado por la prueba y se restauró exactamente el
baseline:

```text
Library=4
catálogo=4
stored publish=0
Eventos continúa configurado
```

Queda verificado en ejecución:

```text
ACCEPT
    → nace exactamente un Profile

REJECT
    → no nace Profile
    → no queda residuo
    → no cambia la instancia
```

---

# 9. Biblioteca de Perfiles

La biblioteca de Bitácora Engine debe contener sólo Perfiles Bitácora.

Origen/persistencia:

```text
bundled
    viene con Bitácora
    archivo versionado

stored
    Perfil persistido en WordPress
```

`bundled` y `stored` no son especies conceptuales distintas.

Modelo objetivo:

```text
biblioteca
    = sólo Perfiles aceptados
```

No debe servir simultáneamente como depósito de Templates en edición.

La procedencia puede determinar propiedades administrativas —por ejemplo, si puede
eliminarse o no— pero no cambia el hecho de que ambos son Perfiles Bitácora.

---

# 10. Estados y terminología de Perfil — REVISIÓN CERRADA

El modelo anterior utilizó:

```text
ERROR
INCOMPLETO
HABILITADO
DISPONIBLE
EN USO
```

como si fueran estados de Perfil.

Esto queda **SUPERADO** en el modelo objetivo.

Para Bitácora Engine:

```text
Perfil
    = entidad ya aceptada
```

No existen estados técnicos `INCOMPLETO` o `ERROR` de un Perfil.

`EN USO` pertenece a la instancia, no al Perfil.

`DISPONIBLE` o `HABILITADO` pueden, si alguna interfaz los necesita, funcionar como
información contextual/affordance para el operador, pero no constituyen estados de ciclo de
vida del Perfil.

Ejemplo:

```text
instalación con instancia
    → ningún Perfil puede usarse ahora

instalación neutra
    → los Perfiles de la biblioteca pueden ofrecer acción “Usar”
```

El Perfil no cambió de estado; cambió el contexto de la instalación.

---

# 11. Perfil → Instancia

Bitácora Engine puede poner un Perfil en producción sólo cuando la instalación está neutra:

```text
SIN INSTANCIA
    ↓
seleccionar Perfil
    ↓
USAR PERFIL
    ↓
crear instancia
    ↓
EN USO
```

El Perfil origina la estructura inicial, pero luego la instancia tiene vida propia.

El Perfil de origen no actúa como controlador vivo del runtime.

Una instancia puede evolucionar sin modificar su Perfil de origen.

---

# 12. Cerrar Bitácora

Cerrar la instancia y eliminar un Perfil son operaciones completamente distintas.

## Cerrar instancia

Solicitud del Supervisor:

```text
Instancia "Eventos" EN USO
    ↓
Cerrar Bitácora
    ↓
eliminar contenido/runtime de la instancia
    ↓
instalación neutra
```

El Perfil `Eventos` permanece en la biblioteca.

Terminología preferida en UI:

```text
Cerrar Bitácora
Cerrar Bitácora "Eventos"
```

Evitar:

```text
Eliminar perfil en uso
Cambiar perfil
```

cuando en realidad se está destruyendo la instancia.

Sólo después del cierre puede ofrecerse nuevamente `Usar este perfil`.

---


## Respaldo Documental de la instancia — CERRADO 2026-10-01

Antes de destruir una instancia mediante `Cerrar Bitácora`, el Supervisor puede generar un
**RESPALDO DOCUMENTAL** de la instancia activa.

Propósito:

```text
conservar una representación documental completa de la instancia
para archivo, auditoría, documentación o análisis posterior
```

No es:

```text
- un backup restaurable
- un paquete importable
- una Template
- una exportación de Profile
- un mecanismo de reconstrucción automática de Bitácora
```

La interfaz lo expresa explícitamente:

> Este respaldo no genera un paquete reutilizable directamente por Bitácora.

El respaldo no modifica la instancia.

### Frontera documental

La frontera coincide deliberadamente con la frontera destructiva de `Cerrar Bitácora`.

Incluye:

```text
Profile de origen
estructura materializada
secciones
Tipos
páginas de sección
bitacora_item
comentarios
todos los attachments de la instalación
archivos físicos disponibles
metadatos
hashes SHA-256 de archivos físicos
```

Dado que una instalación mantiene cero o una única instancia y `Cerrar Bitácora` elimina todos
los attachments, RESPALDO DOCUMENTAL conserva también **todos los attachments**, no sólo aquellos
referenciados directamente por una entrada.

### Formato v1

El paquete generado es un ZIP:

```text
bitacora-documental-<perfil>-AAAAMMDD-HHMMSS.zip

LEEME.txt
manifest.json
perfil.json
estructura.json
entradas.json
comentarios.json
adjuntos.json
archivos/
```

Si existe un attachment registrado cuyo archivo físico ya no está disponible, el respaldo puede
generarse igualmente y documenta la anomalía mediante `ADVERTENCIAS.txt`.

### Identidad temporal de la instancia

Se incorporó estado runtime mínimo:

```text
bitacora_configured_profile
    identidad del Profile que originó la instancia activa

bitacora_configured_at
    timestamp Unix UTC de puesta en uso de la instancia
```

`bitacora_configured_at`:

- se registra sólo después de materializar correctamente la instancia;
- no pertenece al Profile;
- no se infiere silenciosamente para instancias históricas;
- se elimina únicamente durante un cierre exitoso;
- se utiliza en la advertencia de `Cerrar Bitácora`;
- se incorpora al Respaldo Documental.

La pantalla de cierre muestra ahora:

```text
LEÉ ATENTAMENTE ANTES DE CONTINUAR

Período de actividad
Desde: <configured_at>
Hasta: <momento de render del cierre>
```

### Implementación

Commits principales:

```text
defa588  Record Bitacora instance start time
4faf81e  Show instance period in close warning
bea2d7b  Add documental backup collector
b5924a6  Add documental backup package
2d6032a  Add documental backup download
```

La implementación está separada en dos responsabilidades:

```text
collect
    fotografía lógica de la instancia

package
    serialización + ZIP + archivos físicos
```

El botón `RESPALDO DOCUMENTAL ?` está activo sólo sobre el Profile de la instancia `EN USO`,
genera el ZIP mediante `admin-post.php`, valida capability, nonce e identidad de la instancia,
entrega el archivo al navegador y elimina el temporal del servidor.

### Pruebas funcionales

Primera prueba con instancia vacía:

```text
Profile: Eventos
secciones: 9
Tipos: 44
páginas: 9
entradas: 0
comentarios: 0
attachments: 0

ZIP:
LEEME.txt
manifest.json
perfil.json
estructura.json
entradas.json
comentarios.json
adjuntos.json
```

Prueba posterior con contenido real:

```text
1 bitacora_item
2 comentarios
1 attachment físico
```

El ZIP descargado contenía correctamente:

```text
archivos/2026/10/ca.txt
adjuntos.json
comentarios.json
entradas.json
estructura.json
LEEME.txt
manifest.json
perfil.json
```

`manifest.json` informó:

```text
items=1
comments=2
attachments=1
```

Con esa prueba queda validado el recorrido completo:

```text
UI
→ handler
→ collector
→ package ZIP
→ descarga
→ archivo físico incluido
```

---

# 13. Prueba UI del cierre destructivo — CERRADA 2026-09-28

Se probó en KOOPITA la pantalla actualmente implementada como cambio de Perfil.

Datos detectados correctamente para la instancia Eventos:

```text
5 entradas
0 comentarios
2 archivos adjuntos
```

La interfaz advirtió además que:

```text
- se eliminan secciones, Tipos y páginas
- los archivos adjuntos se eliminan físicamente
- la operación es irreversible desde Bitácora
```

`Cancelar` fue probado y:

```text
Cancelar = PASS
contenido de Eventos intacto
```

La prueba funcional de render/conteos/guard queda cerrada.

La semántica actual de la pantalla:

```text
Confirmar cambio de perfil
Eliminar contenido y usar este perfil
```

queda **SUPERADA**.

El trabajo técnico del warning y sus conteos debe reutilizarse para:

```text
Cerrar Bitácora "Eventos"
```

Texto conceptual acordado:

```text
La operación pondrá fin a la instancia "Eventos" de Bitácora,
actualmente en uso.

Si continúa la operación se eliminarán definitivamente:

- 5 entradas
- 0 comentarios
- 2 archivos adjuntos
- todas las secciones, Tipos y páginas de la instancia "Eventos"
- los archivos físicos correspondientes a los adjuntos

Esta operación no se puede deshacer.

[Cerrar Bitácora "Eventos" y eliminar su contenido] [Cancelar]
```

---

# 14. Pantalla de Perfiles — deuda de interfaz detectada

La captura de 2026-09-28 mostró la UI actual:

```text
Perfiles disponibles

Centro Educativo   DISPONIBLE
Construcción       DISPONIBLE
Depósito           DISPONIBLE
Eventos            EN USO
Perfil_pruebas00   DISPONIBLE
```

También ofrece `Usar este perfil` mientras existe la instancia Eventos.

Esto responde al modelo anterior.

Modelo objetivo:

```text
Instancia actual
---------------
Eventos
EN USO
Perfil de origen: Eventos

[Cerrar Bitácora "Eventos"]


Perfiles
--------
Los perfiles definen la estructura inicial de una Bitácora.

Centro Educativo
Construcción
Depósito
Eventos
...
```

Mientras exista una instancia:

```text
- no ofrecer "Usar este perfil"
- no convertir "EN USO" en estado del Perfil
- si interesa, indicar secundariamente qué Perfil originó la instancia
```

Cuando la instalación queda neutra:

```text
- ofrecer "Usar este perfil" para los Perfiles de la biblioteca
```

La creación/edición de Templates no debería permanecer mezclada en esta pantalla cuando se
complete la separación con el Constructor.

---

# 15. Modificación estructural de instancia y PAUSA breve

**Estado al 2026-10-10: CONTRATO FUNCIONAL DECIDIDO / PENDIENTE DE IMPLEMENTACIÓN.**

La modificación estructural de una instancia viva no se realiza normalmente
mientras la instancia está detenida.

La instancia permanece **EN USO** durante la preparación y edición de la nueva
estructura. `EN PAUSA` queda reducido a una ventana crítica breve destinada
exclusivamente a aplicar una propuesta ya terminada y validada.

Principio:

> **PAUSA no es un modo de edición. Es la interrupción breve y exclusiva del
> servicio necesaria para aplicar de forma segura una modificación estructural
> previamente preparada.**

Flujo objetivo:

```text
INSTANCIA EN USO
        ↓
1. EXTRAER PRE
   fotografía del esqueleto estructural vigente
        ↓
2. EDITAR COPIA
   la instancia continúa funcionando normalmente
        ↓
3. VALIDAR PROPUESTA
   validar definición canónica
   comparar nuevamente estructura viva contra PRE
        ↓
4. PAUSA BREVE
   bloquear uso normal de la instancia
   adquirir exclusividad de mantenimiento
        ↓
5. APLICAR / RECONCILIAR
        ↓
6. EXTRAER Y VALIDAR POST
        ↓
7. REANUDAR
        ↓
INSTANCIA EN USO
```

## 15.1 Esqueleto de instancia

Bitácora debe poder reconstruir desde una instancia viva una definición
estructural equivalente a la definición canónica de Profile correspondiente
a su estado actual.

La extracción incluye exclusivamente propiedades pertenecientes al contrato
estructural vigente:

```text
core
sections
classes
```

No forman parte del esqueleto:

```text
bitacora_item
contenido de entradas
comentarios
adjuntos
imágenes
videos
biblioteca de medios
autores
fechas de contenido
IDs WordPress
term_id
post_id
attachment_id
configured_at
estado active|paused
datos operativos de la instancia
```

Si una instancia acaba de ser creada y todavía no sufrió modificaciones
estructurales:

```text
Profile P
    ↓
materializar
    ↓
extraer esqueleto E

E ≡ definición estructural de P
```

Después de modificaciones estructurales:

```text
E ≡ definición original
    + modificaciones acumuladas de la instancia
```

No es necesario reproducir el historial de cambios para reconstruir el estado
actual.

## 15.2 Modificaciones permitidas

La edición trabaja sobre una copia del esqueleto, no directamente sobre la
estructura viva.

Secciones complementarias:

```text
- alta
- ocultar / mostrar
- modificar nombre y etiquetas
- modificar orden
- mover main ↔ more
- activar / desactivar features
```

Tipos:

```text
- alta
- ocultar / mostrar
- modificar nombre
- modificar orden
```

Bitácora core section:

```text
- modificar etiquetas permitidas
- activar / desactivar features
```

No se modifica su identidad estructural:

```text
slug=notas
role=core
area=main
state=active
order=0
existencia obligatoria
```

No se permite eliminar estructura histórica de una instancia viva.

Ocultar sustituye la necesidad de eliminar:

```text
VISIBLE
OCULTO
```

Ocultar una Sección o Tipo:

```text
- lo retira del uso normal futuro
- conserva identidad y relaciones
- conserva todo contenido histórico asociado
- no modifica el estado interno de otros objetos
- puede revertirse mediante MOSTRAR
```

Una Sección oculta no aparece en navegación ni admite nuevas entradas, pero su
historia sigue resolviendo búsquedas, relaciones y referencias válidas.

Un Tipo oculto deja de ofrecerse para nuevas entradas pero continúa
identificando correctamente las entradas históricas que lo utilizaron.

PAUSA y edición estructural no pueden eliminar, agregar ni modificar contenido
de usuario. El agregado permitido es **estructura**, no contenido.

## 15.3 Features

Features vigentes:

```text
file
location
comments
```

Su modificación afecta el uso futuro.

```text
OFF → ON
    habilita la capacidad para operaciones futuras

ON → OFF
    deja de ofrecerla para nuevas operaciones
    conserva todos los datos históricos existentes
```

No se introducen combinaciones especiales ni dependencias preventivas entre
features.

## 15.4 Identidad técnica

Para objetos existentes:

```text
slug
scope
scope_id
identidades WordPress
```

permanecen estables.

Los nombres y etiquetas visibles pueden cambiar sin recrear el objeto.

Las altas reciben una identidad técnica nueva generada/determinada por
Bitácora. El operador no necesita editar slugs manualmente.

## 15.5 Validación previa y huella PRE

Antes de aplicar una propuesta deben cumplirse dos comprobaciones distintas:

```text
A. la PROPUESTA es una definición estructural válida

B. la estructura viva continúa siendo equivalente a PRE
```

Para B puede utilizarse una huella determinista de la definición normalizada,
por ejemplo SHA-256.

Las operaciones normales de contenido ocurridas durante la preparación
—nuevas entradas, comentarios o adjuntos— no alteran la huella estructural y
no invalidan la propuesta.

Si la estructura cambió desde PRE:

```text
NO APLICAR
volver a extraer
revisar/rebasar la propuesta
```

## 15.6 PAUSA como zona crítica

Sólo el momento de aplicación requiere retirar temporalmente la instancia del
servicio.

Durante esa ventana:

```text
- no hay navegación normal
- no hay consultas
- no hay búsquedas
- no hay lectura de entradas
- no hay creación o edición
- no hay comentarios
- no deben operar enlaces compartibles de la instancia
- no debe existir acceso ordinario a datos/contenidos de la instancia
```

El bloqueo debe realizarse mediante un guard central y no mediante una matriz
de excepciones pantalla por pantalla.

El acceso directo a archivos físicos mediante URLs públicas requiere una
solución técnica específica; invalidar sesiones o bloquear WordPress no basta
por sí solo si el webserver sirve el archivo directamente.

## 15.7 Autoridad y exclusividad

Se prevé un rol **Administrador Bitácora**:

```text
Administrador Bitácora
    = Supervisor Bitácora
    + autoridad de mantenimiento estructural
```

El rol nativo `administrator` de WordPress conserva su autoridad habitual y
también debe poseer las capabilities gestionadas por Bitácora.

Al comenzar la ventana de PAUSA se registra el usuario que adquiere el
mantenimiento.

Durante esa ventana no deben existir dos operadores concurrentes sobre la
estructura.

La pérdida de conexión o de sesión no altera el estado de la instancia. La
recuperación normal de credenciales WordPress permite al mismo usuario volver
a autenticarse.

## 15.8 Aplicación y verificación

La aplicación no vuelve a sembrar la instancia desde cero.

Debe **reconciliar** la estructura existente con la PROPUESTA:

```text
objeto existente
    → conservar identidad técnica
    → modificar solamente propiedades permitidas

objeto nuevo
    → materializar agregado

objeto histórico ausente de la propuesta
    → no eliminar
```

El éxito requiere:

```text
POST extraído
    ≡
PROPUESTA validada
```

Sólo entonces:

```text
PAUSED → ACTIVE
```

## 15.9 Fallo de aplicación y recuperación PRE

Ante un error:

```text
1. registrar error y estado alcanzado
2. reconciliar nuevamente hacia PRE
3. extraer estructura resultante
4. validar
5. comprobar resultado ≡ PRE
```

Si la recuperación resulta correcta:

```text
volver a ACTIVE
corregir PROPUESTA mientras Bitácora funciona
validar nuevamente
intentar otra aplicación
```

La recuperación de PRE conserva las identidades técnicas existentes.

Puede eliminar exclusivamente artefactos estructurales que hayan sido creados
por el intento fallido y que nunca llegaron a quedar disponibles para uso
normal.

Sólo si falla también la recuperación hacia PRE:

```text
Bitácora permanece PAUSED
el operador mantiene exclusividad
se requiere diagnóstico/recuperación
```

## 15.10 Registro documental de mantenimiento

El registro tiene finalidad exclusivamente forense/documental.

No es:

```text
- undo
- sistema de versiones
- mecanismo de ejecución
- rollback lógico de usuario
```

Puede conservarse en filesystem como expediente pequeño asociado a la
instancia.

Contenido mínimo:

```text
PRE.json
    estructura inicial

PROPUESTA.json
    estructura aprobada para aplicar

POST.json
    estructura obtenida

RESUMEN / diferencias
    cambios PRE → PROPUESTA

errores de aplicación
    cuando existan
```

Los archivos pueden incorporar hashes SHA-256 para detectar alteraciones
accidentales.

El expediente permanece asociado documentalmente a la instancia y puede
incorporarse al Respaldo Documental final.

## 15.11 Editor común y addon

La edición estructural no requiere un segundo editor específico para PAUSA.

El futuro addon de gestión puede utilizar el mismo editor para:

```text
Template / Profile
        y
esqueleto extraído de instancia viva
```

La diferencia está en el destino.

```text
definición editada
    ├── adoptar como NUEVO PROFILE
    │       → nuevo UUID
    │
    └── aplicar a ESTA INSTANCIA
            → conserva configured_profile
            → conserva identidad histórica del Profile de origen
```

Modificar una instancia no modifica el Profile que la originó.

Si el esqueleto de una instancia se guarda como definición reutilizable, se
crea siempre un **nuevo Profile**, con nueva identidad generada por el Engine.

No se introduce un concepto obligatorio de Profile sucesor, padre/hijo o
versionado de Profiles.

# 16. Organización `main|more`

`main|more` describe organización visual, no jerarquía semántica.

Implementación actual:

```text
main
    dashboard principal

more
    segundo dashboard / expansión de espacio
```

La Bitácora core section permanece siempre en `main`.

Para las Secciones complementarias puede modificarse:

```text
area = main | more
order
```

Por ahora se mantienen solamente estos dos dashboards.

No se implementan niveles anidados de `more` ni una paginación generalizada
preventiva. Si un caso real necesitara más espacio, podrá evaluarse entonces
un tercer dashboard.

La cantidad práctica de Secciones que hoy caben cómodamente en los dos
dashboards es una restricción de presentación, no una invariante del contrato
estructural.

`area` tampoco debe reutilizarse para expresar permisos o privacidad.

La privacidad pertenece a las entradas y es una dimensión independiente de la
ubicación visual de una Sección.

Del mismo modo, una futura forma alternativa de presentar una Sección
—por ejemplo una vista de un artefacto externo— no debe obligar a redefinir
`main|more`.

La modificación de `area` y `order` se prepara sobre el esqueleto mientras la
instancia permanece EN USO y se materializa durante la breve zona crítica de
aplicación.

# 17. Identidad técnica y etiqueta visible

Principio cerrado:

```text
identidad técnica != etiqueta visible
```

Para una sección runtime:

```text
slug / scope_id / key
    identidad técnica estable

name
    etiqueta visible mutable
```

Para un Tipo:

```text
slug/key
    identidad técnica

name
    etiqueta visible
```

La prueba histórica de Centro Educativo mostró que el editor actual puede tratar un cambio
de nombre como cambio de identidad y recrear estructura. Esa conducta debe corregirse.

No se asume que sea necesario inventar un `termmeta label`; WordPress ya distingue
`term_id`, `slug` y `name`.

La separación entre identidad técnica y nombre visible es una regla general, no una
propiedad exclusiva de la Bitácora core section.

Para cualquier sección, la identidad técnica debe permanecer estable mientras su `name`
puede modificarse según las reglas de edición de la instancia.

Para la Bitácora core section existe además una invariante reservada:

```text
slug = notas
    identidad técnica reservada

name = Bitácora / Notas / Entradas / ...
    presentación configurable
```

---

# 18. Creación, promoción, exportación y duplicación

## Regla de dominio

No existen Perfiles Bitácora fuera de Bitácora Engine.

Por lo tanto, la operación **importar Perfil** no forma parte del modelo.

Fuera del Engine existen **Templates**.

## Templates

Una Template pertenece a un productor o a un medio externo.

Puede:

```text
- editarse
- duplicarse
- persistirse externamente
- exportarse
```

incluso si Bitácora Engine rechaza su adopción por presunción de duplicación.

## PROMOCIÓN

La promoción se inicia en el productor:

```text
productor
    ↓
Template / candidato
    ↓
PROMOCIÓN
    ↓
Bitácora Engine
    ↓
REJECT | ACCEPT → Perfil
```

## CREACIÓN

La creación se inicia desde Bitácora Engine.

Su primer paso es siempre leer un Template desde un medio externo, por ejemplo filesystem u
otro mecanismo futuro:

```text
Bitácora Engine
    ↓
Crear Perfil
    ↓
leer Template externo
    ↓
candidato
    ↓
validar / adoptar
    ↓
REJECT | ACCEPT → Perfil
```

PROMOCIÓN y CREACIÓN pueden compartir el mismo pipeline interno de validación y adopción.
La diferencia está en el origen de la invocación.

## EXPORTACIÓN

Exportar un Perfil significa transformarlo en una Template y persistir esa Template en un
medio externo:

```text
Perfil
    ↓
EXPORTACIÓN
    ↓
Template
    ↓
medio externo
```

La identidad Bitácora del Perfil no convierte a la Template exportada en un Perfil externo.
Si esa Template vuelve a ingresar al Engine, debe atravesar nuevamente una operación de
adopción y, si es aceptada como nuevo Perfil, recibirá identidad Bitácora asignada por el
Engine.

Una restauración exacta, si alguna vez se implementa, será otra operación y no debe
confundirse con creación, promoción o exportación.

---

## Extracción estructural desde una instancia viva

Una instancia viva puede actuar como fuente de una definición estructural
reutilizable.

```text
instancia viva
    ↓
extraer esqueleto actual
    ↓
definición canónica
    ↓
opcionalmente editar
    ↓
Template / candidato
    ↓
adopción
    ↓
NUEVO PROFILE
```

La extracción refleja la estructura vigente de la instancia, incluidas las
modificaciones estructurales acumuladas, pero excluye completamente contenido
y datos de usuario.

Aplicar esa definición nuevamente a la misma instancia:

```text
- no crea Profile
- no genera UUID
- conserva configured_profile
```

Convertirla en una definición reutilizable y adoptarla:

```text
- crea un Profile nuevo
- genera un UUID nuevo
- no modifica el Profile de origen
```

El nuevo Profile puede resultar una mejora práctica derivada de experiencia
real de uso, pero el Engine no necesita modelar genealogía ni herencia entre
Profiles.

---

# 19. Perfiles bundled reales — ESTADO ACTUAL

## Construcción

```text
UUID: 0f19bdfc-5f6d-4719-b898-e5f18cb1f926
runtime esperado:
    6 secciones
    33 Tipos
```

## Depósito

```text
UUID: 2a3822df-187c-4c2b-8993-1b29324f1074
runtime esperado:
    4 secciones
    16 Tipos
```

## Eventos

```text
UUID: c5a6cdce-8854-4491-bce5-ecc678fe90d9
runtime esperado:
    9 secciones
    44 Tipos

mantener literalmente:
    Operación Del Evento

Programación:
    area=main
```

## Centro Educativo

```text
UUID: 46532279-c48f-4e17-aae5-080456c68785
runtime esperado:
    6 secciones
    30 Tipos
```

Secciones:

```text
Bitácora
Coordinación Diaria
Actividades Especiales
Reuniones
Comunicaciones
Mantenimiento
```

Bocetos anteriores con `Planificación`, `Actividades`, `Recursos`, etc. están SUPERADOS.

---

# 20. Estado técnico operativo — 2026-10-01

## Git

Estado runtime/Profile más reciente verificado:

```text
2d6032a  Add documental backup download
b5924a6  Add documental backup package
bea2d7b  Add documental backup collector
4faf81e  Show instance period in close warning
defa588  Record Bitacora instance start time
cd8197a  Separate profile use from Bitacora closing
```

Desde `cd8197a`, `Usar Perfil` y `Cerrar Bitácora` son operaciones separadas:

```text
instalación neutra
    → Usar Perfil
    → instancia EN USO

instancia EN USO
    → Cerrar Bitácora
    → instalación neutra
```

`Usar Perfil` no destruye ni sustituye una instancia activa.


```text
repo:
/home/dosmilun/repos/bitacora

branch:
0.3.0-dev

HEAD verificado:
2291c2c Fix accepted profile adoption flow

worktree:
limpio
```

El warning/guard destructivo probado en UI quedó consolidado en el commit `e20211c`.

Permanece conectado al flujo antiguo de cambio de Perfil; su traslado a `Cerrar Bitácora`
forma parte del próximo refactor de interfaz.

## KOOPITA

```text
WP:
/home/dosmilun/koopita.angiru.uy

instancia actual:
Eventos

Perfil de origen:
Eventos
c5a6cdce-8854-4491-bce5-ecc678fe90d9

runtime:
items=5
comments=0
attachments=2
sections=9
classes=44
```

El botón `Cancelar` de la confirmación destructiva fue probado el 2026-09-28 y conserva la
instancia sin cambios.

Catálogo observado:

```text
Centro Educativo   bundled
Construcción       bundled
Depósito           bundled
Eventos            bundled
Perfil_pruebas00   stored
```

La UI todavía presenta `DISPONIBLE/EN USO` y acciones del modelo anterior.

## BITA

```text
/home/bita020/bita.dreamhosters.com
```

Ya se utilizó para probar materialización sucesiva de los cuatro bundled.

Debe reutilizarse para la regresión final de 0.3.0.

---

# 21. Evidencia destructiva ya verificada

En KOOPITA se probó realmente el flujo antiguo:

```text
Eventos
5 items
2 attachments
→ Construcción
```

Resultado:

```text
Construcción
6 secciones
33 Tipos
0 items
0 attachments
```

Los archivos físicos desaparecieron.

Backup previo:

```text
/home/dosmilun/tmp/salidas/
bitacora-before-eventos-to-construccion_20260912-154039/
```

con:

```text
koopita-before-switch.sql
uploads.tar.gz
```

Luego se restauraron DB + uploads y KOOPITA volvió a Eventos.

Conclusión:

> **Una restauración completa requiere DB + uploads. SQL solo no alcanza.**

Esto es evidencia técnica, no una promesa de undo de Bitácora.

---

# 22. Decisiones cerradas que no deben reabrirse por accidente

## Producto

```text
- nombre: Bitácora
- rebranding cerrado
- theme autónomo
- modelo genérico:
    bitacora_item
    bitacora_section
    bitacora_class
```

## Features

```text
feature_file
feature_location
feature_comments
```

`feature_thumbnail` eliminado.

## Arquitectura

```text
- Bitácora Engine = software/núcleo
- Bitácora core section = sección estructural obligatoria
- Perfil Bitácora = sólo entidad ya aceptada por Engine
- Instancia = Bitácora concreta en producción
- Template = objeto de autoría externo/opcional
- Constructor de Templates no es requisito para operar Bitácora
```

## Frontera

```text
- adopción atómica
- PROMOCIÓN iniciada por productor
- CREACIÓN iniciada por Engine desde Template externo
- ACCEPT → nace Perfil
- REJECT → no existe Perfil
- UUID asignado exclusivamente por Engine
- label aporta identidad humana
- duplicado exacto sólo por equivalencia de definición estructural canónica
- label de Profile único; una colisión de nombre exige elegir otro
- similitud estructural produce advertencia, no rechazo
- Engine no administra candidatos rechazados
- Engine no administra “Perfiles incompletos”
```

## Runtime

```text
- una instalación: cero o una instancia
- EN USO / EN PAUSA pertenecen a instancia
- Perfil de origen no controla dinámicamente la instancia
- Cerrar Bitácora y Usar Perfil son operaciones distintas
- modificaciones en PAUSA son aditivas/conservativas
- historia y referencias existentes no se destruyen
```

## `was_used`

La antigua regla:

```text
Perfil usado alguna vez
→ congelado para siempre
```

fue eliminada y no debe reintroducirse.

---

# 23. Ideas superadas

## “Perfil esqueleto” como entidad incompleta dentro del Engine

**SUPERADO.**

El esqueleto en construcción pertenece al mundo Template/Constructor.

## Estados `ERROR / INCOMPLETO / HABILITADO` como ciclo de vida de Perfil

**SUPERADO para el modelo objetivo del Engine.**

Son posibles estados de una Template en el Constructor, no de un Perfil Bitácora.

## `EN USO` como estado del Perfil

**SUPERADO.**

Pertenece a la instancia.

## `DISPONIBLE` como estado técnico del Perfil

**SUPERADO.**

Puede ser una indicación contextual de UI, no un estado de dominio.

## Perfil de origen como controlador vivo del runtime

**SUPERADO.**

Origina la instancia; después pueden evolucionar independientemente.

## “Cambiar Perfil” como operación primaria

**SUPERADO como UX objetivo.**

Usar:

```text
Cerrar Bitácora
+
Usar Perfil
```

## Rollback automático A→B

**REBAJADO.**

Si A fue cerrada explícitamente y falla la creación de B, la instalación queda neutra y se
puede intentar nuevamente.

## Perfil sucesor obligatorio para evolucionar una instancia

**REBAJADO / no requerido.**

Sólo reconsiderarlo si aparece una necesidad real de convertir una estructura runtime
evolucionada nuevamente en Perfil reutilizable.

## Perfil externo / UUID transferido

**SUPERADO.**

No existe un Perfil Bitácora fuera del Engine. Una Template externa no transporta identidad
Bitácora canónica hacia una nueva adopción. Todo Perfil nuevo recibe UUID del Engine que lo
adopta.

---

# 24. Registro único de pendientes

| ID | Estado | Asunto |
|---|---|---|
| P01 | CERRADO | C01–C04 formalizados y reflejados en el pipeline Candidate → Profile |
| P02 | DECIDIDO / PENDIENTE DE IMPLEMENTACIÓN | Sustituir presunción de duplicado por: duplicado exacto; label único; similitud como advertencia |
| P03 | CERRADO | Entrada PHP de PROMOCIÓN implementada sobre adopción autoritativa común |
| P04 | CERRADO | Adopción stored protegida por lock + transacción + rollback; REJECT sin Profile residual |
| P05 | CERRADO | Library limitada a Profiles aceptados; autoría legacy retirada del Engine |
| P06 | DECIDIDO / PENDIENTE DE IMPLEMENTACIÓN | Gestión opcional mediante addon/productor con editor común de Templates/Profiles y esqueleto de instancia |
| P07 | CERRADO | `Cerrar Bitácora` separado de `Usar Perfil`; commit `cd8197a` |
| P08 | CERRADO | Warning/guard destructivo reutilizado y ampliado con período real de instancia |
| P09 | CERRADO | `EN USO`/`DISPONIBLE` retirados como estados técnicos de Profile; `DISPONIBLE` queda sólo como UI contextual |
| P10 | CERRADO | `Usar este perfil` sólo se ofrece y opera desde instalación neutra |
| P11 | DECIDIDO / IMPLEMENTAR | Separar identidad técnica y etiqueta visible en editor/runtime |
| P12 | DECIDIDO / PENDIENTE DE IMPLEMENTACIÓN | Modificación estructural preparada en ACTIVE; PAUSA breve sólo para aplicar/verificar/recuperar |
| P13 | DECIDIDO / IMPLEMENTAR | Ocultar Sección preserva historia, excluye navegación normal e impide nuevas entradas; mantiene referencias y búsqueda multisección |
| P14 | CERRADO | Ocultar Tipo lo retira del uso futuro sin alterar identidad, relaciones ni historia; MOSTRAR revierte la decisión |
| P15 | DECIDIDO / IMPLEMENTAR | Exportar Profile como Template externo; no existe “importar Perfil” |
| P16 | CERRADO | CREACIÓN desde Template externo implementada reutilizando la adopción autoritativa |
| P17 | CERRADO | No se requiere Perfil sucesor: aplicar a instancia conserva origen; reutilizar esqueleto crea un Profile nuevo |
| P18 | DECIDIDO / PROBAR | Primera puesta en uso desde instalación neutra |
| P19 | DECIDIDO / PROBAR | Usuario `use_bitacora_profiles=yes`, `manage_bitacora_profiles=no` |
| P20 | DECIDIDO / PROBAR | Regresión final/fresh 0.3.0 en BITA |
| P21 | CERRADO | `docs/CONTEXTO-MAESTRO.md` actualizado y versionado al estado post-refactor de Profiles |
| P22 | DECIDIDO | Release 0.3.0 después de la regresión final |
| P23 | OBSERVACIÓN | Búsqueda multisección |
| P24 | OBSERVACIÓN | Shortcuts/referencias entre entradas |
| P25 | OBSERVACIÓN | Diferencia visual menor frontend/wp-admin |
| P26 | CERRADO | Ciclo completo Template → CREACIÓN → adopción → Library → catálogo → DELETE verificado en KOOPITA |
| P27 | CERRADO | Pipeline PROMOCIÓN verificado; los casos históricos de duplicación deberán actualizarse al nuevo criterio exacto |
| P28 | CERRADO | RESPALDO DOCUMENTAL v1: collector + ZIP + descarga UI + prueba con entrada, comentarios y attachment físico |

---

# 25. Pruebas pendientes para cerrar 0.3.0

La antigua regresión integral de `Perfil_pruebas00` debe reinterpretarse a la luz de la
separación Template/Perfil.

No conviene invertir más trabajo en consolidar una UX de “Perfil incompleto” dentro del
Engine si esa UI está destinada a convertirse en Constructor.

Prioridades actuales:

1. implementar el nuevo criterio de duplicado exacto, label único y similitud como advertencia;
2. implementar extracción `instancia → definición canónica`;
3. verificar round-trip sobre KOOPITA:
   `Profile P → instancia sin cambios → esqueleto E`, con `E ≡ P`;
4. definir/comprobar comparación determinista y huella estructural PRE;
5. implementar reconciliación segura `PRE → PROPUESTA → POST`, incluida recuperación hacia PRE;
6. incorporar rol/capability de Administrador Bitácora y guard operativo de la PAUSA breve;
7. resolver bloqueo efectivo de enlaces compartibles y acceso directo a medios durante PAUSA;
8. construir el addon/editor estructural común cuando las primitivas del Engine estén cerradas;
9. ejecutar regresión bundled, BITA fresh, idempotencia y cierre de 0.3.0.

# 26. Convenciones operativas de terminal

Repo/hosts:

```text
KOOPITA repo:
/home/dosmilun/repos/bitacora

KOOPITA WP:
/home/dosmilun/koopita.angiru.uy

BITA WP:
/home/bita020/bita.dreamhosters.com
```

Reglas:

```text
- todo bloque empieza con cd explícito
- evitar set -e / set -u / set -euo pipefail en shells interactivas
- evitar exit y return top-level
- usar git --no-pager
- KOOPITA no tiene rg: usar grep/sed/awk/find
```

`cacli`:

```text
- se ejecuta localmente en koopa
- ejecuta el bloque remoto por ssh koopita 'bash -s'
- interpreta la última línea no vacía de stdout como ruta remota
- esa ruta debe estar bajo /home/dosmilun/tmp/salidas/
- nada puede imprimirse después de la ruta final
```

Bloques clásicos pueden terminar mostrando y haciendo `cat "$OUT"`.

No confundir bloques clásicos con bloques `cacli`.

---

# 27. Punto de reentrada recomendado

Estado conceptual al 2026-10-10:

```text
La modificación estructural de una instancia ya no se diseña como
una sesión larga de edición dentro de EN PAUSA.

ACTIVE
    ↓
extraer PRE
    ↓
editar PROPUESTA
    ↓
validar
    ↓
verificar instancia viva == PRE
    ↓
PAUSA breve
    ↓
aplicar / reconciliar
    ↓
verificar POST
    ↓
ACTIVE
```

Ante fallo de aplicación:

```text
registrar
    ↓
reconciliar hacia PRE
    ↓
si resultado == PRE
    → ACTIVE
    → corregir propuesta con la instancia funcionando

si también falla recuperación
    → permanecer PAUSED
    → diagnóstico
```

El editor estructural pertenece al futuro addon/productor y puede operar sobre:

```text
- Template / definición de Profile
- esqueleto extraído de instancia viva
```

Bitácora Engine conserva:

```text
- definición canónica
- validación
- adopción
- extracción de estructura
- comparación/huella
- reconciliación
- PAUSA técnica
- verificación
- recuperación PRE
```

Próximo paso técnico recomendado:

```text
1. actualizar implementación de duplicación:
       exacto    → REJECT
       label     → exigir nombre distinto
       similar   → warning

2. implementar:
       bitacora_get_instance_profile_definition()
   o nombre equivalente

3. probar:
       Profile Eventos
           ↓
       instancia KOOPITA
           ↓
       extracción
           ↓
       equivalencia funcional con la definición de origen
```

No tocar todavía la interfaz del addon ni implementar una PAUSA completa antes
de demostrar esta correspondencia.
