<?php
use CRM_Ao_ExtensionUtil as E;
use Drupal\civicrm_entity\SupportedEntities;

/**
 * Job.UpdateGeoLocation API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_job_UpdateGeoLocation_spec(&$spec) {
  $spec['magicword']['api.required'] = 1;
}

/**
 * Job.UpdateGeoLocation API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_job_UpdateGeoLocation($params) {
  $entity = CRM_Utils_Array('entity', $params, 'contact');
  $limit = CRM_Utils_Array('limit', $params, 10);

  if ($entity == 'event') {
    $dao = CRM_Core_DAO::executeQuery("
      SELECT a.id as address_id, e.id as event_id
        FROM civicrm_event e
         INNER JOIN civicrm_loc_block lb ON lb.id = e.loc_block_id
         INNER JOIN  civicrm_address a ON a.id = lb.address_id
         LIMIT 0, $limit
    ");

    while($dao->fetch()) {
      $addressID = $dao->address_id;
      if (!empty($addressID)) {
        $address = new CRM_Core_BAO_Address();
        $address->id = $addressID;
        $address->find(TRUE);
        if (!empty($address->geo_code_1) && !empty($address->geo_code_2)) {
          _entitySave($address, $dao->event_id, 'Event');
        }
      }
    }
  }
  else {
    $dao = CRM_Core_DAO::executeQuery("
      SELECT a.id as address_id, e.id as contact_id
        FROM civicrm_contact e
         INNER JOIN  civicrm_address a ON a.contact_id = e.id AND a.is_primary = 1
         WHERE contact_sub_type LIKE '%service_provider%'
         LIMIT 0, $limit
    ");
    while($dao->fetch()) {
      $addressID = $dao->address_id;
      if (!empty($addressID)) {
        $address = new CRM_Core_BAO_Address();
        $address->id = $addressID;
        $address->find(TRUE);
        if (!empty($address->geo_code_1) && !empty($address->geo_code_2)) {
          _entitySave($address, $dao->contact_id, 'Contact');
        }
      }
    }
  }
  return civicrm_api3_create_success();
}

function _entitySave($address, $entityID, $entity) {
  $entityType = SupportedEntities::getEntityType($entity);
  $key = ($entity == 'Contact') ? 'field_mapped_location_1' : 'field_mapped_location';
  $entity = \Drupal::entityTypeManager()->getStorage(SupportedEntities::getEntityType($entity))->load($entityID);
  $params = [
    'lat' => $address->geo_code_1,
    'lng'=> $address->geo_code_2,
    'lat_sin' => sin(deg2rad($address->geo_code_1)),
    'lat_cos' => cos(deg2rad($address->geo_code_1)),
    'lng_rad' => deg2rad($address->geo_code_2),
  ];
  $params['data'] = $params;
  $entity->get('field_geolocation')->setValue(array($params));
  $entity->get($key)->setValue(1);
  $entity->save();
}
