<?php
/**
 * Perfil incluido: Depósito
 *
 * Definición declarativa distribuida con Bitácora.
 * Promovida desde un perfil funcionalmente validado.
 */

defined( 'ABSPATH' ) || exit;

return array (
  'id' => '2a3822df-187c-4c2b-8993-1b29324f1074',
  'label' => 'Depósito',
  'core' =>
  array (
    'feature_comments' => true,
    'feature_file' => false,
  ),
  'sections' =>
  array (
    'recepciones' =>
    array (
      'name' => 'Recepciones',
      'slug' => 'recepciones',
      'order' => 10,
      'state' => 'active',
      'area' => 'main',
      'feature_file' => true,
      'feature_location' => false,
      'feature_comments' => true,
    ),
    'devoluciones' =>
    array (
      'name' => 'Devoluciones',
      'slug' => 'devoluciones',
      'order' => 20,
      'state' => 'active',
      'area' => 'main',
      'feature_file' => true,
      'feature_location' => false,
      'feature_comments' => true,
    ),
    'proveedores' =>
    array (
      'name' => 'Proveedores',
      'slug' => 'proveedores',
      'order' => 30,
      'state' => 'active',
      'area' => 'more',
      'feature_file' => true,
      'feature_location' => false,
      'feature_comments' => true,
    ),
  ),
  'classes' =>
  array (
    'devoluciones-dano' =>
    array (
      'name' => 'Daño',
      'slug' => 'devoluciones-dano',
      'scope' => 'section',
      'scope_id' => 'devoluciones',
      'order' => 10,
      'state' => 'active',
    ),
    'devoluciones-error-de-entrega' =>
    array (
      'name' => 'Error de entrega',
      'slug' => 'devoluciones-error-de-entrega',
      'scope' => 'section',
      'scope_id' => 'devoluciones',
      'order' => 20,
      'state' => 'active',
    ),
    'devoluciones-vencimiento' =>
    array (
      'name' => 'Vencimiento',
      'slug' => 'devoluciones-vencimiento',
      'scope' => 'section',
      'scope_id' => 'devoluciones',
      'order' => 30,
      'state' => 'active',
    ),
    'devoluciones-otro-motivo' =>
    array (
      'name' => 'Otro motivo',
      'slug' => 'devoluciones-otro-motivo',
      'scope' => 'section',
      'scope_id' => 'devoluciones',
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
    'notas-observacion' =>
    array (
      'name' => 'Observación',
      'slug' => 'notas-observacion',
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
    'recepciones-correcta' =>
    array (
      'name' => 'Correcta',
      'slug' => 'recepciones-correcta',
      'scope' => 'section',
      'scope_id' => 'recepciones',
      'order' => 10,
      'state' => 'active',
    ),
    'recepciones-faltante' =>
    array (
      'name' => 'Faltante',
      'slug' => 'recepciones-faltante',
      'scope' => 'section',
      'scope_id' => 'recepciones',
      'order' => 20,
      'state' => 'active',
    ),
    'recepciones-error-de-entrega' =>
    array (
      'name' => 'Error de entrega',
      'slug' => 'recepciones-error-de-entrega',
      'scope' => 'section',
      'scope_id' => 'recepciones',
      'order' => 30,
      'state' => 'active',
    ),
    'recepciones-dano' =>
    array (
      'name' => 'Daño',
      'slug' => 'recepciones-dano',
      'scope' => 'section',
      'scope_id' => 'recepciones',
      'order' => 40,
      'state' => 'active',
    ),
    'proveedores-observacion' =>
    array (
      'name' => 'Observación',
      'slug' => 'proveedores-observacion',
      'scope' => 'section',
      'scope_id' => 'proveedores',
      'order' => 10,
      'state' => 'active',
    ),
    'proveedores-reclamo' =>
    array (
      'name' => 'Reclamo',
      'slug' => 'proveedores-reclamo',
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
    'proveedores-referencia' =>
    array (
      'name' => 'Referencia',
      'slug' => 'proveedores-referencia',
      'scope' => 'section',
      'scope_id' => 'proveedores',
      'order' => 40,
      'state' => 'active',
    ),
  ),
);
