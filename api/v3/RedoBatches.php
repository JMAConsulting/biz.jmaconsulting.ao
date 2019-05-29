<?php

/**
 * This api exposes CiviCRM DonorPerfect records.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Job to migrate DonorPerfect profile as contacts in CiviCRM
 *
 * @param array $params
 *
 * @return array
 *   API result array
 */
function civicrm_api3_redo_batches_create($params) {
  $contactID = CRM_Core_Session::getLoggedInContactID();
  $sql = "
    SELECT ee.batch_id, b.title, ee.payment_processor_id
     FROM `civicrm_easybatch_entity` as ee
    LEFT JOIN civicrm_batch b ON ee.batch_id = b.id
    LEFT JOIN civicrm_entity_batch eb ON eb.batch_id = b.id
    LEFT JOIN civicrm_financial_trxn ft ON ft.id = eb.entity_id
    WHERE ee.batch_date BETWEEN '2019-04-1' AND '2019-05-30' AND ee.payment_processor_id IS NOT NULL  AND ee.payment_processor_id = 3 GROUP BY ee.batch_id ";
    $dao = CRM_Core_DAO::executeQuery($sql);

    while($dao->fetch()) {
      civicrm_api3('Batch', 'create', [
        'id' => $dao->batch_id,
        'title' => str_replace('Auto', 'Visa Auto', $dao->title),
      ]);
      CRM_Core_DAO::executeQuery("UPDATE civicrm_easybatch_entity SET card_type_id = 1 WHERE batch_id = " . $dao->batch_id);
      $batchID = civicrm_api3('Batch', 'create', [
        'title' => str_replace('Auto', 'MasterCard Auto', $dao->title),
        'status_id' => "Closed",
        'type_id' => "Contribution",
      ]);

      CRM_Core_DAO::executeQuery("INSERT INTO civicrm_easybatch_entity (`batch_id`, `contact_id`, `payment_processor_id`, `is_automatic`, `batch_date`, `card_type_id`)
       VALUES($batchID, $contactID,  $dao->payment_processor_id, 1, '" . date('Y-m-d H:i:s'). "', 2)");

       $sql = "INSERT IGNORE INTO civicrm_entity_batch ('entity_table', 'entity_id', 'batch_id')
         SELECT 'civicrm_financial_trxn', eb.entity_id, $batchID
          FROM civicrm_entity_batch eb
          LEFT JOIN civicrm_financial_trxn ft ON ft.id = eb.entity_id
          AND eb.batch_id = $dao->batch_id AND ft.card_type_id = 2
       ";
       $dao = CRM_Core_DAO::executeQuery($sql);
    }

}
