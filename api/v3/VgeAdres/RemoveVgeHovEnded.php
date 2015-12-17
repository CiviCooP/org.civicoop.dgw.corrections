<?php
set_time_limit(0);

/**
 * VgeAdres.RemoveVgeHovEnded API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_vge_adres_removevgehovended_spec(&$spec) {
}

/**
 * VgeAdres.RemoveVgeHovEnded API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_vge_adres_removevgehovended($params) {  
  $debug = CRM_Utils_Array::value('debug', $params, false);
    
  $return['is_error'] = false;
  
  if($debug){
    $return['message'][] = ts('Debug is on !');
    echo ts('Debug is on !') . '<br/>' . PHP_EOL;
  }
  
  // households
  $return_household = vge_adres_removevgehovended_hov_household($debug);
  if($return_household['is_error']){
    $return = $return_household;
    $return['is_error'] = true;
    return civicrm_api3_create_error($return);
  }
  
  // organizations
  $return_organization = vge_adres_removevgehovended_hov_organization($debug);
  if($return_organization['is_error']){
    $return = array_merge($return_household, $return_organization);
    $return['is_error'] = true;
    return civicrm_api3_create_error($return);
  }
  
  $return = array_merge($return_household, $return_organization);
  
  if($debug){
    CRM_Utils_System::civiExit();
  }
  
  return civicrm_api3_create_success($return);
}

function vge_adres_removevgehovended_hov_household($debug){ 
  $return['is_error'] = false;
  $return['message'] = [];
  
  $locationtype = CRM_Utils_DgwUtils::getLocationType(['name' => 'VGEadres'], 'getsingle');
  if(!$locationtype){
    $return['error_message'] = sprintf('Cannot get the location_type_id of the VGE adres');
    if($debug){
      echo sprintf('Cannot get the location_type_id of the VGE adres') . '<br/>' . PHP_EOL;
    }
    $return['is_error'] = true;
    return $return;
  }
  
  $customgroup = CRM_Utils_DgwUtils::getCustomGroup(['name' => 'Huurovereenkomst (huishouden)'], 'getsingle'); 
  if(!$customgroup){
    $return['error_message'] = sprintf('Cannot get the custom_group_id of the Huurovereenkomst (huishouden)');
    if($debug){
      echo sprintf('Cannot get the custom_group_id of the Huurovereenkomst (huishouden)') . '<br/>' . PHP_EOL;
    }
    $return['is_error'] = true;
    return $return;
  }
  
  $customfield = CRM_Utils_DgwUtils::getCustomFieldAll(['custom_group_id' => $customgroup['id']]);  
  if(!$customfield){
    $return['error_message'] = sprintf('Cannot get all the custom fields of the Huurovereenkomst (huishouden)');
    if($debug){
      echo sprintf('Cannot get all the custom fields of the Huurovereenkomst (huishouden)') . '<br/>' . PHP_EOL;
    }
    $return['is_error'] = true;
    return $return;
  }
  
  $customfields = [];
  foreach($customfield['values'] as $key => $field){
    $customfields[$field['name']] = $field;
  }
  
  $query = "SELECT contact.id, hov.entity_id,
    hov." . $customfields['HOV_nummer_First']['column_name'] . ",
    hov." . $customfields['VGE_nummer_First']['column_name'] . ",
    hov." . $customfields['VGE_adres_First']['column_name'] . ",
    hov." . $customfields['Correspondentienaam_First']['column_name'] . ",
    hov." . $customfields['Begindatum_HOV']['column_name'] . ",
    hov." . $customfields['Einddatum_HOV']['column_name'] . "
    FROM civicrm_contact AS contact
    LEFT JOIN " . $customgroup['table_name'] . " as hov ON contact.id = hov.entity_id
    WHERE contact.contact_type = %1 AND hov." . $customfields['Einddatum_HOV']['column_name']. " < %2
    ORDER BY contact.id ASC
  ";
  $params = array( 
      1 => array('Household', 'String'),
      2 => array(date('Y-m-d H:m:i'), 'String'),
  );
  $dao = CRM_Core_DAO::executeQuery($query, $params);
    
  while ($dao->fetch()) {    
    $return['message'][] = ts('Next household with id \'' . $dao->id);
    if($debug){
      echo '<br/>' . PHP_EOL;
      echo ts('Next household with id \'' . $dao->id . '\'') . '<br/>' . PHP_EOL;
    }
      
    /**
     * Sometimes the address of the HOV is like Adriaan Pauwstraat,15,7331 NH,Apeldoorn
     * We have to convert it to Adriaan Pauwstraat 15 to compare it with the street address
     */
    $VGE_adres_First = explode(',', $dao->{$customfields['VGE_adres_First']['column_name']});
    $vge_adres = $VGE_adres_First[0];
    // if there is a house number
    if(isset($VGE_adres_First[1]) and !empty($VGE_adres_First[1])){
      $vge_adres .= ' ' . $VGE_adres_First[1];
    }
        
    // delete household vge adres
    $addresses = CRM_Utils_DgwUtils::getAddress(['contact_id' => $dao->id, 'location_type_id' => $locationtype['id']], 'get');
    foreach($addresses['values'] as $address){
      // delete if household vge_adres_first_7 is the same as the hoofdhuurder street_address
      if($vge_adres == $address['street_address']){
        $return['message'][] = ts('Household with id \'' . $dao->id . '\', the address with street address \'' . $address['street_address'] . '\' with address id \'' . $address['id'] . '\', the address is deleted !');
        if($debug){
          echo ts('Household with id \'' . $dao->id . '\', the address with street address \'' . $address['street_address'] . '\' with address id \'' . $address['id'] . '\', the address is deleted !') . '<br/>' . PHP_EOL;
        }
        if(!$debug){
          CRM_Utils_DgwUtils::deleteAddress($address['id']);
        }
      }
    }
    
    // get hoofdhuurders
    $hoofdhuurders = CRM_Utils_DgwUtils::getHoofdhuurders($dao->id, false);
    foreach($hoofdhuurders as $hoofdhuurder){
      $return['message'][] = ts('Next hoofdhuurder of household with id \'' . $dao->id . '\' the with id \'' . $hoofdhuurder['contact_id']);
      if($debug){
        echo ts('Next hoofdhuurder of household with id \'' . $dao->id . '\' the with id \'' . $hoofdhuurder['contact_id'] . '\'') . '<br/>' . PHP_EOL;
      }
      $addresses = CRM_Utils_DgwUtils::getAddress(['contact_id' => $hoofdhuurder['contact_id'], 'location_type_id' => $locationtype['id']], 'get');
      foreach($addresses['values'] as $address){
        // delete if household vge_adres_first_7 is the same as the hoofdhuurder street_address
        if($vge_adres == $address['street_address']){
          $return['message'][] = ts('Household with id \'' . $dao->id . '\', there the hoofdhuurder with id \'' . $hoofdhuurder['contact_id'] . '\' the address with street address \'' . $address['street_address'] . '\' with address id \'' . $address['id'] . '\', the address is deleted !');
          if($debug){
            echo ts('Household with id \'' . $dao->id . '\', there the hoofdhuurder with id \'' . $hoofdhuurder['contact_id'] . '\' the address with street address \'' . $address['street_address'] . '\' with address id \'' . $address['id'] . '\', the address is deleted !') . '<br/>' . PHP_EOL;
          }
          if(!$debug){
            CRM_Utils_DgwUtils::deleteAddress($address['id']);
          }
        }
      }
    }
    
    // get medehuurders
    $medehuurders = CRM_Utils_DgwUtils::getMedeHuurders($dao->id, false);
    foreach($medehuurders as $medehuurder){     
      $return['message'][] = ts('Next medehuurder of household with id \'' . $dao->id . '\' the with id \'' . $medehuurder['medehuurder_id']);
      if($debug){
        echo ts('Next medehuurder of household with id \'' . $dao->id . '\' the with id \'' . $medehuurder['medehuurder_id'] . '\'') . '<br/>' . PHP_EOL;
      }
      $addresses = CRM_Utils_DgwUtils::getAddress(['contact_id' => $medehuurder['medehuurder_id'], 'location_type_id' => $locationtype['id']], 'get');
      foreach($addresses['values'] as $address){
        // delete if household vge_adres_first_7 is the same as the medehuurder street_address
        if($vge_adres == $address['street_address']){
          $return['message'][] = ts('Household with id \'' . $dao->id . '\', there the medehuurder with id \'' . $medehuurder['medehuurder_id'] . '\' the address with street address \'' . $address['street_address'] . '\' with address id \'' . $address['id'] . '\', the address is deleted !');
          if($debug){
            echo ts('Household with id \'' . $dao->id . '\', there the medehuurder with id \'' . $medehuurder['medehuurder_id'] . '\' the address with street address \'' . $address['street_address'] . '\' with address id \'' . $address['id'] . '\', the address is deleted !') . '<br/>' . PHP_EOL;
          }
          if(!$debug){
            CRM_Utils_DgwUtils::deleteAddress($address['id']);
          }
        }
      }
    }
  }
  
  return $return;
}

