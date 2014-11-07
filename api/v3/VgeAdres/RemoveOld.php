<?php

/**
 * VgeAdres.RemoveOld API 
 * BOS1406343 Erik Hommel <erik.hommel@civicoop.org>
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_vge_adres_removeold($params) {
  
  /*
   * Configuration part
   */
  set_time_limit(0);
  define('VGE_ADRES_LOCATION_TYPE_ID', 10);
  
  $query = 'SELECT a.id, a.contact_id, b.contact_type FROM civicrm_address a '
    . 'JOIN civicrm_contact b on a.contact_id = b.id '
    . 'WHERE location_type_id = %1 AND contact_type = %2';
  $params = array(1 => array(VGE_ADRES_LOCATION_TYPE_ID, 'Positive'), 2 => array('Individual', 'String'));
  $dao = CRM_Core_DAO::executeQuery($query, $params);
  while ($dao->fetch()) {
    $hoofdHuurder = CRM_Utils_DgwUtils::checkContactHoofdhuurder($dao->contact_id, TRUE);
    $medeHuurder = CRM_Utils_DgwUtils::checkContactMedehuurder($dao->contact_id, TRUE);
    if ($hoofdHuurder == FALSE && $medeHuurder == FALSE) {
      /*
       * first remove addresses for huishoudens then remove for contact
       */
      remove_address_household($dao->contact_id);
      delete_vge_adres($dao->contact_id);
    }
  }
    
  $returnValues = array('is_error'=> 0, 'message' => 'Verwerking succesvol afgerond');
  return civicrm_api3_create_success($returnValues, $params, 'VgeAdres', 'RemoveOld');
}
function remove_address_household($contactId) {
  $huisHoudens = CRM_Utils_DgwUtils::getHuishoudens($contactId);
  if ($huisHoudens['count'] > 0) {
    unset($huisHoudens['count']);
    foreach ($huisHoudens as $huisHouden) {
      delete_vge_adres($huisHouden['huishouden_id']);
    }
  }
}
function delete_vge_adres($contactId) {
  $delAdrQry = 'DELETE FROM civicrm_address WHERE location_type_id = %1 AND contact_id = %2';
  $delAdrParams = array(1 => array(VGE_ADRES_LOCATION_TYPE_ID, 'Positive'), 2 => array($contactId, 'Positive'));
  CRM_Core_DAO::executeQuery($delAdrQry, $delAdrParams);  
}