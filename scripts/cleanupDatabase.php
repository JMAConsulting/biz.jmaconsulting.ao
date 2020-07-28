<?php

Class CRM_Cleanup_Database {

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

  function dropViews() {
    $domain = new CRM_Core_DAO_Domain();
    $domain->id = CRM_Core_Config::domainID();
    $domain->find(TRUE);
    $locales = ['fr_FR'];
    $existingLocales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);
    foreach ($existingLocales as $k => $locale) {
      if (in_array($locale, $locales)) {
        unset($existingLocales[$k]);
      }
    }
    $domain->locales = implode(CRM_Core_DAO::VALUE_SEPARATOR, $existingLocales);
    $domain->save();

    $tables = CRM_Core_I18n_SchemaStructure::tables();
    $columns =& CRM_Core_I18n_SchemaStructure::columns();
    $indices =& CRM_Core_I18n_SchemaStructure::indices();
    $queries = [];
    $dropQueries = [];
    foreach ($tables as $table) {

      // drop indices
      if (isset($indices[$table])) {
        foreach ($indices[$table] as $index) {
          foreach ($locales as $loc) {
            if (CRM_Core_BAO_SchemaHandler::checkIfIndexExists($table, "{$index['name']}_{$loc}")) {
              $queries[] = "DROP INDEX {$index['name']}_{$loc} ON {$table}";
            }
          }
        }
      }

      $dao = new CRM_Core_DAO();
      // deal with columns
      foreach ($columns[$table] as $column => $type) {
        foreach ($locales as $loc) {
          $dropQueries[] = "ALTER TABLE {$table} DROP IF EXISTS {$column}_{$loc}";
        }
      }

      // drop views
      foreach ($locales as $loc) {
        $queries[] = "DROP VIEW IF EXISTS {$table}_{$loc}";
      }
    }

    // execute the queries without i18n rewriting
    $dao = new CRM_Core_DAO();
    foreach ($queries as $query) {
      $dao->query($query, FALSE);
    }

    foreach ($dropQueries as $query) {
      $dao->query($query, FALSE);
    }
  }

  // now lets rebuild all triggers
  CRM_Core_DAO::triggerRebuild();
}

$script = new CRM_Cleanup_Database();
$script->dropViews();
