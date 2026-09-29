# Bitácora — Contexto Maestro de desarrollo

**Revisión documental:** 2026-09-29
**Estado técnico de referencia:** 2026-09-29
**Rama:** `0.3.0-dev`  
**Repo canónico:** `/home/dosmilun/repos/bitacora`  
**HEAD verificado:** `2291c2c Fix accepted profile adoption flow`

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

### Presunciones de duplicación

La adopción se rechaza si se cumple **cualquiera** de estas premisas:

```text
1. ya existe en la biblioteca un Perfil con el mismo label

2. ya existe en la biblioteca un Perfil con igual cantidad
   y los mismos nombres de secciones
```

En ese caso Bitácora presume que el candidato es un duplicado.

Normalización técnica vigente:

```text
label
    trim
    comparación case-insensitive
    el candidato se normaliza de forma consistente con persistencia

nombres de secciones complementarias
    trim
    comparación case-insensitive
    orden irrelevante
    misma cantidad
    Bitácora core section (`notas`) excluida

acentos
    no se eliminan
    `Catalogos` != `Catálogos`
```

Una Template presuntamente duplicada puede seguir existiendo en el productor y puede
exportarse como Template. Lo único que no acepta es la promoción a un nuevo Perfil dentro
de esa biblioteca.

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

# 15. Instancia EN PAUSA — fuera del contrato Template/Perfil

`EN PAUSA` pertenece al ciclo operativo de la instancia y **no forma parte del Contrato de
Frontera**.

Objetivo:

```text
EN USO
    ↓
PAUSAR / EDICIÓN LIGHT
    ↓
modificaciones controladas
    ↓
REANUDAR
    ↓
EN USO
```

Las modificaciones “calientes” deben ser **aditivas o conservativas**.

Regla:

> **Nunca romper referencias existentes ni destruir historia.**

Ejemplos permitidos:

```text
- agregar sección
- agregar Tipo
- renombrar sección
- renombrar Tipo
- reordenar secciones
- mover main ↔ more
- cambiar labels de la Bitácora core section
- cambiar features para uso futuro
- eventualmente ocultar/inhibir elementos según reglas a definir
```

No permitido:

```text
- eliminar un Tipo usado por entradas
- eliminar una sección con contenido
- romper scope_id existentes
- borrar comentarios históricos al desactivar comentarios
- borrar adjuntos históricos al desactivar archivos
- cualquier cambio que deje referencias huérfanas
```

Coherencia con `core_overrides`:

Los atributos de presentación/features de la Bitácora core section que un Perfil puede
configurar inicialmente deben poder ser candidatos a modificación durante PAUSA.

Las invariantes estructurales siguen reservadas al Engine:

```text
slug=notas
role=core
area=main
state=active
order=0
existencia
```

Desactivar una feature afecta el uso futuro, no destruye historia.

---

# 16. Organización `main|more`

`main|more` es principalmente organización visual de secciones complementarias.

La Bitácora core section permanece siempre en `main`.

En una instancia, durante PAUSA, debe poder:

```text
- mover una sección complementaria main ↔ more
- reordenar
- agregar secciones
- ocultar secciones existentes respetando la preservación histórica
```

Ocultar una sección no elimina ni invalida su contenido.

Una sección oculta:

```text
- queda excluida de la interfaz normal de navegación
- no admite nuevas entradas
- conserva todas sus entradas y relaciones
- conserva imágenes, adjuntos, comentarios y demás elementos asociados
- mantiene válidas las referencias existentes hacia sus entradas o recursos
- puede aportar resultados a búsquedas multisección
- sigue siendo accesible mediante búsquedas, referencias u otros mecanismos válidos
```

El ocultamiento permite retirar una sección del uso corriente sin destruir historia ni
romper referencias cuando su eliminación no es admisible.

---

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

# 20. Estado técnico operativo — 2026-09-29

