<?php

Class CRM_ROCR_Import {

  //public $civicrmPath = '/var/www/jma.staging.autismontario.com/htdocs/vendor/civicrm/civicrm-core/';
  public $civicrmPath = '/home/edsel/public_html/test/sites/all/modules/civicrm/';
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

  function rocrImport() {
    // Custom Fields
    $chapter = civicrm_api3('CustomField', 'getvalue', array(
      'name' => 'Chapter',
      'custom_group_id' => 'chapter_region',
      'return' => 'id',
    ));
    $region = civicrm_api3('CustomField', 'getvalue', array(
      'name' => 'Region',
      'custom_group_id' => 'chapter_region',
      'return' => 'id',
    ));
    $rocrId = civicrm_api3('CustomField', 'getvalue', array(
      'name' => 'ROCR_External_ID',
      'return' => 'id',
    ));
    $firstContact = civicrm_api3('CustomField', 'getvalue', array(
      'name' => 'First_Contacted',
      'return' => 'id',
    ));
    $consLanguage = civicrm_api3('CustomField', 'getvalue', array(
      'name' => 'Cons_Language',
      'return' => 'id',
    ));
    $consLanguageOther = civicrm_api3('CustomField', 'getvalue', array(
      'name' => 'Cons_Language_Other',
      'return' => 'id',
    ));
    $consCompleted = civicrm_api3('CustomField', 'getvalue', array(
      'name' => 'Cons_Completed',
      'return' => 'id',
    ));
    $importSource = civicrm_api3('CustomField', 'getvalue', array(
      'name' => 'Import_Source',
      'return' => 'id',
    ));
    $sql = "SELECT * FROM rocr WHERE FamilyID = 'ES-65307'";
    $dao = CRM_Core_DAO::executeQuery($sql);

    while ($dao->fetch()) {
      $params = [
        'first_name' => $dao->FIChildFirstName,
        'last_name' => $dao->FIChildSurname,
        'contact_type' => 'Individual',
        'contact_sub_type' => 'Child',
      ];
      $childBirthDate = $dao->FIBirthMonth . '/' . $dao->FIBirthDay . '/' . $dao->FIBirthYear;
      if ($dao->FIBirthMonth && $dao->FIBirthDay && $dao->FIBirthYear) {
        $params['birth_date'] = date('m/d/Y', strtotime($childBirthDate));
      }
      $ruleType = 'First_Last_BirthDate_9';
      $cid = $this->checkDupes($params, $ruleType);
      if ($cid) {
        $params['contact_id'] = $cid;
        $par = [
          1 => [$dao->FamilyID, 'String'],
          2 => ['Child', 'String'],
          3 => [$dao->FIChildFirstName, 'String'],
          4 => [$dao->FIChildSurname, 'String'],
        ];
        CRM_Core_DAO::executeQuery("INSERT INTO rocrdupes (familyid, type, first_name, last_name) VALUES (%1, %2, %3, %4)", $par);
      }
      $params['custom_' . $rocrId] = $dao->FamilyID;
      $params['custom_' . $chapter] = $this->getChapter($dao->RCPChapter);
      $params['custom_' . $region] = $this->getRegion($dao->RCPRegion);
      $params['custom_' . $firstContact] = date('m/d/Y', strtotime($dao->DateOfFirstContact));
      $params['custom_' . $consCompleted] = date('m/d/Y', strtotime($dao->ConsCompletedDate));
      $params['custom_' . $consLanguage] = $dao->ConsLanguage;
      $params['custom_' . $consLanguageOther] = $dao->ConsLanguageOther;
      $params['custom_' . $importSource] = "ROCR";
      $child = civicrm_api3('Contact', 'create', $params);

      // Parent
      $parentParams = [
        'first_name' => $dao->FIFName,
        'last_name' => $dao->FISurname,
        'email' => $dao->FIEmail,
        'contact_type' => 'Individual',
        'custom_' . $importSource => "ROCR",
        'custom_' . $rocrId => $dao->FamilyID,
      ];
      $ruleType = 'First_Last_Email_10';
      $pCid = $this->checkDupes($parentParams, $ruleType);
      if ($pCid) {
        $parentParams['contact_id'] = $pCid;
        $par = [
          1 => [$dao->FamilyID, 'String'],
          2 => ['Parent', 'String'],
          3 => [$dao->FIFName, 'String'],
          4 => [$dao->FISurname, 'String'],
        ];
        CRM_Core_DAO::executeQuery("INSERT INTO rocrdupes (familyid, type, first_name, last_name) VALUES (%1, %2, %3, %4)", $par);

      }
      $parent = civicrm_api3('Contact', 'create', $parentParams);
      if ($child['id'] && $parent['id']) {
        $this->addRelationship($child['id'], $parent['id'], "Child of");
      }

      if (!empty($parent['id'])) {
        // Phones
        if ($dao->FIHomeTel) {
          civicrm_api3('Phone', 'create', [
            "phone" => $dao->FIHomeTel,
            "location_type_id" => "Home",
            "contact_id" => $parent['id'],
          ]);
        }
        if ($dao->FIMobile) {
          civicrm_api3('Phone', 'create', [
            "phone" => $dao->FIMobile,
            "location_type_id" => "Home",
            "phone_type_id" => "Mobile",
            "contact_id" => $parent['id'],
          ]);
        }
        if ($dao->FIWorkTel) {
          civicrm_api3('Phone', 'create', [
            "phone" => $dao->FIWorkTel,
            "location_type_id" => "Work",
            "contact_id" => $parent['id'],
          ]);
        }

        // Address
        $address = civicrm_api3('Address', 'create', [
          'street_address' => $dao->FIStreet,
          'city' => $dao->FICity,
          'postal_code' => $dao->FIPostalCode,
          'location_type_id' => "Home",
          'contact_id' => $parent['id'],
        ]);

        // Share this address with the child.
        if (!empty($address['id']) && !empty($child['id'])) {
          civicrm_api3('Address', 'create', [
            'location_type_id' => "Home",
            'contact_id' => $child['id'],
            'master_id' => $address['id'],
          ]);
        }
      }

      for ($i = 1; $i <= 3; $i++) {
        // Create siblings.
        $name = "FISiblingName$i";
        $dob = "FISiblingDOB$i";
        if (!empty($dao->$name)) {
          $sName = explode(' ', $dao->$name);
          if (empty($sName[1])) {
            $lName = $dao->FISurname;
          }
          else {
            $lName = $sName[1];
          }
          $siblingParams = [
            'first_name' => $sName[0],
            'last_name' => $lName,
            'contact_type' => 'Individual',
            'contact_sub_type' => 'Child',
            'custom_' . $importSource => "ROCR",
            'custom_' . $rocrId => $dao->FamilyID,
          ];
          if (!empty($dao->$dob)) {
            $siblingParams['birth_date'] = date('m/d/Y', strtotime($dao->$dob));
          }
          $ruleType = 'First_Last_BirthDate_8';
          $s = $this->checkDupes($siblingParams, $ruleType);
          if ($s) {
            $siblingParams['contact_id'] = $s;
            $par = [
              1 => [$dao->FamilyID, 'String'],
              2 => ['Sibling', 'String'],
              3 => [$sName[0], 'String'],
              4 => [$lName, 'String'],
            ];
            CRM_Core_DAO::executeQuery("INSERT INTO rocrdupes (familyid, type, first_name, last_name) VALUES (%1, %2, %3, %4)", $par);
          }
          $sibling = civicrm_api3('Contact', 'create', $siblingParams);
          if (!empty($child['id'])) {
            $this->addRelationship($child['id'], $sibling['id'], "Sibling of");
          }
        }
      }
    }
  }

  protected function getChapter($chapter) {
    switch ($chapter) {
    case 'Chatham-Kent':
      $chapter = "Chatham Kent";
      break;
    case 'Durham':
      $chapter = "Durham Region";
      break;
    case 'Grey-Bruce':
      $chapter = "Grey Bruce";
      break;
    case 'Huron-Perth':
      $chapter = "Huron Perth";
      break;
    case 'North-Bay':
      $chapter = "North Bay";
      break;
    case 'Niagara':
      $chapter = "Niagara Region";
      break;
    case 'Sarnia Lambton':
      $chapter = "Sarnia-Lambton";
      break;
    case 'Sault Ste Marie':
      $chapter = "Sault Ste. Marie";
      break;
    case 'Simcoe':
      $chapter = "Simcoe County";
      break;
    case 'Thunder-Bay':
      $chapter = "Thunder Bay";
      break;
    case 'Upper-Canada':
      $chapter = "Upper Canada";
      break;
    case 'Wellington':
      $chapter = "Wellington County";
      break;
    }
    return $chapter;
  }

  protected function getRegion($region) {
    switch ($region) {
    case 'ES':
    case 'SE':
      $region = "East/South-East";
      break;
    case 'CE':
      $region = "Central-East";
      break;
    case 'CW':
      $region = "Central-West";
      break;
    case 'ES':
    case 'Eastern':
      $region = "Eastern";
      break;
    case 'HN':
      $region = "Hamilton-Niagara";
      break;
    case 'NE':
      $region = "North-East";
      break;
    case 'NO':
      $region = "Northern";
      break;
    case 'TO':
      $region = "Toronto";
      break;
    case 'Provincial':
      $region = "Provincial";
      break;
    case 'SW':
      $region = "South-West";
      break;
    }
    return $region;
  }

  protected function checkDupes($params, $ruleType) {
    $dedupeParams = CRM_Dedupe_Finder::formatParams($params, 'Individual');
    $dedupeParams['check_permission'] = FALSE;
    $rule = CRM_Core_DAO::singleValueQuery("SELECT max(id) FROM civicrm_dedupe_rule_group WHERE name = '" . $ruleType . "'");
    $dupes = CRM_Dedupe_Finder::dupesByParams($dedupeParams, "Individual", NULL, array(), $rule);
    $cid = CRM_Utils_Array::value('0', $dupes, NULL);
    return $cid;
  }

  protected function addRelationship($contact_id_a, $contact_id_b, $type) {
    $relTypeId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_RelationshipType', $type, 'id', 'name_a_b');
    $relationshipParams = array(
      "contact_id_a" => $contact_id_a,
      "contact_id_b" => $contact_id_b,
      "relationship_type_id" => $relTypeId,
    );
    $relation = civicrm_api3("Relationship", "get", $relationshipParams);
    if ($relation['count'] == 0) {
      civicrm_api3("Relationship", "create", [
        "contact_id_a" => $contact_id_a,
        "contact_id_b" => $contact_id_b,
        "relationship_type_id" => $relTypeId,
        "skipRecentView" => TRUE,
      ]);
    }
  }

}

$import = new CRM_ROCR_Import();
$import->rocrImport();
