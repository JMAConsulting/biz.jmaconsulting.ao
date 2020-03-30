<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Ao_Form_Mapping extends CRM_Core_Form {
  function buildQuickForm() {
    $membershipTypesNames = CRM_Utils_Array::collect('name', civicrm_api3('MembershipType', 'get',[])['values']);
    foreach ($membershipTypesNames as $id => $membershipType) {
      $this->add('select', "contribution_page_id[{$id}]",
        $membershipType,
        CRM_Contribute_PseudoConstant::contributionPage()
      );
    }
    $this->assign('membershipTypes', $membershipTypesNames);

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    parent::buildQuickForm();
  }

  function postProcess() {
    $params = $this->exportValues();
    Civi::settings()->set('ao_membership_type_mapping', CRM_Utils_Array::value('contribution_page_id', $params));
    parent::postProcess();
  }

}
