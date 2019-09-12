<?php

Class CRM_Missing_Cheque {

  public $civicrmPath = '/var/www/autismontario.com/htdocs/vendor/civicrm/civicrm-core/';
  //public $civicrmPath = '/home/edsel/public_html/test/sites/all/modules/civicrm/';
  public $sourceContactId = '';

  function __construct() {
    // you can run this program either from an apache command, or from the cli
    $this->initialize();
  }

  function initialize() {
    $civicrmPath = $this->civicrmPath;
    require_once $civicrmPath .'civicrm.config.php';
    require_once $civicrmPath .'CRM/Core/Config.php';
    $config = CRM_Core_Config::singleton();
  }

  function fixCheques() {
    $sql = "SELECT data FROM `civicrm_batch` WHERE type_id = 1 AND MONTH(created_date) > 6 AND YEAR(created_date) = 2019 AND data LIKE '%payment_instrument\":\"7\"%'";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $count = 0;
    while ($dao->fetch()) {
      $data = json_decode($dao->data, TRUE)['values'];
      foreach ($data['field'] as $key => $param) {
        $contribution = civicrm_api3('Contribution', 'get', array_merge($param, [
          'contact_id' => $data['primary_contact_id'][$key],
          'sequential' => 1,
        ]))['values'];
        if (!empty($contribution[0] && !empty($contribution[0]['id']))) {
          print_r($contribution[0]['id']);
          civicrm_api3('Contribution', 'create', [
            'id' => $contribution[0]['id'],
            'check_number' => $param['contribution_check_number'],
          ]);
          $count++;
        }
      }
    }
    print_r('Contribution found : ' . $count);
  }

}

$import = new CRM_ROCR_Import();
//$import->rocrImport();
$import->fixCheques();
