<?php

require_once 'ao.variables.php';
require_once 'ao.civix.php';

use CRM_Ao_ExtensionUtil as E;
use Drupal\civicrm_entity\SupportedEntities;

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
    if ($form->_action == CRM_Core_Action::UPDATE) {
      $fields['activity_type_id'] = $form->_activityTypeId;
    }
    if (in_array($fields['activity_type_id'], [70, 137])) {
      $assigneeContact = $fields['assignee_contact_id'];
      /* $relTypeId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_RelationshipType', 'Parent of', 'id', 'name_b_a');
      $relationship = civicrm_api3('Relationship', 'get', [
        'contact_id_b' => $withContact,
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
      } */
      if (!empty($assigneeContact)) {
        $isAoEmail = CRM_Core_DAO::singleValueQuery("SELECT 1 FROM civicrm_email WHERE email LIKE '%@autismontario.com' AND contact_id = $assigneeContact LIMIT 1");
        if (!$isAoEmail) {
          $errors['assignee_contact_id'] = ts('The contact being assigned to this activity must be an AO staff member');
        }
      }
    }
    /* if (!empty($fields[CURRENT_NEEDS]['AdultNeeds'])) {
      if(count(array_filter($fields[ADULT_NEEDS])) == 0) {
        $errors[ADULT_NEEDS] = ts('Please specify one of Adult Needs options');
      }
    } */
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
    if (!empty($fields[CURRENT_NEEDS]['OAP'])) {
      if(count(array_filter($fields[OAP])) == 0) {
        $errors[OAP] = ts('Please specify one of OAP options');
      }
    }
    if (empty($fields['target_contact_id'])) {
      $errors['target_contact_id'] = ts('With Contact is a required field.');
    }
  }
  if (!empty($fields['birth_date']) && $fields['birth_date'] > date('Y-m-d')) {
    $errors['birth_date'] = E::ts('Date of birth cannot be in the future');
  }
}

