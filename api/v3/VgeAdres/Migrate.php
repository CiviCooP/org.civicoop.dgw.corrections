<?php

/**
 * VgeAdres.Migrate API 
 * BOS1406343 Erik Hommel <erik.hommel@civicoop.org>
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_vge_adres_migrate($params) {
  /*
   * Configuration part
   */
  $tables = array();
  $tables[0]['table'] = 'civicrm_value_huurovereenkomst_2';
  $tables[0]['end'] = 'einddatum_hov_10';
  $tables[0]['vge_id'] = 'vge_nummer_first_6';
  
  $tables[1]['table'] = 'civicrm_value_huurovereenkomst__org__11';
  $tables[1]['end'] = 'einddatum_overeenkomst_62';
  $tables[1]['vge_id'] = 'vge_nummer_59';
  
  define('VGE_ADRES_LOCATION_TYPE_ID', 10);
  
  foreach ($tables as $hovTable) {
    
    define('HOV_TABLE', $hovTable['table']);
    define('END_DATE_COLUMN', $hovTable['end']);
    define('VGE_ID_COLUMN', $hovTable['vge_id']);
    
    $queryHov = 'SELECT DISTINCT(entity_id) AS contactId FROM '.$hovTable['table'];
    $daoHov = CRM_Core_DAO::executeQuery($queryHov);
    while ($daoHov->fetch()) {
      generate_vge_adres_contact($daoHov->contactId);
    }
  }
  $returnValues = array('is_error'=> 0, 'message' => 'Verwerking succesvol afgerond');
  return civicrm_api3_create_success($returnValues, $params, 'VgeAdres', 'Migrate');
}
/*
 * Function to generate Vge Adres for contact
 */
function generate_vge_adres_contact($contactId) {
  /*
   * only process if no vge adres for contact
   */
  if (check_vge_adres_exists($contactId) == FALSE) {
    /*
     * get huurovereenkomst without end date or get latest if there is none
     */
    $hov = get_hov_without_end_date($contactId);
    if ($hov == FALSE) {
      $hov = get_hov_with_latest_end_date($contactId);
    }
    /*
     * generate vge adres for contact
     */
    if (!empty($hov->vge_id)) {
      create_vge_adres($hov->vge_id, $contactId);
    }
  }
}
/*
 * function to create vge adres for contact
 */
function create_vge_adres($vgeId, $contactId) {
  $params = get_adres_params($vgeId);
  if (!empty($params)) {
    /*
     * create vge_adres for huishouden or organisatie
     */
    $params['contact_id'] = $contactId;
    civicrm_api3('Address', 'Create', $params);
    /*
     * then create adresses for hoofd and medehuurders
     */
    $hoofdHuurders = CRM_Utils_DgwUtils::getHoofdhuurders($contactId, false);
    $medeHuurders = CRM_Utils_DgwUtils::getMedehuurders($contactId, false);
    foreach ($hoofdHuurders as $hoofdHuurder) {
      if (check_vge_adres_exists($hoofdHuurder['contact_id']) == FALSE) {
        $params['contact_id'] = $hoofdHuurder['contact_id'];
        civicrm_api3('Address', 'Create', $params);
      }
    }
    foreach ($medeHuurders as $medeHuurder) {
      if (check_vge_adres_exists($medeHuurder['medehuurder_id']) == FALSE) {
        $params['contact_id'] = $medeHuurder['medehuurder_id'];
        civicrm_api3('Address', 'Create', $params);
      }
    }
  }
}
/*
 * Function to get a hov with end date empty
 */
function get_hov_with_latest_end_date($contactId) {
  $query = 'SELECT '.VGE_ID_COLUMN.' AS vge_id FROM '.HOV_TABLE.' WHERE entity_id = %1 AND '
    .END_DATE_COLUMN.' IS NOT NULL ORDER BY '.END_DATE_COLUMN.' DESC';
  $params = array(1 => array($contactId, 'Positive'));
  $dao = CRM_Core_DAO::executeQuery($query, $params);
  if ($dao->fetch()) {
    return $dao;
  } else {
    return FALSE;
  }
}
/*
 * Function to get a hov with end date empty
 */
function get_hov_without_end_date($contactId) {
  $query = 'SELECT '.VGE_ID_COLUMN.' AS vge_id FROM '.HOV_TABLE.' WHERE entity_id = %1 AND '
    .END_DATE_COLUMN.' IS NULL';
  $params = array(1 => array($contactId, 'Positive'));
  $dao = CRM_Core_DAO::executeQuery($query, $params);
  if ($dao->fetch()) {
    return $dao;
  } else {
    return FALSE;
  }
}
/*
 * Function to check if there is a vge adres for the contact
 */
function check_vge_adres_exists($contactId) {
  if (!empty($contactId)) {
    $query = 'SELECT COUNT(*) AS countExist FROM civicrm_address '
      . 'WHERE contact_id = %1 AND location_type_id = %2';
    $params = array(1 => array($contactId, 'Positive'), 2 => array(VGE_ADRES_LOCATION_TYPE_ID, 'Positive'));
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    if ($dao->fetch()) {
      if ($dao->countExist > 0) {
        return TRUE;
      }
    }
  }
  return FALSE;
}
/*
 * Function to retrieve adres with vge ID and set params
 */
function get_adres_params($vgeId) {
  $adres = CRM_Mutatieproces_Property::getByVgeId($vgeId);
  if (isset($adres['count']) && $adres['count'] == 0) {
    return array();
  }
  $params = array(
    'location_type_id' => VGE_ADRES_LOCATION_TYPE_ID,
    'country_id' => $adres['vge_country_id'],
    'street_name' => $adres['vge_street_name']);    
  if (isset($adres['vge_street_number'])) {
    $params['street_number'] = $adres['vge_street_number'];
  }
  if (isset($adres['vge_postal_code'])) {
    $params['postal_code'] = $adres['vge_postal_code'];
  }
  if (isset($adres['vge_street_unit'])) {
    $params['street_unit'] = $adres['vge_street_unit'];
  }
  if (isset($adres['vge_city'])) {
    $params['city'] = $adres['vge_city'];
  }
  $params['street_address'] = _glueStreetAddressNl($params);
  return $params;  
}


