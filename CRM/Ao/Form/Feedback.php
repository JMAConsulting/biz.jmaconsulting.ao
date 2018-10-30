<?php


require_once __DIR__ . '/ao.variables.php';

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Ao_Form_Feedback extends CRM_Core_Form {
  protected $_eventID;
  protected $_contactID;
  protected $_id;


  public function preProcess() {
    $this->_eventID = CRM_Utils_Request::retrieve('eid', 'Positive', $this, TRUE);
    $userChecksum = CRM_Utils_Request::retrieve('cs', 'String', $this);
    $this->_contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);

    if ($this->_contactID && $userChecksum) {
      if (!CRM_Contact_BAO_Contact_Utils::validChecksum($this->_contactID, $userChecksum)) {
        CRM_Core_Error::fatal(ts("Invalid contact."));
      }
    }

    //if (!empty($_POST['hidden_custom'])) {
    //  $this->set('type', 'Activity');
    //  CRM_Custom_Form_CustomData::preProcess($this, NULL, NULL, 1, 'Activity', $this->_id);
    //  CRM_Custom_Form_CustomData::buildQuickForm($this);
      //CRM_Custom_Form_CustomData::setDefaultValues($this);
    //}
  }

  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Event Feedback Form'));
    $this->assign('customDataType', 'Activity');
    $this->assign('customDataSubType', ACTIVTY_TYPE_ID);

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ),
    ));
  }

  public function postProcess() {
    $params = [
      'subject' => ts('Feedback submitted by ') . CRM_Contact_BAO_Contact::displayName($this->_contactID),
      'activity_type_id' => ACTIVTY_TYPE_ID,
      'details' => ts('Event - %1', [
        1 => civicrm_api3('Event', 'getvalue', ['id' => $this->_eventID, 'return' => 'title']),
      ]),
      'source_record_id' => $this->_eventID,
      'source_contact_id' => CRM_Core_Session::singleton()->getLoggedInContactID(),
      'target_contact_id' => [$this->_contactID],
    ];
    $this->_id = civicrm_api3('Activity', 'create', $params)['id'];

    $customValues = CRM_Core_BAO_CustomField::postProcess($this->_submitValues, $this->_id, 'Activity');
    if (!empty($customValues) && is_array($customValues)) {
      CRM_Core_BAO_CustomValueTable::store($customValues, 'civicrm_activity', $this->_id);
    }

    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/activity', sprintf("atype=%d&action=view&reset=1&id=%d&cid=%d&context=activity&searchContext=activity", ACTIVTY_TYPE_ID, $this->_id, $this->_contactID)));
  }

}