function ao_civicrm_pageRun(&$page) {
  if ($page->getVar('_name') == 'CRM_Contact_Page_DashBoard') {
    $items = CRM_Core_BAO_Dashboard::getContactDashletsForJS();
    try {
      $item = CRM_Utils_Array::value(0, civicrm_api3('Dashboard', 'get', ['url' => ['LIKE' => "%instance/47%"],  'sequential' => 1])['values']);
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
  if ($page->getVar('_name') == 'CRM_Activity_Page_Tab') {
    CRM_Core_Region::instance('page-header')->add([
      'template' => 'CRM/Activity/Page/activityButton.tpl',
    ]);
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
  elseif ($objectName == 'Mailing' && in_array($op, ['create', 'edit'])) {
    $objectRef->body_html = str_replace('[show_link]', 'https://www.autismontario.com/civicrm/mailing/view?reset=1&id=' . $objectId, $objectRef->body_html);
    $objectRef->save();
  }
  if ($objectName == "Address" && $op != "delete") {
    $objectRef->find(TRUE);
    if (empty($objectRef->contact_id) || empty($objectRef->geo_code_1) || empty($objectRef->geo_code_2)) {
      return;
    }

    $entityID = CRM_Core_DAO::singlevalueQuery("SELECT id FROM civicrm_contact WHERE contact_sub_type LIKE '%service_provider%' AND id = " . $objectRef->contact_id);

    if ($entityID) {
      _entitySave($objectRef, $entityID, 'Contact');
    }
  }
}

function _entitySave($address, $entityID, $entity) {
  $entityType = SupportedEntities::getEntityType($entity);
  $key = ($entity == 'Contact') ? 'field_mapped_location_1' : 'field_mapped_location';
  $entity = \Drupal::entityTypeManager()->getStorage(SupportedEntities::getEntityType($entity))->load($entityID);
  $params = [
    [
      'lat' => $address->geo_code_1,
      'lng'=> $address->geo_code_2,
      'lat_sin' => sin(deg2rad($address->geo_code_1)),
      'lat_cos' => cos(deg2rad($address->geo_code_1)),
      'lng_rad' => deg2rad($address->geo_code_2),
    ],
  ];
  $params['data'] = $params;
  if ($entity == 'Contact') {
    $dao = CRM_Core_DAO::executeQuery("SELECT id, geo_code_1, geo_code_2 FROM civicrm_address WHERE contact_id = {$entityID} AND geo_code_1 IS NOT NULL AND geo_code_2 IS NOT NULL");
    while($dao->fetch()) {
      $p = [
        'lat' => $dao->geo_code_1,
        'lng'=> $dao->geo_code_2,
        'lat_sin' => sin(deg2rad($dao->geo_code_1)),
        'lat_cos' => cos(deg2rad($dao->geo_code_1)),
        'lng_rad' => deg2rad($dao->geo_code_2),
      ];
      $p['data'] = $p;
      $params[] = $p;
    }
  }

  $entity->get('field_geolocation')->setValue($params);
  $entity->get($key)->setValue(1);
  $entity->save();
}


function ao_civicrm_postSave_civicrm_membership($dao) {
 // civicrm_api3('CustomValue', 'create', array('entity_id' => $dao->id, 'custom_758' => getMemberID()));
}

function ao_civicrm_buildForm($formName, &$form) {
  // AOS-439 : expose 'Address Name' field on 'Event Location' config page
  if ($formName == 'CRM_Event_Form_ManageEvent_Location') {
    $params = ['entity' => 'address'];
    $form->addField("address[1][name]", $params);
  }
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
    if (CRM_Core_I18n::getLocale() == "fr_CA") {
      CRM_Core_Resources::singleton()->addScript(
        "CRM.$(function($) {
          var intFormat = new Intl.NumberFormat('fr-CA', {style: 'currency', currency: 'CAD', currencyDisplay: 'symbol'});
          if ($('#auto_renew').length !== -1) {
            $('#auto_renew').next('label').text('Veuillez renouveler automatiquement mon adhésion.');
          }
          var amount = $('.crm-price-amount-amount').text().replace('$ ', '').trim();
          if ($('[name=price_21]').length !== -1) {
            $('.crm-price-amount-amount').text(intFormat.format(amount));
            var label = $('[name=price_21]').next('label').html();
            label = label + ' <span class=\"crm-price-amount-label\">par année</span>';
            $('[name=price_21]').next('label').html(label);
          }
          if ($('[name=price_19]').length !== -1) {
            $('.crm-price-amount-amount').text(intFormat.format(amount));
            var label = $('[name=price_19]').next('label').html();
            label = label + ' <span class=\"crm-price-amount-label\">par année</span>';
            $('[name=price_19]').next('label').html(label);
          }
          if ($('[name=price_17]').length !== -1) {
            $('.crm-price-amount-amount').text(intFormat.format(amount));
            var label = $('[name=price_17]').next('label').html();
            label = label + ' <span class=\"crm-price-amount-label\">par année</span>';
            $('[name=price_17]').next('label').html(label);
          }
        });"
      );
    }
  }
  $membershipPages = [4, 5, 6];
  if ($formName == 'CRM_Contribute_Form_Contribution_Confirm' && CRM_Core_I18n::getLocale() == "fr_CA" && in_array($form->_id, $membershipPages)) {
    CRM_Core_Resources::singleton()->addScript(
      "CRM.$(function($) {
        var intFormat = new Intl.NumberFormat('fr-CA', {style: 'currency', currency: 'CAD', currencyDisplay: 'symbol'});
        var amount = $('.amount_display-group strong').text().replace('$ ', '').trim();
        $('.amount_display-group strong').text(intFormat.format(amount));
        label = $('.amount_display-group .display-block').html();
        $('.amount_display-group .display-block').html(label + ' par année');
      });"
    );
  }
  if ($formName == 'CRM_Contribute_Form_Contribution_ThankYou' && CRM_Core_I18n::getLocale() == "fr_CA" && in_array($form->_id, $membershipPages)) {
    CRM_Core_Resources::singleton()->addScript(
      "CRM.$(function($) {
        var intFormat = new Intl.NumberFormat('fr-CA', {style: 'currency', currency: 'CAD', currencyDisplay: 'symbol'});
        var firstAmount = $('.amount_display-group strong:eq(0)').text().replace('$ ', '').trim();
        $('.amount_display-group strong:eq(0)').text(intFormat.format(firstAmount));
        var secondAmount = $('.amount_display-group strong:eq(2)').text().replace('$ ', '').trim();
        $('.amount_display-group strong:eq(2)').text(intFormat.format(secondAmount));
        var contents = $('.amount_display-group .display-block').html();
        $('.amount_display-group .display-block').html(contents.substring(0, contents.indexOf('</strong>') + 9) + ' par année' + contents.substring(contents.indexOf('</strong>') + 9));
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
  elseif($formName == 'CRM_Event_Form_ManageEvent_Location' && !empty($form->_id)) {
    $addressID = CRM_Core_DAO::singlevalueQuery("
      SELECT a.id
        FROM civicrm_event e
         INNER JOIN civicrm_loc_block lb ON lb.id = e.loc_block_id
         INNER JOIN  civicrm_address a ON a.id = lb.address_id
      WHERE e.id = {$form->_id}
    ");
    if (!empty($addressID)) {
      $address = new CRM_Core_BAO_Address();
      $address->id = $addressID;
      $address->find(TRUE);
      if (!empty($address->geo_code_1) && !empty($address->geo_code_2)) {
        _entitySave($address, $form->_id, 'Event');
      }
    }
  }
  /* if ($formName == "CRM_Contribute_Form_Contribution_Confirm") {
    if (!empty($form->_params['contributionRecurID'])) {
      $cid = CRM_Core_DAO::singleValueQuery("SELECT contact_id FROM civicrm_contribution_recur WHERE id = %1", [1 => [$form->_params['contributionRecurID'], "Integer"]]);
      if (empty($cid)) {
        return;
      }
      $token = CRM_Core_DAO::singlevalueQuery("SELECT MAX(id) FROM civicrm_payment_token WHERE contact_id = %1", [1 => [$cid, "Integer"]]);
      if (!empty($token)) {
        $result = civicrm_api3('ContributionRecur', 'create', array(
          'id' => $form->_params['contributionRecurID'],
          'payment_token_id' => $token,
        ));
      }
    }
  } */
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
 * Implements hook_civicrm_pre().
 *
 * AOS-64 Ensure that all postcodes are formatted in the format of xxx yyy
 */
function ao_civicrm_pre($op, $objectName, $id, &$params) {
  $update = TRUE;
  if ($objectName === 'Address' && ($op === 'create' || $op === 'edit')) {
    if ($id) {
      // If we have a postal code in the address parameters then reformat into the correct format
      // To determine if the imput was already in the correct format
      if (!empty($params['postal_code'])) {
        $postalCode = str_replace(' ', '', $params['postal_code']);
        $postalCode = substr($postalCode, 0, 3) . ' ' . substr($postalCode, 3);
        if ($postalCode == $address['postal_code']) {
          $update = FALSE;
        }
      }
      else {
        // If we have an id but don't have a postal code, this could be from an API call where
        // Someone is just changing the is_primary flag pull the current postal code out of the database
        // and reformat it into the expected format to see if the postcode in the db is already in the correct format.
        $address = civicrm_api3('Address', 'getsingle', ['id' => $id]);
        $postalCode = str_replace(' ', '', $address['postal_code']);
        $postalCode = substr($postalCode, 0, 3) . ' ' . substr($postalCode, 3);
        if ($postalCode == $address['postal_code']) {
          $update = FALSE;
        }
      }
    }
    elseif (!empty($params['postal_code'])) {
      // If we have a postal code in the address parameters then reformat into the correct format
      // To determine if the imput was already in the correct format
      $postalCode = str_replace(' ', '', $params['postal_code']);
      $postalCode = substr($postalCode, 0, 3) . ' ' . substr($postalCode, 3);
      if ($postalCode == $address['postal_code']) {
        $update = FALSE;
      }
    }
    if ($update) {
      $params['postal_code'] = $postalCode;
    }
  }

  // If we have an entryURL we have come in via either a Contribution Form or an Event Registration Form
  if ($objectName === 'Individual' && array_key_exists('entryURL', $params)) {
    if (array_key_exists('address', $params) && (stripos($params['entryURL'], 'contribute') !== FALSE || stripos($params['entryURL'], 'register') !== FALSE)) {
      foreach ($params['address'] as $key => $v) {
        $params['address'][$key]['skip_auto_create'] = 1;
        $params['address'][$key][ADDRESS_CREATED_DATE] = date('Y-m-d H:i:s');
        $params['address'][$key][ADDRESS_SOURCE] = 'Front end form';
      }
    }
  }
  // skip_auto_create will only be set if we have come from a public form.
  if ($objectName === 'Address' && array_key_exists('id', $params)) {
    if (!empty($params['skip_auto_create'])) {
      // Remove id so we force create a new address.
      unset($params['id']);
      // Ensure that the new address isn't primary
      $params['is_primary'] = 0;
    }
    $currentAddress = civicrm_api3('Address', 'get', ['id' => $params['id']]);
    if (!empty($currentAddress['values'])) {
      $currentAddress = $currentAddress['values'][$currentAddress['id']];
      if ($params['street_address'] != $currentAddress['street_address'] || $params['city'] != $currentAddress['city']
        || $params['postal_code'] != $currentAddress['postal_code'] || $params['state_province_id'] != $currentAddress['state_province_id']) {
        if (empty($params['skip_auto_create'])) {
          $params[ADDRESS_SOURCE] = 'Backoffice form';
        }
        $params[ADDRESS_CREATED_DATE] = date('Y-m-d H:i:s');
      }
    }
  }
  elseif ($objectName === 'Address') {
    if (empty($params['address-name'])) {
      if (empty($params['skip_auto_create'])) {
        $params[ADDRESS_SOURCE] = 'Backoffice form';
     }
      $params[ADDRESS_CREATED_DATE] = date('Y-m-d H:i:s');
    }
  }
}

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
