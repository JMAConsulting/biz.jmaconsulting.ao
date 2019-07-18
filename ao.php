<?php

require_once 'ao.variables.php';
require_once 'ao.civix.php';

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function ao_civicrm_config(&$config) {

  CRM_Core_Resources::singleton()->addStyleFile('biz.jmaconsulting.ao', 'css/aostyle.css');
  _ao_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function ao_civicrm_xmlMenu(&$files) {
  _ao_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function ao_civicrm_install() {
  civicrm_api3('Navigation', 'create', array(
    'label' => ts('Membership to Contribution Page mapping', array('domain' => 'biz.jmaconsulting.ao')),
    'name' => 'membership_type_mapping',
    'url' => 'civicrm/membership-type/mapping?reset=1',
    'domain_id' => CRM_Core_Config::domainID(),
    'is_active' => 1,
    'parent_id' => civicrm_api3('Navigation', 'getvalue', array(
      'return' => "id",
      'name' => "CiviMember",
    )),
    'permission' => 'administer CiviCRM',
  ));

  CRM_Core_BAO_Navigation::resetNavigation();
  _ao_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function ao_civicrm_uninstall() {
  $id = civicrm_api3('Navigation', 'getvalue', array(
    'return' => "id",
    'name' => 'membership_type_mapping',
  ));
  if ($id) {
    civicrm_api3('Navigation', 'delete', array('id' => $id));
  }
  CRM_Core_BAO_Navigation::resetNavigation();
  _ao_civix_civicrm_uninstall();
}

function ao_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  if ($formName == "CRM_Activity_Form_Activity" && (in_array($form->_action, [CRM_Core_Action::ADD, CRM_Core_Action::UPDATE]))) {
    if ($fields['activity_type_id'] == 70) {
      $sourceContact = $fields['source_contact_id'];
      $relTypeId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_RelationshipType', 'Parent of', 'id', 'name_b_a');
      $relationship = civicrm_api3('Relationship', 'get', [
        'contact_id_b' => $sourceContact,
        'relationship_type_id' => $relTypeId,
        'return' => ['is_active'],
      ]);
      $isActive = FALSE;
      if ($relationship['count'] >= 1) {
        foreach ($relationship['values'] as $id => $value) {
          if ($value['is_active']) {
            $isActive = TRUE;
            break;
          }
        }
      }
      if (!$relationship['count'] || !$isActive) {
        $errors['source_contact_id'] = ts('This contact does not active an active Parent relationship');
      }
    }
    if (!empty($fields[CURRENT_NEEDS]['AdultNeeds'])) {
      if(count(array_filter($fields[ADULT_NEEDS])) == 0) {
        $errors[ADULT_NEEDS] = ts('Please specify one of Adult Needs options');
      }
    }
    if (!empty($fields[CURRENT_NEEDS]['AOinfo'])) {
      if(count(array_filter($fields[AO_INFO])) == 0) {
        $errors[AO_INFO] = ts('Please specify one of AO info options');
      }
    }
    if (!empty($fields[CURRENT_NEEDS]['School'])) {
      if(count(array_filter($fields[SCHOOL])) == 0) {
        $errors[SCHOOL] = ts('Please specify one of School options');
      }
    }
    if (empty($fields['target_contact_id'])) {
      $errors['target_contact_id'] = ts('With Contact is a required field.');
    }
  }
}

function ao_civicrm_pageRun(&$page) {
  if ($page->getVar('_name') == 'CRM_Contact_Page_DashBoard') {
    $items = CRM_Core_BAO_Dashboard::getContactDashletsForJS();
    try {
      $item = CRM_Utils_Array::value(0, civicrm_api3('Dashboard', 'get', ['id' => 11, 'sequential' => 1])['values']);
      $items[1][] = array(
        'id' => $item['id'],
        'name' => $item['name'],
        'title' => $item['label'],
        'url' => CRM_Core_BAO_Dashboard::parseUrl($item['url']),
        'cacheMinutes' => $item['cache_minutes'],
        'fullscreenUrl' => CRM_Core_BAO_Dashboard::parseUrl($item['fullscreen_url']),
      );
      $page->assign('contactDashlets', $items);
    }
    catch (API_Exception $e) {
    }
  }
  if ('CRM_Contribute_Page_ContributionPage' == $page->getVar('_name')) {
    Civi::resources()->addScript("
    CRM.$(function($) {
      $('.btn-slide').attr('style', 'padding-right:15px !important;');
      $('.btn-slide').css('text-indent', 'initial');
      $('.btn-slide').css('width', 'auto');
    });
    ", -100, 'html-header');
  }
}

function ao_civicrm_alterReportVar($type, &$columns, &$form) {
  if ('CRM_Report_Form_Activity' == get_class($form)) {
    if ($type == 'columns') {
      $columns['civicrm_activity_contact']['filters']['record_type_id'] = array(
        'name' => 'record_type_id',
        'title' => ts('Record type'),
        'type' => CRM_Utils_Type::T_INT,
        'operatorType' => CRM_Report_Form::OP_SELECT,
        'options' => ['' => '- select -' ] + CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate'),
      );
    }
    if ($type == 'sql' && CRM_Utils_Array::value("current_record_type_value", $form->getVar('_params')) == 1) {
      $contactID = CRM_Core_Session::singleton()->get('userID');
      $match = "contact_id = " . $contactID;
      $replace = $match . " AND record_type_id = 1";
      foreach ($form->sqlFormattedArray as $key => $sql) {
        $form->sqlFormattedArray[$key] = str_replace($match, $replace, $sql);
      }
    }
  }
  if ('CRM_Report_Form_Contribute_BatchDetail' == get_class($form)) {
    if ($type == 'columns') {
      $columns['civicrm_financial_trxn']['filters']['to_financial_account_id'] = array(
        'name' => 'to_financial_account_id',
        'title' => ts('Deposit Account'),
        'type' => CRM_Utils_Type::T_INT,
        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
        'options' => ['' => '- select -' ] + CRM_Contribute_PseudoConstant::financialAccount(),
      );
    }
  }
}

function ao_civicrm_links($op, $objectName, $objectId, &$links, &$mask, &$values) {
  if ($objectName == 'ContributionPage' && $op == 'contributionpage.configure.actions') {
    foreach ($links as &$link) {
      if (empty($link['class'])) {
        continue;
      }
      foreach ($link['class'] as $key => $class) {
        if ($class == 'disabled') {
          unset($link['class'][$key]);
        }
      }
    }
  }
  elseif ($objectName == 'Contribution' && $op == 'contribution.selector.row') {
    if (!in_array('civicrm/payment', CRM_Utils_Array::collect('url', $links))) {
      $links[] = [
        'name' => 'Record Refund',
        'url' => 'civicrm/backoffice/refund',
        'qs' => 'reset=1&id=%%id%%&cid=%%cid%%&action=add&component=%%cxt%%',
        'title' => ts('Record Refund'),
        'bit' => 1,
      ];
    }
  }
}

function getMemberID() {
  $memberID = CRM_Core_DAO::singleValueQuery("select max(membership_number_758) from civicrm_value_member_genera_9");
  $memberID = $memberID + 1;
  return $memberID;
}

function ao_civicrm_post( $op, $objectName, $objectId, &$objectRef ) {
  if ($objectName == "Membership" && $op == "create") {
    civicrm_api3('CustomValue', 'create', array('entity_id' => $objectId, 'custom_758' => getMemberID()));
  }
}

function ao_civicrm_postSave_civicrm_membership($dao) {
 // civicrm_api3('CustomValue', 'create', array('entity_id' => $dao->id, 'custom_758' => getMemberID()));
}

function ao_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Contribute_Form_Contribution_Main') {
    CRM_Core_Resources::singleton()->addScript(
      "CRM.$(function($) {
        $('#editrow-" . CNAME_MATCH_GIFT . "').toggle(($(\"input[name='" . C_MATCH_GIFT . "']:checked\").val() == 1));
        $('input[name=\"" . C_MATCH_GIFT . "\"]').on('click', function() {
          var value = $(this).val();
          $('#editrow-" . CNAME_MATCH_GIFT . "').toggle((value == 1));
        });
        $('#editrow-" . CONTRI_MAIL_MATCH . "').hide();
        $('input[name=\"" . CONTACT_MAIL_MATCH . "\"').on('click', function() {
          var value = $(this).val();
          $.each($('input[name=\"" . CONTRI_MAIL_MATCH . "\"'), function() {
            if (value == $(this).val()) {
              $(this).prop('checked', true);
            }
          });
        });
      });"
    );
  }
  if ($formName == 'CRM_Contribute_Form_ContributionView' && ($contributionID = CRM_Utils_Array::value('id', $_GET))) {
    return;
    $string = '&nbsp;&nbsp;&nbsp;&nbsp;';
    foreach ([CHAPTER, GIFT_TYPE] as $customID) {
      $label = civicrm_api3('CustomField', 'getValue', ['id' => $customID, 'return' => 'label']);
      $value = CRM_Core_BAO_CustomField::displayValue(civicrm_api3('Contribution', 'getValue', ['id' => $contributionID, 'return' => 'custom_' . $customID]), $customID);
      $string .= "&nbsp;&nbsp;&nbsp;<span class=\"label\"><strong>$label</strong></span>:&nbsp;$value";
    }
    CRM_Core_Resources::singleton()->addScript(
      "CRM.$(function($) {
        $.each($('.crm-contribution-view-form-block table > tbody > tr:nth-child(2)'), function() {
          if ($('td', this).length == 2) {
            $('td:nth-child(2)', this).append('$string');
          }
        });
      });"
    );
  }
  if ($formName == 'CRM_Contribute_Form_Contribution') {
    CRM_Core_Region::instance('page-body')->add(array(
      'template' => 'CRM/AddBillingDetails.tpl',
    ));
    $defaultProfileID = civicrm_api3('uf_group', 'getvalue', [
      'name' => 'new_individual',
      'is_active' => 1,
      'return' => 'id',
    ]);
    CRM_Core_Resources::singleton()->addScript(
      "CRM.$(function($) {
        $('select[id^=\"soft_credit_type\"]').on('change', function() {
          var value = $(this).val(),
          index = 0;
          $.each(CRM.config.entityRef.contactCreate, function(k, v) {
            if (v.type == 'Individual') {
              index = k;
            }
          });
          if (value == 1 || value == 2) {
            CRM.config.entityRef.contactCreate[index].url = CRM.url('civicrm/profile/create?reset=1&context=dialog&gid=" . TRIBUTE_PROFILE_ID ."');
          }
          else {
            CRM.config.entityRef.contactCreate[index].url = CRM.url('civicrm/profile/create?reset=1&context=dialog&gid={$defaultProfileID}');
          }
        });
      });
    ");
  }
  if ($formName == 'CRM_Event_Form_Registration_Register') {
    if (array_key_exists(LANG_SPOKEN, $form->_fields) && array_key_exists(LANG_OTHER, $form->_fields)) {
      $form->assign('lang_spoken', LANG_SPOKEN);
      $form->assign('lang_other', LANG_OTHER);
    }
    CRM_Core_Region::instance('page-body')->add(array(
      'template' => 'CRM/Ao/Ao.tpl',
    ));
  }
  if ($formName == "CRM_Activity_Form_Activity") {
    CRM_Core_Region::instance('page-body')->add(array(
      'template' => 'CRM/Ao/ParentConsultation.tpl',
    ));
  }
  if ($formName == "CRM_Event_Form_Participant") {
    CRM_Core_Resources::singleton()->addScript(
      "CRM.$(function($) {
         $( document ).ajaxComplete(function( event, xhr, settings ) {
           $('.pay-later_info-section').css('margin-top', '45px');
           $('#email-receipt table').css('margin-top', '45px');
         });
      });"
    );
  }
  if ($formName == "CRM_Event_Form_Search") {
    $rows = CRM_Core_Smarty::singleton()->get_template_vars('rows');
    foreach ($rows as &$row) {
      $row['action'] = str_replace('Transfer or Cancel', 'Cancel', $row['action']);
    }
    CRM_Core_Smarty::singleton()->assign('rows', $rows);
  }
  if ($formName == "CRM_Event_Form_SelfSvcUpdate") {
    $form->add('select', 'action', ts('Cancel Registration?'), array(ts('-select-'), 2 => ts('Cancel')), TRUE);
  }
}

