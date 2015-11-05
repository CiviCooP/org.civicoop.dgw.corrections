<?php
/**
 * NoContactAddress.FixOudAdres1 API
 * temp fix BOS@1508087 - contact zonder contactadres met dubbele oude adressen
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_no_contact_address_fixoudadres1($params) {
  /*
   * lees alle contacten uit bosw1508087_onlyoud_contact
   */
  $contactDao = CRM_Core_DAO::executeQuery("SELECT contact_id FROM bosw1508087_onlyoud_contact");
  while ($contactDao->fetch()) {
    /*
     * haal laatste adres met locatie_type oud
     */
    $addressQuery = "SELECT address_id FROM bosw1508087_onlyoud_address WHERE contact_id = %1 AND location_type = %2
      ORDER BY address_id DESC";
    $addressParams = array(
      1 => array($contactDao->contact_id, "Integer"),
      2 => array("Oud", "String"));
    $latestOudAddressId = CRM_Core_DAO::singleValueQuery($addressQuery, $addressParams);
    if (!empty($latestOudAddressId)) {
      /*
       * zeker stellen dat alle andere adressen van contact niet primary zijn
       */
      $updateNotPrimary = "UPDATE civicrm_address SET is_primary = %1 WHERE contact_id = %2 AND id != %3";
      $updateNotPrimaryParams = array(
        1 => array(0, "Integer"),
        2 => array($contactDao->contact_id, "Integer"),
        3 => array($latestOudAddressId, "Integer"));
      CRM_Core_DAO::executeQuery($updateNotPrimary, $updateNotPrimaryParams);
      /*
       * laatste oude adres primary en contact adres maken
       */
      $updateContactAdres = "UPDATE civicrm_address SET location_type_id = %1, is_primary = %1 WHERE id = %2";
      $updateContactAdresParams = array(
        1 => array(1, "Integer"),
        2 => array($latestOudAddressId, "Integer"));
      CRM_Core_DAO::executeQuery($updateContactAdres, $updateContactAdresParams);
      /*
       * alle overgebleven oude adressen verwijderen
       */
      $deleteOudAdres = "DELETE FROM civicrm_address WHERE contact_id = %1 AND location_type_id = %2";
      $deleteOudAdresParams = array(
        1 => array($contactDao->contact_id, "Integer"),
        2 => array(6, "Integer"));
      CRM_Core_DAO::executeQuery($deleteOudAdres, $deleteOudAdresParams);
      /*
       * als contact hoofdhuurder, ook adressen van huishouden bijwerken
       */
      if (CRM_Utils_DgwUtils::checkContactHoofdhuurder($contactDao->contact_id)) {
        CRM_Utils_DgwUtils::processAddressesHoofdHuurder($contactDao->contact_id);
      }
    }
  }
  return civicrm_api3_create_success(array(), $params, 'NoContactAddress', 'FixOudAdres1');
}

