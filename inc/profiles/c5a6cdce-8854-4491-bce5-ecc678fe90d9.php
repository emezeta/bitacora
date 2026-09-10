<?php
/**
 * Perfil incluido: Eventos
 *
 * Definición declarativa distribuida con Bitácora.
 * Promovida desde un perfil funcionalmente validado.
 */

defined( 'ABSPATH' ) || exit;

return array (
  'id' => 'c5a6cdce-8854-4491-bce5-ecc678fe90d9',
  'label' => 'Eventos',
  'core' =>
  array (
    'feature_comments' => true,
  ),
  'sections' =>
  array (
    'convocatorias' =>
    array (
      'name' => 'Convocatorias',
      'slug' => 'convocatorias',
      'order' => 10,
      'state' => 'active',
      'area' => 'main',
      'feature_file' => true,
      'feature_location' => false,
      'feature_comments' => true,
    ),
    'participantes' =>
    array (
      'name' => 'Participantes',
      'slug' => 'participantes',
      'order' => 20,
      'state' => 'active',
      'area' => 'main',
      'feature_file' => true,
      'feature_location' => false,
      'feature_comments' => true,
    ),
    'obras-propuestas' =>
    array (
      'name' => 'Obras / Propuestas',
      'slug' => 'obras-propuestas',
      'order' => 30,
      'state' => 'active',
      'area' => 'main',
      'feature_file' => true,
      'feature_location' => false,
      'feature_comments' => true,
    ),
    'programacion' =>
    array (
      'name' => 'Programación',
      'slug' => 'programacion',
      'order' => 40,
      'state' => 'active',
      'area' => 'main',
      'feature_file' => false,
      'feature_location' => true,
      'feature_comments' => true,
    ),
    'difusion' =>
    array (
      'name' => 'Difusión',
      'slug' => 'difusion',
      'order' => 50,
      'state' => 'active',
      'area' => 'more',
      'feature_file' => true,
      'feature_location' => false,
      'feature_comments' => true,
    ),
    'operacion-del-evento' =>
    array (
      'name' => 'Operación Del Evento',
      'slug' => 'operacion-del-evento',
      'order' => 60,
      'state' => 'active',
      'area' => 'more',
      'feature_file' => false,
      'feature_location' => true,
      'feature_comments' => true,
    ),
    'produccion' =>
    array (
      'name' => 'Producción',
      'slug' => 'produccion',
      'order' => 70,
      'state' => 'active',
      'area' => 'more',
      'feature_file' => true,
      'feature_location' => true,
      'feature_comments' => true,
    ),
    'proveedores' =>
    array (
      'name' => 'Proveedores',
      'slug' => 'proveedores',
      'order' => 80,
      'state' => 'active',
      'area' => 'more',
      'feature_file' => true,
      'feature_location' => false,
      'feature_comments' => true,
    ),
  ),
  'classes' =>
  array (
    'convocatorias-llamado' =>
    array (
      'name' => 'Llamado',
      'slug' => 'convocatorias-llamado',
      'scope' => 'section',
      'scope_id' => 'convocatorias',
      'order' => 10,
      'state' => 'active',
    ),
    'convocatorias-consulta' =>
    array (
      'name' => 'Consulta',
      'slug' => 'convocatorias-consulta',
      'scope' => 'section',
      'scope_id' => 'convocatorias',
      'order' => 20,
      'state' => 'active',
    ),
    'convocatorias-postulacion' =>
    array (
      'name' => 'Postulación',
      'slug' => 'convocatorias-postulacion',
      'scope' => 'section',
      'scope_id' => 'convocatorias',
      'order' => 30,
      'state' => 'active',
    ),
    'convocatorias-seguimiento' =>
    array (
      'name' => 'Seguimiento',
      'slug' => 'convocatorias-seguimiento',
      'scope' => 'section',
      'scope_id' => 'convocatorias',
      'order' => 40,
      'state' => 'active',
    ),
    'notas-novedad' =>
    array (
      'name' => 'Novedad',
      'slug' => 'notas-novedad',
      'scope' => 'section',
      'scope_id' => 'notas',
      'order' => 10,
      'state' => 'active',
    ),
    'notas-decision' =>
    array (
      'name' => 'Decisión',
      'slug' => 'notas-decision',
      'scope' => 'section',
      'scope_id' => 'notas',
      'order' => 20,
      'state' => 'active',
    ),
    'notas-pendiente' =>
    array (
      'name' => 'Pendiente',
      'slug' => 'notas-pendiente',
      'scope' => 'section',
      'scope_id' => 'notas',
      'order' => 30,
      'state' => 'active',
    ),
    'notas-aviso' =>
    array (
      'name' => 'Aviso',
      'slug' => 'notas-aviso',
      'scope' => 'section',
      'scope_id' => 'notas',
      'order' => 40,
      'state' => 'active',
    ),
    'participantes-ponente' =>
    array (
      'name' => 'Ponente',
      'slug' => 'participantes-ponente',
      'scope' => 'section',
      'scope_id' => 'participantes',
      'order' => 10,
      'state' => 'active',
    ),
    'participantes-concursante' =>
    array (
      'name' => 'Concursante',
      'slug' => 'participantes-concursante',
      'scope' => 'section',
      'scope_id' => 'participantes',
      'order' => 20,
      'state' => 'active',
    ),
    'participantes-expositor' =>
    array (
      'name' => 'Expositor',
      'slug' => 'participantes-expositor',
      'scope' => 'section',
      'scope_id' => 'participantes',
      'order' => 30,
      'state' => 'active',
    ),
    'participantes-invitado-especial' =>
    array (
      'name' => 'Invitado especial',
      'slug' => 'participantes-invitado-especial',
      'scope' => 'section',
      'scope_id' => 'participantes',
      'order' => 40,
      'state' => 'active',
    ),
    'participantes-contacto' =>
    array (
      'name' => 'Contacto',
      'slug' => 'participantes-contacto',
      'scope' => 'section',
      'scope_id' => 'participantes',
      'order' => 50,
      'state' => 'active',
    ),
    'obras-propuestas-recibida' =>
    array (
      'name' => 'Recibida',
      'slug' => 'obras-propuestas-recibida',
      'scope' => 'section',
      'scope_id' => 'obras-propuestas',
      'order' => 10,
      'state' => 'active',
    ),
    'obras-propuestas-en-revision' =>
    array (
      'name' => 'En revisión',
      'slug' => 'obras-propuestas-en-revision',
      'scope' => 'section',
      'scope_id' => 'obras-propuestas',
      'order' => 20,
      'state' => 'active',
    ),
    'obras-propuestas-precalificada' =>
    array (
      'name' => 'Precalificada',
      'slug' => 'obras-propuestas-precalificada',
      'scope' => 'section',
      'scope_id' => 'obras-propuestas',
      'order' => 30,
      'state' => 'active',
    ),
    'obras-propuestas-aceptada' =>
    array (
      'name' => 'Aceptada',
      'slug' => 'obras-propuestas-aceptada',
      'scope' => 'section',
      'scope_id' => 'obras-propuestas',
      'order' => 40,
      'state' => 'active',
    ),
    'obras-propuestas-rechazada' =>
    array (
      'name' => 'Rechazada',
      'slug' => 'obras-propuestas-rechazada',
      'scope' => 'section',
      'scope_id' => 'obras-propuestas',
      'order' => 50,
      'state' => 'active',
    ),
    'programacion-ponencia' =>
    array (
      'name' => 'Ponencia',
      'slug' => 'programacion-ponencia',
      'scope' => 'section',
      'scope_id' => 'programacion',
      'order' => 10,
      'state' => 'active',
    ),
    'programacion-show' =>
    array (
      'name' => 'Show',
      'slug' => 'programacion-show',
      'scope' => 'section',
      'scope_id' => 'programacion',
      'order' => 20,
      'state' => 'active',
    ),
    'programacion-presentacion-especial' =>
    array (
      'name' => 'Presentación especial',
      'slug' => 'programacion-presentacion-especial',
      'scope' => 'section',
      'scope_id' => 'programacion',
      'order' => 30,
      'state' => 'active',
    ),
    'programacion-actividad-paralela' =>
    array (
      'name' => 'Actividad paralela',
      'slug' => 'programacion-actividad-paralela',
      'scope' => 'section',
      'scope_id' => 'programacion',
      'order' => 40,
      'state' => 'active',
    ),
    'programacion-cambio' =>
    array (
      'name' => 'Cambio',
      'slug' => 'programacion-cambio',
      'scope' => 'section',
      'scope_id' => 'programacion',
      'order' => 50,
      'state' => 'active',
    ),
    'difusion-diseno' =>
    array (
      'name' => 'Diseño',
      'slug' => 'difusion-diseno',
      'scope' => 'section',
      'scope_id' => 'difusion',
      'order' => 10,
      'state' => 'active',
    ),
    'difusion-publicacion' =>
    array (
      'name' => 'Publicación',
      'slug' => 'difusion-publicacion',
      'scope' => 'section',
      'scope_id' => 'difusion',
      'order' => 20,
      'state' => 'active',
    ),
    'difusion-prensa-rrpp' =>
    array (
      'name' => 'Prensa / RRPP',
      'slug' => 'difusion-prensa-rrpp',
      'scope' => 'section',
      'scope_id' => 'difusion',
      'order' => 30,
      'state' => 'active',
    ),
    'difusion-campana' =>
    array (
      'name' => 'Campaña',
      'slug' => 'difusion-campana',
      'scope' => 'section',
      'scope_id' => 'difusion',
      'order' => 40,
      'state' => 'active',
    ),
    'difusion-coordinacion' =>
    array (
      'name' => 'Coordinación',
      'slug' => 'difusion-coordinacion',
      'scope' => 'section',
      'scope_id' => 'difusion',
      'order' => 50,
      'state' => 'active',
    ),
    'produccion-montaje' =>
    array (
      'name' => 'Montaje',
      'slug' => 'produccion-montaje',
      'scope' => 'section',
      'scope_id' => 'produccion',
      'order' => 10,
      'state' => 'active',
    ),
    'produccion-equipamiento' =>
    array (
      'name' => 'Equipamiento',
      'slug' => 'produccion-equipamiento',
      'scope' => 'section',
      'scope_id' => 'produccion',
      'order' => 20,
      'state' => 'active',
    ),
    'produccion-traslado' =>
    array (
      'name' => 'Traslado',
      'slug' => 'produccion-traslado',
      'scope' => 'section',
      'scope_id' => 'produccion',
      'order' => 30,
      'state' => 'active',
    ),
    'produccion-necesidad' =>
    array (
      'name' => 'Necesidad',
      'slug' => 'produccion-necesidad',
      'scope' => 'section',
      'scope_id' => 'produccion',
      'order' => 40,
      'state' => 'active',
    ),
    'produccion-incidencia' =>
    array (
      'name' => 'Incidencia',
      'slug' => 'produccion-incidencia',
      'scope' => 'section',
      'scope_id' => 'produccion',
      'order' => 50,
      'state' => 'active',
    ),
    'operacion-del-evento-inicio-de-actividad' =>
    array (
      'name' => 'Inicio de actividad',
      'slug' => 'operacion-del-evento-inicio-de-actividad',
      'scope' => 'section',
      'scope_id' => 'operacion-del-evento',
      'order' => 10,
      'state' => 'active',
    ),
    'operacion-del-evento-cambio' =>
    array (
      'name' => 'Cambio',
      'slug' => 'operacion-del-evento-cambio',
      'scope' => 'section',
      'scope_id' => 'operacion-del-evento',
      'order' => 20,
      'state' => 'active',
    ),
    'operacion-del-evento-demora' =>
    array (
      'name' => 'Demora',
      'slug' => 'operacion-del-evento-demora',
      'scope' => 'section',
      'scope_id' => 'operacion-del-evento',
      'order' => 30,
      'state' => 'active',
    ),
    'operacion-del-evento-incidencia' =>
    array (
      'name' => 'Incidencia',
      'slug' => 'operacion-del-evento-incidencia',
      'scope' => 'section',
      'scope_id' => 'operacion-del-evento',
      'order' => 40,
      'state' => 'active',
    ),
    'operacion-del-evento-coordinacion' =>
    array (
      'name' => 'Coordinación',
      'slug' => 'operacion-del-evento-coordinacion',
      'scope' => 'section',
      'scope_id' => 'operacion-del-evento',
      'order' => 50,
      'state' => 'active',
    ),
    'operacion-del-evento-aviso' =>
    array (
      'name' => 'Aviso',
      'slug' => 'operacion-del-evento-aviso',
      'scope' => 'section',
      'scope_id' => 'operacion-del-evento',
      'order' => 60,
      'state' => 'active',
    ),
    'proveedores-presupuesto' =>
    array (
      'name' => 'Presupuesto',
      'slug' => 'proveedores-presupuesto',
      'scope' => 'section',
      'scope_id' => 'proveedores',
      'order' => 10,
      'state' => 'active',
    ),
    'proveedores-confirmacion' =>
    array (
      'name' => 'Confirmación',
      'slug' => 'proveedores-confirmacion',
      'scope' => 'section',
      'scope_id' => 'proveedores',
      'order' => 20,
      'state' => 'active',
    ),
    'proveedores-compromiso' =>
    array (
      'name' => 'Compromiso',
      'slug' => 'proveedores-compromiso',
      'scope' => 'section',
      'scope_id' => 'proveedores',
      'order' => 30,
      'state' => 'active',
    ),
    'proveedores-reclamo' =>
    array (
      'name' => 'Reclamo',
      'slug' => 'proveedores-reclamo',
      'scope' => 'section',
      'scope_id' => 'proveedores',
      'order' => 40,
      'state' => 'active',
    ),
    'proveedores-referencia' =>
    array (
      'name' => 'Referencia',
      'slug' => 'proveedores-referencia',
      'scope' => 'section',
      'scope_id' => 'proveedores',
      'order' => 50,
      'state' => 'active',
    ),
  ),
);
