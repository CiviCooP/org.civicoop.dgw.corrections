<?php

/**
 * Job.UpdateGreeting API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_job_updategreeting_spec(&$spec) {
}

/**
 * Job.UpdateGreeting API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_job_updategreeting($params) {
  $contactType = $params['ct'];
  $greeting    = $params['gt'];
  $limit = CRM_Utils_Array::value('limit', $params);
  
  $contactType = trim($contactType);
  $greeting = trim($greeting);
  $limit = trim($limit);
  
  $error = false;
  
  if(empty($contactType)){
    echo('Parameter ct (contact type) is empty !') . '<br/>' . PHP_EOL;
    $error = true;
  }
  
  if(empty($contactType)){
    echo('Parameter gt (Greeting type) is empty !') . '<br/>' . PHP_EOL;
    $error = true;
  }
  
  if(empty($limit)){
    echo('Parameter limit is empty !') . '<br/>' . PHP_EOL;
    $error = true;
  }
  
  if($error){
    Echo('Er is een error in de parameters !') . '<br/>' . PHP_EOL;
    return false;
  }
  
  $idFldName = $displayFldName = NULL;
  if (in_array($greeting, CRM_Contact_BAO_Contact::$_greetingTypes)) {
    $idFldName = $greeting . '_id';
    $displayFldName = $greeting . '_display';
  }

  if ($idFldName) {
    $queryParams = array(1 => array($contactType, 'String'));
    
    $sql = "
    SELECT DISTINCT id, $idFldName, middle_name, last_name, gender_id
    FROM civicrm_contact
    WHERE contact_type = %1
    AND ({$idFldName} IS NULL
    OR ( {$idFldName} IS NOT NULL AND ({$displayFldName} IS NULL OR {$displayFldName} = '')) )";
    
    if ($limit) {
      $sql .= " LIMIT 0, %2";
      $queryParams += array(2 => array($limit, 'Integer'));
    }
    
    $dao = CRM_Core_DAO::executeQuery($sql, $queryParams);
    while ($dao->fetch()) {
      $prefix_id = 0;
      if ( $dao->gender_id == 1 ) {
          $prefix_id = 1;
      }
      if ( $dao->gender_id == 2 ) {
          $prefix_id = 2;
      }
      require_once 'CRM/Utils/DgwUtils.php';
      $displayGreetings = CRM_Utils_DgwUtils::setDisplayGreetings( $dao->gender_id, $dao->middle_name, $dao->last_name );
      
      $greetings = "";
      if ( isset( $displayGreetings['is_error'] ) ) {
        if ( $displayGreetings['is_error'] == 0 ) {
          if ( isset( $displayGreetings['greetings'] ) ) {
            /*
             * BOS1403421 add escapeString
             */
            $greetings = CRM_Core_DAO::escapeString($displayGreetings['greetings']);
          }
        }
      }
      
      $updContact = "UPDATE civicrm_contact set prefix_id = $prefix_id, ";
      $updContact .= "email_greeting_display = '$greetings', addressee_display = '$greetings', ";
      $updContact .= "postal_greeting_display = '$greetings' WHERE id = $dao->id";
      CRM_Core_DAO::executeQuery( $updContact );
    }
  }
  
  return true;
}

