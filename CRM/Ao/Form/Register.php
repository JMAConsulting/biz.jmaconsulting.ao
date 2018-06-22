<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Ao_Form_Register extends CRM_Core_Form {
  function buildQuickForm() {
    $this->add('select', "membership_type_id", NULL, CRM_Utils_Array::collect('name', civicrm_api3('MembershipType', 'get',[])['values']));

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Go'),
        'isDefault' => TRUE,
      ),
    ));

    parent::buildQuickForm();
  }

  function postProcess() {
    $values = $this->exportValues();
    $contributionPageID = CRM_Utils_Array::value($values['membership_type_id'], Civi::settings()->get('ao_membership_type_mapping'));
    return CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contribute/transact', 'reset=1&id=' . $contributionPageID));
  }


}
