<?php
// This file declares a managed database record of type "Job".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 => 
  array (
    'name' => 'Remove vge adres if hov ended',
    'entity' => 'Job',
    'params' => 
    array (
      'version' => 3,
      'name' => 'Remove vge adres if hov ended',
      'description' => 'Remove the vge adres when the hov is ended.',
      'run_frequency' => 'Daily',
      'api_entity' => 'VgeAdres',
      'api_action' => 'RemoveVgeHovEnded',
      'parameters' => '',
    ),
  ),
);