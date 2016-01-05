<?php

/**
 * HouseholdSync.Remove API
 * BOS1601093 verwijderen huishoudens uit sync tabellen (groepen handmatig)
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_household_sync_remove($params) {
  $query = "SELECT DISTINCT(entity_id)
    FROM civicrm_value_synchronisatie_first_noa_8 sync
    JOIN civicrm_contact cont ON sync.entity_id = cont.id
    WHERE contact_type = %1";
  $dao = CRM_Core_DAO::executeQuery($query, array(1 => array("Household", "String")));
  while ($dao->fetch()) {
    $delete = "DELETE FROM civicrm_value_synchronisatie_first_noa_8 WHERE entity_id = %1";
    CRM_Core_DAO::executeQuery($delete, array(1 => array($dao->entity_id, 'Integer')));
  }
  $returnValues = array('Huishoudens verwijderd uit synchronisatie groepen en tabellen');
  return civicrm_api3_create_success($returnValues, $params, 'HouseholdSync', 'Remove');
}

