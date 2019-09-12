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
    $sql = "SELECT id, data FROM `civicrm_batch` WHERE type_id = 1 AND MONTH(created_date) > 6 AND YEAR(created_date) = 2019 AND data LIKE '%payment_instrument\":\"7\"%'";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $count = 0;
    while ($dao->fetch()) {
      $data = json_decode($dao->data, TRUE)['values'];

      $sql = "SELECT GROUP_CONCAT(DISTINCT cc.id)
        FROM `civicrm_batch` b
        LEFT JOIN civicrm_entity_batch eb ON eb.batch_id = b.id AND eb.entity_table = 'civicrm_financial_trxn'
        LEFT JOIN civicrm_entity_financial_trxn eft ON eft.financial_trxn_id = eb.entity_id AND eft.entity_table = 'civicrm_contribution'
        LEFT JOIN civicrm_contribution cc ON cc.id = eft.entity_id AND cc.payment_instrument_id = 7
       WHERE b.id = $dao->id AND cc.id IS NOT NULL
       ";
      $contributionIDs = explode(', ', CRM_Core_DAO::singleValueQuery($sql));

      foreach ($data['field'] as $key => $param) {
        $p = [
          'financial_type' => $param['financial_type'],
          'total_amount' => $param['total_amount'],
          'payment_instrument_id' => 7,
          'contact_id' => $data['primary_contact_id'][$key],
          'sequential' => 1,
        ];
        $contributions = civicrm_api3('Contribution', 'get', $p)['values'];
        print_r($contributionIDs);
        foreach ($contributions as $contribution) {
          if (in_array($contribution['id'], $contributionIDs)) {
            $p = [
              'id' => $contribution['id'],
              'check_number' => $param['contribution_check_number'],
            ];
            civicrm_api3('Contribution', 'create', $p);
            $count++;
          }
        }
      }
    }
    print_r('Contribution found : ' . $count);
  }

}

$import = new CRM_ROCR_Import();
//$import->rocrImport();
$import->fixCheques();
