<?php

/**
 * DgwAddress.Parse API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_dgw_address_parse($params) {
    $count_adressen = 0;
    /*
     * select all addresses with street_address and street_number empty
     */
    $query = "SELECT id, street_address FROM civicrm_address 
        WHERE street_address != '' AND street_number IS NULL";
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
        $adres_elements = _splitStreetAddressNl($dao->street_address);
        $update_fields = array();
        if (isset($adres_elements['street_name']) && !empty($adres_elements['street_name'])) {
            $street_name = CRM_Core_DAO::escapeString($adres_elements['street_name']);
            $update_fields[] = "street_name = '$street_name'";
        }
        if (isset($adres_elements['street_number']) && !empty($adres_elements['street_number'])) {
            $update_fields[] = "street_number = {$adres_elements['street_number']}";
        }
        if (isset($adres_elements['street_unit']) && !empty($adres_elements['street_unit'])) {
            $street_unit = CRM_Core_DAO::escapeString($adres_elements['street_unit']);
            $update_fields[] = "street_unit = '$street_unit'";
        }
        if (!empty($update_fields)) {
            $count_adressen++;
            $update = "UPDATE civicrm_address SET ".implode(", ", $update_fields)." WHERE id = {$dao->id}";
            CRM_Core_DAO::executeQuery($update);
        }
    }
    $returnValues = "Geslaagd, $count_adressen addressen aangepast";
    return civicrm_api3_create_success($returnValues, $params, 'DgwAddress', 'Parse');
}

