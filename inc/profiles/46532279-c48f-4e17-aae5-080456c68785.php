<?php
/**
 * Perfil incluido: Centro Educativo
 *
 * Definición declarativa distribuida con Bitácora.
 * Promovida desde un perfil funcionalmente validado.
 */

defined( 'ABSPATH' ) || exit;

return array (
  'id' => '46532279-c48f-4e17-aae5-080456c68785',
  'label' => 'Centro Educativo',
  'core' =>
  array (
  ),
  'sections' =>
  array (
    'coordinacion-diaria' =>
    array (
      'name' => 'Coordinación Diaria',
      'slug' => 'coordinacion-diaria',
      'order' => 10,
      'state' => 'active',
      'area' => 'main',
      'feature_file' => true,
      'feature_location' => false,
      'feature_comments' => true,
    ),
    'actividades-especiales' =>
    array (
      'name' => 'Actividades Especiales',
      'slug' => 'actividades-especiales',
      'order' => 20,
      'state' => 'active',
      'feature_file' => false,
      'feature_location' => true,
      'feature_comments' => true,
      'area' => 'more',
    ),
    'reuniones' =>
    array (
      'name' => 'Reuniones',
      'slug' => 'reuniones',
      'order' => 30,
      'state' => 'active',
      'feature_file' => true,
      'feature_location' => true,
      'feature_comments' => false,
      'area' => 'main',
    ),
    'comunicaciones' =>
    array (
      'name' => 'Comunicaciones',
      'slug' => 'comunicaciones',
      'order' => 40,
      'state' => 'active',
      'feature_file' => true,
      'feature_location' => false,
      'feature_comments' => false,
      'area' => 'main',
    ),
    'mantenimiento' =>
    array (
      'name' => 'Mantenimiento',
      'slug' => 'mantenimiento',
      'order' => 50,
      'state' => 'active',
      'feature_file' => false,
      'feature_location' => true,
      'feature_comments' => false,
      'area' => 'main',
    ),
  ),
  'classes' =>
  array (
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
    'actividades-especiales-propuesta' =>
    array (
      'name' => 'Propuesta',
      'slug' => 'actividades-especiales-propuesta',
      'scope' => 'section',
      'scope_id' => 'actividades-especiales',
      'order' => 10,
      'state' => 'active',
    ),
    'actividades-especiales-preparacion' =>
    array (
      'name' => 'Preparación',
      'slug' => 'actividades-especiales-preparacion',
      'scope' => 'section',
      'scope_id' => 'actividades-especiales',
      'order' => 20,
      'state' => 'active',
    ),
    'actividades-especiales-confirmacion' =>
    array (
      'name' => 'Confirmación',
      'slug' => 'actividades-especiales-confirmacion',
      'scope' => 'section',
      'scope_id' => 'actividades-especiales',
      'order' => 30,
      'state' => 'active',
    ),
    'actividades-especiales-cambio' =>
    array (
      'name' => 'Cambio',
      'slug' => 'actividades-especiales-cambio',
      'scope' => 'section',
      'scope_id' => 'actividades-especiales',
      'order' => 40,
      'state' => 'active',
    ),
    'actividades-especiales-incidencia' =>
    array (
      'name' => 'Incidencia',
      'slug' => 'actividades-especiales-incidencia',
      'scope' => 'section',
      'scope_id' => 'actividades-especiales',
      'order' => 50,
      'state' => 'active',
    ),
    'actividades-especiales-cierre' =>
    array (
      'name' => 'Cierre',
      'slug' => 'actividades-especiales-cierre',
      'scope' => 'section',
      'scope_id' => 'actividades-especiales',
      'order' => 60,
      'state' => 'active',
    ),
    'reuniones-convocatoria' =>
    array (
      'name' => 'Convocatoria',
      'slug' => 'reuniones-convocatoria',
      'scope' => 'section',
      'scope_id' => 'reuniones',
      'order' => 10,
      'state' => 'active',
    ),
    'reuniones-acuerdo' =>
    array (
      'name' => 'Acuerdo',
      'slug' => 'reuniones-acuerdo',
      'scope' => 'section',
      'scope_id' => 'reuniones',
      'order' => 20,
      'state' => 'active',
    ),
    'reuniones-pendiente' =>
    array (
      'name' => 'Pendiente',
      'slug' => 'reuniones-pendiente',
      'scope' => 'section',
      'scope_id' => 'reuniones',
      'order' => 30,
      'state' => 'active',
    ),
    'reuniones-seguimiento' =>
    array (
      'name' => 'Seguimiento',
      'slug' => 'reuniones-seguimiento',
      'scope' => 'section',
      'scope_id' => 'reuniones',
      'order' => 40,
      'state' => 'active',
    ),
    'reuniones-aviso' =>
    array (
      'name' => 'Aviso',
      'slug' => 'reuniones-aviso',
      'scope' => 'section',
      'scope_id' => 'reuniones',
      'order' => 50,
      'state' => 'active',
    ),
    'mantenimiento-necesidad' =>
    array (
      'name' => 'Necesidad',
      'slug' => 'mantenimiento-necesidad',
      'scope' => 'section',
      'scope_id' => 'mantenimiento',
      'order' => 10,
      'state' => 'active',
    ),
    'mantenimiento-incidencia' =>
    array (
      'name' => 'Incidencia',
      'slug' => 'mantenimiento-incidencia',
      'scope' => 'section',
      'scope_id' => 'mantenimiento',
      'order' => 20,
      'state' => 'active',
    ),
    'mantenimiento-reparacion' =>
    array (
      'name' => 'Reparación',
      'slug' => 'mantenimiento-reparacion',
      'scope' => 'section',
      'scope_id' => 'mantenimiento',
      'order' => 30,
      'state' => 'active',
    ),
    'mantenimiento-servicio' =>
    array (
      'name' => 'Servicio',
      'slug' => 'mantenimiento-servicio',
      'scope' => 'section',
      'scope_id' => 'mantenimiento',
      'order' => 40,
      'state' => 'active',
    ),
    'mantenimiento-seguimiento' =>
    array (
      'name' => 'Seguimiento',
      'slug' => 'mantenimiento-seguimiento',
      'scope' => 'section',
      'scope_id' => 'mantenimiento',
      'order' => 50,
      'state' => 'active',
    ),
    'comunicaciones-aviso' =>
    array (
      'name' => 'Aviso',
      'slug' => 'comunicaciones-aviso',
      'scope' => 'section',
      'scope_id' => 'comunicaciones',
      'order' => 10,
      'state' => 'active',
    ),
    'comunicaciones-circular' =>
    array (
      'name' => 'Circular',
      'slug' => 'comunicaciones-circular',
      'scope' => 'section',
      'scope_id' => 'comunicaciones',
      'order' => 20,
      'state' => 'active',
    ),
    'comunicaciones-consulta' =>
    array (
      'name' => 'Consulta',
      'slug' => 'comunicaciones-consulta',
      'scope' => 'section',
      'scope_id' => 'comunicaciones',
      'order' => 30,
      'state' => 'active',
    ),
    'comunicaciones-coordinacion' =>
    array (
      'name' => 'Coordinación',
      'slug' => 'comunicaciones-coordinacion',
      'scope' => 'section',
      'scope_id' => 'comunicaciones',
      'order' => 40,
      'state' => 'active',
    ),
    'comunicaciones-difusion' =>
    array (
      'name' => 'Difusión',
      'slug' => 'comunicaciones-difusion',
      'scope' => 'section',
      'scope_id' => 'comunicaciones',
      'order' => 50,
      'state' => 'active',
    ),
    'coordinacion-diaria-faltante' =>
    array (
      'name' => 'Faltante',
      'slug' => 'coordinacion-diaria-faltante',
      'scope' => 'section',
      'scope_id' => 'coordinacion-diaria',
      'order' => 10,
      'state' => 'active',
    ),
    'coordinacion-diaria-incidencia' =>
    array (
      'name' => 'Incidencia',
      'slug' => 'coordinacion-diaria-incidencia',
      'scope' => 'section',
      'scope_id' => 'coordinacion-diaria',
      'order' => 20,
      'state' => 'active',
    ),
    'coordinacion-diaria-cambio' =>
    array (
      'name' => 'Cambio',
      'slug' => 'coordinacion-diaria-cambio',
      'scope' => 'section',
      'scope_id' => 'coordinacion-diaria',
      'order' => 30,
      'state' => 'active',
    ),
    'coordinacion-diaria-coordinacion' =>
    array (
      'name' => 'Coordinación',
      'slug' => 'coordinacion-diaria-coordinacion',
      'scope' => 'section',
      'scope_id' => 'coordinacion-diaria',
      'order' => 40,
      'state' => 'active',
    ),
    'coordinacion-diaria-seguimiento' =>
    array (
      'name' => 'Seguimiento',
      'slug' => 'coordinacion-diaria-seguimiento',
      'scope' => 'section',
      'scope_id' => 'coordinacion-diaria',
      'order' => 50,
      'state' => 'active',
    ),
  ),
);
