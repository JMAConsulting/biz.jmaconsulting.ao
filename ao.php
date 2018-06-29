<?php

require_once 'ao.variables.php';
require_once 'ao.civix.php';

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function ao_civicrm_config(&$config) {
  CRM_Core_Resources::singleton()->addScript(
    "CRM.$(function($) {
      $('#toolbar-administration').hide();
      $('.crm-hidemenu').on('click', function() {
        $('#toolbar-administration').show();
      });
    });"
  );
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
  if ($formName == 'CRM_Event_Form_Registration_Register') {
    if (array_key_exists(LANG_SPOKEN, $form->_fields) && array_key_exists(LANG_OTHER, $form->_fields)) {
      $form->assign('lang_spoken', LANG_SPOKEN);
      $form->assign('lang_other', LANG_OTHER);
    }
    CRM_Core_Region::instance('page-body')->add(array(
      'template' => 'CRM/Ao/Ao.tpl',
    ));
  }
}

function ao_civicrm_postProcess($formName, &$form) {
  if ($formName == 'CRM_Event_Form_Registration_Confirm') {
    $params = $form->getVar('_params');
    $genders = CRM_Contact_BAO_Contact::buildOptions('gender_id');
    $gender =  CRM_Utils_Array::value(C_GENDER, $params, NULL) ? $genders[$params[C_GENDER]] : NULL;
    // Create child
    $cParams = array(
      'first_name' => CRM_Utils_Array::value(C_FIRST_NAME, $params, NULL),
      'last_name' => CRM_Utils_Array::value(C_LAST_NAME, $params, NULL),
      'birth_date' => CRM_Utils_Array::value(C_DOB, $params, NULL),
      'gender' => $gender,
      'contact_type' => 'Individual',
      'contact_sub_type' => 'Child',
    );
    $child = civicrm_api3('Contact', 'create', $cParams);
    if (!empty($child['id'])) {
      civicrm_api3("Relationship", "create", array(
        "contact_id_b" => $params['contactID'],
        "contact_id_a" => $child['id'],
        "relationship_type_id" => CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_RelationshipType', 'Child of', 'id', 'name_a_b'),
      ));
    }
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

/**
 * Functions below this ship commented out. Uncomment as required.
 *

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function ao_civicrm_preProcess($formName, &$form) {

}

*/
