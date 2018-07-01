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

    $participant['event'] = $values['event_title'];
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

}
