<?php
class CRM_Ao_AJAX {

/**
 * Wrapper for participant selector.
 *
 * @return array
 *   associated array of participant records
 */
public static function getParticipantList() {
  $params = $_GET;
  // format the params
  $columns = [
    'event_title',
    'event_start_date',
    'participant_status',
  ];

  $apiParams = [
    'contact_id' => $params['cid'],
    'options' => [
      'offset' => $params['start'],
      'limit' => $params['length'],
    ],
  ];
  if (!empty($params['order']) && !empty($params['order'][0])) {
    $apiParams['options']['sort'] = sprintf('%s %s', $columns[$params['order'][0]['column']], $params['order'][0]['dir']);
  }

  // check logged in user for permission
  $page = new CRM_Core_Page();
  CRM_Contact_Page_View::checkUserPermission($page, $params['cid']);

  $result = (array) civicrm_api3('Participant', 'get', $apiParams)['values'];

  $participants = [];
  foreach ($result as $participantID => $values) {
    $participant = array();

    $participant['DT_RowId'] = $values['id'];
    $participant['DT_RowClass'] = 'crm-entity';

    $participant['DT_RowAttr'] = array();
    $participant['DT_RowAttr']['data-entity'] = 'participant';
    $participant['DT_RowAttr']['data-id'] = $values['id'];

    $participant['event'] = sprintf('<a href=%s>%s</a>', CRM_Utils_System::url('civicrm/event/info', "reset=1&id=" . $values['event_id'] . "&context=dashboard"), $values['event_title']);
    $participant['start_date'] = CRM_Utils_Date::customFormat($values['event_start_date']);
    if (!empty($values['event_end_date'])) {
      $participant['start_date'] .= '  &nbsp; - &nbsp; ' . CRM_Utils_Date::customFormat($values['event_end_date']);
    }
    $participant['status'] = $values['participant_status'];
    array_push($participants, $participant);
  }

  $participantsDT = array();
  $participantsDT['data'] = $participants;
  $participantsDT['recordsTotal'] = count($participants);
  $participantsDT['recordsFiltered'] = count($participants);

  CRM_Utils_JSON::output($participantsDT);
}

public static function backofficeRefund() {
  $params = $_GET;
  $contributionID = $params['id'];
  $contactID = $params['cid'];
  $context = CRM_Utils_Array::value('cxt', $params, 'contribution');

  $lineItems = civicrm_api3('LineItem', 'get', [
    'contribution_id' => $contributionID,
    'sequential' => 1,
  ])['values'];
  foreach ($lineItems as $lineItem) {
    CRM_Lineitemedit_Util::cancelEntity($lineItem['entity_id'], $lineItem['entity_table']);
    // change total_price and qty of current line item to 0, on cancel
    civicrm_api3('LineItem', 'create', array(
      'id' => $lineItem['id'],
      'qty' => 0,
      'participant_count' => 0,
      'line_total' => 0.00,
      'tax_amount' => 0.00,
    ));

    $updatedAmount = CRM_Price_BAO_LineItem::getLineTotal($contributionID);
    $taxAmount = CRM_Lineitemedit_Util::getTaxAmountTotalFromContributionID($contributionID);
    // Record adjusted amount by updating contribution info and create necessary financial trxns
    CRM_Lineitemedit_Util::recordAdjustedAmt(
      $updatedAmount,
      $contributionID,
      $taxAmount,
      FALSE
    );
    // Record financial item on cancel of lineitem
    CRM_Lineitemedit_Util::insertFinancialItemOnEdit(
      $contributionID,
      $lineItem
    );
  }
  CRM_Utils_System::redirect('civicrm/payment', "reset=1&id={$contributionID}&cid={$contactID}&action=add&component={$context}");
}

}