function vge_adres_removevgehovended_hov_organization($debug){
  $return['is_error'] = false;
  $return['message'] = [];
  
  $locationtype = CRM_Utils_DgwUtils::getLocationType(['name' => 'VGEadres'], 'getsingle');
  if(!$locationtype){
    $return['error_message'] = sprintf('Cannot get the location_type_id of the VGE adres');
    if($debug){
      echo sprintf('Cannot get the location_type_id of the VGE adres') . '<br/>' . PHP_EOL;
    }
    $return['is_error'] = true;
    return $return;
  }
  
  $customgroup = CRM_Utils_DgwUtils::getCustomGroup(['name' => 'Huurovereenkomst (organisatie)'], 'getsingle'); 
  if(!$customgroup){
    $return['error_message'] = sprintf('Cannot get the custom_group_id of the Huurovereenkomst (organistatie)');
    if($debug){
      echo sprintf('Cannot get the custom_group_id of the Huurovereenkomst (organistatie)') . '<br/>' . PHP_EOL;
    }
    $return['is_error'] = true;
    return $return;
  }
  
  $customfield = CRM_Utils_DgwUtils::getCustomFieldAll(['custom_group_id' => $customgroup['id']]);
  if(!$customfield){
    $return['error_message'] = sprintf('Cannot get all the custom fields of the Huurovereenkomst (organistatie)');
    if($debug){
      echo sprintf('Cannot get all the custom fields of the Huurovereenkomst (organistatie)') . '<br/>' . PHP_EOL;
    }
    $return['is_error'] = true;
    return $return;
  }
  
  $customfields = [];
  foreach($customfield['values'] as $field){
    $customfields[$field['name']] = $field;
  }
  
  $query = "SELECT contact.id, hov.entity_id,
    hov." . $customfields['hov_nummer']['column_name'] . ",
    hov." . $customfields['vge_nummer']['column_name'] . ",
    hov." . $customfields['vge_adres']['column_name'] . ",
    hov." . $customfields['naam_op_overeenkomst']['column_name'] . ",
    hov." . $customfields['begindatum_overeenkomst']['column_name'] . ",
    hov." . $customfields['einddatum_overeenkomst']['column_name'] . "
    FROM civicrm_contact AS contact
    LEFT JOIN " . $customgroup['table_name'] . " as hov ON contact.id = hov.entity_id
    WHERE contact.contact_type = %1 AND hov." . $customfields['einddatum_overeenkomst']['column_name']. " < %2
    ORDER BY contact.id ASC
  ";
  $params = array( 
      1 => array('Organization', 'String'),
      2 => array(date('Y-m-d H:m:i'), 'String'),
  );
  $dao = CRM_Core_DAO::executeQuery($query, $params);
    
  while ($dao->fetch()) {
    $return['message'][] = ts('Next organization with id \'' . $dao->id);
    if($debug){
      echo '<br/>' . PHP_EOL;
      echo ts('Next organization with id \'' . $dao->id . '\'') . '<br/>' . PHP_EOL;
    }
    
    /**
     * Sometimes the address of the HOV is like Adriaan Pauwstraat,15,7331 NH,Apeldoorn
     * We have to convert it to Adriaan Pauwstraat 15 to compare it with the street address
     */
    $vge_adres_first = explode(',', $dao->{$customfields['vge_adres']['column_name']});
    $vge_adres = $vge_adres_first[0];
    // if there is a house number
    if(isset($vge_adres_first[1]) and !empty($vge_adres_first[1])){
      $vge_adres .= ' ' . $vge_adres_first[1];
    }
    
    // delete household vge adres
    $addresses = CRM_Utils_DgwUtils::getAddress(['contact_id' => $dao->id, 'location_type_id' => $locationtype['id']], 'get');
    foreach($addresses['values'] as $address){
      // delete if household vge_adres_first_7 is the same as the hoofdhuurder street_address
      if($vge_adres[0] == $address['street_address']){
        $return['message'][] = ts('Organization with id \'' . $dao->id . '\', the address with street address \'' . $address['street_address'] . '\' with address id \'' . $address['id'] . '\', the address is deleted !');
        if($debug){
          echo ts('Organization with id \'' . $dao->id . '\', the address with street address \'' . $address['street_address'] . '\' with address id \'' . $address['id'] . '\', the address is deleted !') . '<br/>' . PHP_EOL;
        }
        if(!$debug){
          CRM_Utils_DgwUtils::deleteAddress($address['id']);
        }
      }
    }
  }
  
  return $return;
}