<?php

/**
 * NoContactAddress.Find API (BOSW1508087)
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_no_contact_address_find($params) {
  $countFound = 0;
  /*
   * empty result files before we start processing
   */
  if (CRM_Core_DAO::checkTableExists('bosw1508087_address')) {
    CRM_Core_DAO::executeQuery('DELETE FROM bosw1508087_address');
  } else {
    throw new API_Exception('Tabel bosw1508087_address niet gevonden!');
  }
  if (CRM_Core_DAO::checkTableExists('bosw1508087_contact')) {
    CRM_Core_DAO::executeQuery('DELETE FROM bosw1508087_contact');
  } else {
    throw new API_Exception('Tabel bosw1508087_contact niet gevonden!');
  }


  $contactQuery = "SELECT cont.id, cont.contact_type, cont.display_name, aanv.persoonsnummer_first_1 AS pers_first
FROM civicrm_contact cont LEFT JOIN civicrm_value_aanvullende_persoonsgegevens_1 aanv  ON cont.id = aanv.entity_id";
  $daoContact = CRM_Core_DAO::executeQuery($contactQuery);
  while ($daoContact->fetch()) {
    $countAddressQuery = "SELECT COUNT(*) as countAddress FROM civicrm_address WHERE location_type_id = %1 AND contact_id = %2";
    $countAddressParams = array(
      1 => array(1, 'Integer'),
      2 => array($daoContact->id, 'Integer'));
    $daoCountAddress = CRM_Core_DAO::executeQuery($countAddressQuery, $countAddressParams);
    if ($daoCountAddress->fetch()) {
      if ($daoCountAddress->countAddress == 0) {
        _addContact($daoContact);
        $countFound++;
      }
    }
  }
  $returnValues = array('is_error'=> 0, 'message' => 'Verwerking succesvol afgerond, '.$countFound.' contacten gevonden zonder contactadres.');
  return civicrm_api3_create_success($returnValues, $params, 'NoContactAddress', 'Find');
}

/**
 * Function to add contact and addresses to files to report from
 *
 * @param $daoContact
 */
function _addContact($daoContact) {
  if (is_null($daoContact->pers_first)) {
    $daoContact->pers_first = '';
  }
  /*
   * first add contact to file
   */
  $insertContactQuery = "
INSERT INTO bosw1508087_contact (contact_id, contact_type, display_name, pers_first) VALUES(%1, %2, %3, %4)";
  $insertContactParams = array(
    1 => array($daoContact->id, 'Integer'),
    2 => array($daoContact->contact_type, 'String'),
    3 => array($daoContact->display_name, 'String'),
    4 => array($daoContact->pers_first, 'String')
  );

  CRM_Core_DAO::executeQuery($insertContactQuery, $insertContactParams);
  /*
   * then add addresses for contact
   */
  $addressQuery = "SELECT adr.id, adr.street_address, adr.postal_code, adr.city, loc.name AS location_type
FROM civicrm_address adr JOIN civicrm_location_type loc ON adr.location_type_id = loc.id
WHERE adr.contact_id = %1";
  $addressParams = array(1 => array($daoContact->id, 'Integer'));
  $daoAddress = CRM_Core_DAO::executeQuery($addressQuery, $addressParams);
  while ($daoAddress->fetch()) {

    if (is_null($daoAddress->street_address)) {
      $daoAddress->street_address = "";
    }
    if (is_null($daoAddress->postal_code)) {
      $daoAddress->postal_code = "";
    }
    if (is_null($daoAddress->city)) {
      $daoAddress->city = "";
    }

    $insertAddressQuery = "
INSERT INTO bosw1508087_address (address_id, contact_id, street_address, location_type, postal_code, city)
VALUES(%1, %2, %3, %4, %5, %6)";
    $insertAddressParams = array(
      1 => array($daoAddress->id, 'Integer'),
      2 => array($daoContact->id, 'Integer'),
      3 => array($daoAddress->street_address, 'String'),
      4 => array($daoAddress->location_type, 'String'),
      5 => array($daoAddress->postal_code, 'String'),
      6 => array($daoAddress->city, 'String'));
    CRM_Core_DAO::executeQuery($insertAddressQuery, $insertAddressParams);
  }
}