/**
 * Implementation of hook_civicrm_postProcess
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postProcess
 */
function ao_civicrm_postProcess($formName, &$form) {
  if ($formName == "CRM_Activity_Form_Activity") {
    if ($form->_activityTypeId == 70) {
      $sourceContact = $form->_submitValues['source_contact_id'];
      $relTypeId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_RelationshipType', 'Parent of', 'id', 'name_b_a');
      $relationship = CRM_Utils_Array::collect('contact_id_a', civicrm_api3('Relationship', 'get', [
        'contact_id_b' => $sourceContact,
        'relationship_type_id' => $relTypeId,
      ])['values']);
      if (!empty($relationship)) {
        $rels = implode(', ', $relationship);
        $isFilled = CRM_Core_DAO::executeQuery("SELECT entity_id FROM civicrm_value_newsletter_cu_3 WHERE entity_id IN ($rels) AND (first_contacted_358 IS NOT NULL OR first_contacted_358 != '')")
          ->fetchAll();
        if (empty($isFilled)) {
          foreach ($relationship as $child) {
            civicrm_api3('CustomValue', 'create', [
              'entity_id' => $child,
              'custom_358' => date('Ymd'),
            ]);
          }
        }
      }
    }
  }
  elseif(($formName == "CRM_Member_Form_Membership") && ($form->_action & CRM_Core_Action::ADD) && !empty($form->_id)) {
    civicrm_api3('CustomValue', 'create', array('entity_id' => $form->_id, 'custom_758' => getMemberID()));
  }
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function ao_civicrm_enable() {
  _ao_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function ao_civicrm_disable() {
  _ao_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function ao_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _ao_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function ao_civicrm_managed(&$entities) {
  _ao_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function ao_civicrm_caseTypes(&$caseTypes) {
  _ao_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function ao_civicrm_angularModules(&$angularModules) {
_ao_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function ao_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _ao_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

function ao_civicrm_permission(&$permissions) {
  $permissions += array(
    'process backoffice event refund' => array(
      ts('process backoffice event refund', array('domain' => 'biz.jmaconsulting.ao')),
    ),
    'process event refund' => array(
      ts('process event refund', array('domain' => 'biz.jmaconsulting.ao')),
    ),
    'process backoffice contribution refund' => array(
      ts('process backoffice contribution refund', array('domain' => 'biz.jmaconsulting.ao')),
    ),
    'process contribution refund' => array(
      ts('process contribution refund', array('domain' => 'biz.jmaconsulting.ao')),
    ),
  );
}

function ao_civicrm_alterAPIPermissions($entity, $action, &$params, &$permissions) {
  if ($entity == 'contact' && $action == 'getlist' && CRM_Core_Permission::check('access AJAX API')) {
    $permissions['check_permissions'] = FALSE;
  }
}

/**
 * Functions below this ship commented out. Uncomment as required.
 */

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 */
function ao_civicrm_preProcess($formName, &$form) {
  $msg = NULL;
  if ('CRM_Contribute_Form_AdditionalPayment' == $formName) {
    if ($form->getVar('_paymentType') == 'refund') {
      if ($form->getVar('component') == 'event') {
        if ($form->_mode) {
          if (!CRM_Core_Permission::check('process event refund')) {
            $msg = ts('You do not have permission to process refund for an event');
          }
        }
        elseif (!CRM_Core_Permission::check('process backoffice event refund')) {
          $msg = ts('You do not have permission to process backoffice refund for an event');
        }
      }
      else {
        if ($form->_mode) {
          if (!CRM_Core_Permission::check('process contribution refund')) {
            $msg = ts('You do not have permission to process refund');
          }
        }
        elseif (!CRM_Core_Permission::check('process backoffice contribution refund')) {
          $msg = ts('You do not have permission to process backoffice refund');
        }
      }
    }
    if ($msg) {
      CRM_Core_Error::statusBounce($msg);
    }
  }
}
