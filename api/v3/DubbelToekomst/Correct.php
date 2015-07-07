<?php

/**
 * DubbelToekomst.Correct API
 * incident BOSW1504076
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_dubbel_toekomst_correct($params) {
  $sameContactId = null;
  $keepAddress = array();

  $query = 'SELECT a.id AS contact_id, b.id AS address_id
      FROM civicrm_contact a JOIN civicrm_address b ON a.id=b.contact_id and location_type_id=%1
      WHERE a.id IN(SELECT contact_id FROM civicrm_address WHERE location_type_id = %1
      GROUP BY contact_id HAVING count(*) > 1 ORDER BY contact_id) ORDER BY contact_id, address_id DESC';
  $queryParams = array(1 => array(7, 'Integer'));
  $dao = CRM_Core_DAO::executeQuery($query, $queryParams);

  while ($dao->fetch()) {
    if ($dao->contact_id != $sameContactId) {
      $sameContactId = $dao->contact_id;
      $keepAddress = setKeepAddress($dao->address_id);
    }
    if ($dao->address_id != $keepAddress['address_id']) {
      checkAddress($dao->address_id, $keepAddress);
    }
  }
  $returnValues = array('is_error'=> 0, 'message' => 'Verwerking succesvol afgerond');
  return civicrm_api3_create_success($returnValues, $params, 'DubbelToekomst', 'Correct');
}

function checkAddress($addressId, $keepAddress) {
  try {
    $removeAddress = civicrm_api3('Address', 'Getsingle', array('id' => $addressId));
    $equalAddress = TRUE;
    $checkFields = array('street_name', 'street_number', 'street_unit', 'city', 'postal_code');
    foreach ($checkFields as $checkFieldName) {
      if (isset($removeAddress[$checkFieldName])) {
        if ($removeAddress[$checkFieldName] != $keepAddress[$checkFieldName]) {
          $equalAddress = false;
        }
      } else {
        if (!empty($keepAddress[$checkFieldName])) {
          $equalAddress = FALSE;
        }
      }
    }

    if ($equalAddress) {
      removeAddress($addressId);
    }
  } catch (CiviCRM_API3_Exception $ex) {}
}

function removeAddress($addressId) {
  /*
   * remove address from sync table first
   */
  $query = 'DELETE FROM civicrm_value_synchronisatie_first_noa_8 WHERE entity_49 = %1 AND entity_id_50 = %2';
  $params = array(
    1 => array('address', 'String'),
    2 => array($addressId, 'Integer'));
  CRM_Core_DAO::executeQuery($query, $params);
  /*
   * remove address
   */
  civicrm_api3('Address', 'Delete', array('id' => $addressId));
}

function setKeepAddress($addressId) {
  $keepAddress = array();
  try {
    $address = civicrm_api3('Address', 'Getsingle', array('id' => $addressId));
    $keepAddress['address_id'] = $address['id'];
    if (!empty($address['street_name'])) {
      $keepAddress['street_name'] = $address['street_name'];
    } else {
      $keepAddress['street_name'] = null;
    }
    if (!empty($address['street_number'])) {
      $keepAddress['street_number'] = $address['street_number'];
    } else {
      $keepAddress['street_number'] = null;
    }
    if (!empty($address['street_unit'])) {
      $keepAddress['street_unit'] = $address['street_unit'];
    } else {
      $keepAddress['street_unit'] = null;
    }
    if (!empty($address['city'])) {
      $keepAddress['city'] = $address['city'];
    } else {
      $keepAddress['city'] = null;
    }
    if (!empty($address['postal_code'])) {
      $keepAddress['postal_code'] = $address['postal_code'];
    } else {
      $keepAddress['postal_code'] = null;
    }
  } catch (CiviCRM_API3_Exception $ex) {}
  return $keepAddress;
}

