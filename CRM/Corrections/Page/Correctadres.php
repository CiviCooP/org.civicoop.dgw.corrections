<?php
require_once 'CRM/Core/Page.php';

class CRM_Corrections_Page_Correctadres extends CRM_Core_Page {
    function run() {
        CRM_Utils_System::setTitle(ts('Corrigeren adressen'));
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
        if (!isset($session)) {
            $session = CRM_Core_Session::singleton();
        }
        $session->reset();
        $session->setStatus("Corrigeren addressen is succesvol afgerond.", "Corrigeren adressen afgerond", 'success');
        $this->assign('exitMsg', "Corrigeren adressen klaar, $count_adressen aangepast");
        $this->assign('returnUrl', CRM_Utils_System::url('civicrm', null, true));
        parent::run();
    }
}