## Git

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
- presunción de duplicado por label
- presunción de duplicado por cantidad+nombres de secciones
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
| P02 | CERRADO | Normalización de duplicados definida e implementada |
| P03 | CERRADO | Entrada PHP de PROMOCIÓN implementada sobre adopción autoritativa común |
| P04 | CERRADO | Adopción stored protegida por lock + transacción + rollback; REJECT sin Profile residual |
| P05 | CERRADO | Library limitada a Profiles aceptados; autoría legacy retirada del Engine |
| P06 | PENDIENTE DE ANÁLISIS | Decidir forma física futura del Constructor: plugin/addon/módulo/integración externa |
| P07 | DECIDIDO / IMPLEMENTAR | Refactorizar UI: `Cerrar Bitácora` separado de `Usar Perfil` |
| P08 | DECIDIDO / IMPLEMENTAR | Reutilizar warning/guard destructivo en `Cerrar Bitácora` |
| P09 | CERRADO | `EN USO`/`DISPONIBLE` retirados como estados técnicos de Profile; `DISPONIBLE` queda sólo como UI contextual |
| P10 | DECIDIDO / IMPLEMENTAR | Ocultar `Usar este perfil` mientras exista una instancia |
| P11 | DECIDIDO / IMPLEMENTAR | Separar identidad técnica y etiqueta visible en editor/runtime |
| P12 | DECIDIDO / IMPLEMENTAR | Implementar EN PAUSA / edición light conservativa |
| P13 | DECIDIDO / IMPLEMENTAR | Ocultar Sección preserva historia, excluye navegación normal e impide nuevas entradas; mantiene referencias y búsqueda multisección |
| P14 | PENDIENTE DE ANÁLISIS | Semántica exacta de ocultar Tipo |
| P15 | DECIDIDO / IMPLEMENTAR | Exportar Profile como Template externo; no existe “importar Perfil” |
| P16 | CERRADO | CREACIÓN desde Template externo implementada reutilizando la adopción autoritativa |
| P17 | PENDIENTE DE ANÁLISIS | Evaluar si alguna vez hace falta Perfil sucesor |
| P18 | DECIDIDO / PROBAR | Primera puesta en uso desde instalación neutra |
| P19 | DECIDIDO / PROBAR | Usuario `use_bitacora_profiles=yes`, `manage_bitacora_profiles=no` |
| P20 | DECIDIDO / PROBAR | Regresión final/fresh 0.3.0 en BITA |
| P21 | CERRADO | `docs/CONTEXTO-MAESTRO.md` actualizado y versionado al estado post-refactor de Profiles |
| P22 | DECIDIDO | Release 0.3.0 después de la regresión final |
| P23 | OBSERVACIÓN | Búsqueda multisección |
| P24 | OBSERVACIÓN | Shortcuts/referencias entre entradas |
| P25 | OBSERVACIÓN | Diferencia visual menor frontend/wp-admin |
| P26 | CERRADO | Ciclo completo Template → CREACIÓN → adopción → Library → catálogo → DELETE verificado en KOOPITA |
| P27 | CERRADO | PROMOCIÓN verificada: ACCEPT + REJECT por label duplicado + REJECT por composición de secciones |

---

# 25. Pruebas pendientes para cerrar 0.3.0

La antigua regresión integral de `Perfil_pruebas00` debe reinterpretarse a la luz de la
separación Template/Perfil.

No conviene invertir más trabajo en consolidar una UX de “Perfil incompleto” dentro del
Engine si esa UI está destinada a convertirse en Constructor.

Prioridades:

```text
1. refactor mínimo de Cerrar Bitácora
2. verificar instalación neutra
3. Usar Perfil desde estado neutro
4. permisos reales
5. no regresión de los cuatro bundled
6. BITA fresh
7. idempotencia
8. release
```

Las pruebas específicas del Constructor se definirán después de cerrar su frontera y forma
de integración.

---

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

La frontera Candidate → Profile y sus dos formas de entrada —CREACIÓN y PROMOCIÓN— quedaron implementadas y verificadas end-to-end.

Retomar desde:

```text
1. cerrar operación runtime:
       Instancia actual
       Cerrar Bitácora
       instalación neutra
       Usar Perfil

2. trasladar/reutilizar el warning destructivo probado en `e20211c`

3. verificar:
       Cerrar Bitácora deja instalación neutra
       Usar Perfil sólo aparece/opera en instalación neutra
       primera puesta en uso
       permisos use_bitacora_profiles / manage_bitacora_profiles

4. regresión de los cuatro bundled

5. BITA fresh + idempotencia

6. después retomar como bloques separados:
       EN PAUSA
       Constructor de Templates
       EXPORTACIÓN Profile → Template
```

Principio de cierre:

> **Bitácora Engine hace lo que sabe hacer: garantiza sus servicios, administra Perfiles
> Bitácora y opera una instancia. La construcción de Templates pertenece a una herramienta
> opcional. La frontera entre ambos mundos es una adopción atómica: sea iniciada por PROMOCIÓN o por
> CREACIÓN, o nace un Perfil o el candidato es rechazado sin dejar rastro en el dominio de
> Bitácora.**
