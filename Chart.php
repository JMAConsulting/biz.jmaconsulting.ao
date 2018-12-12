<?php

Class CRM_Account_Import {

  public $civicrmPath = '/var/www/jma.staging.autismontario.com/htdocs/vendor/civicrm/civicrm-core/';

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

  function accountImport() {
    $sql = "SELECT `Description` as name, `Account Type` as financial_account_type_id, `Account Number` as accounting_code, `Type` as account_type_code FROM accounts";
    $dao = CRM_Core_DAO::executeQuery($sql);

    while ($dao->fetch()) {
      if ($dao->financial_account_type_id == "EXPENSE") {
        $dao->financial_account_type_id = "Expenses";
      }
      $dao->financial_account_type_id = ucfirst($dao->financial_account_type_id);
      $account = civicrm_api3('FinancialAccount', 'get', [
        'name' => $dao->name,
      ]);
      if ($account['count'] < 1) {
        civicrm_api3('FinancialAccount', 'create', [
          'name' => $dao->name,
          'financial_account_type_id' => $dao->financial_account_type_id,
          'accounting_code' => $dao->accounting_code,
          'account_type_code' => $dao->account_type_code,
          'is_active' => 1,
          'contact_id' => 1,      
        ]);
      }
    }
  }

 
}

$import = new CRM_Account_Import();
$import->accountImport();
