<?php
class CRM_Contact_GetAPIWrapper implements API_Wrapper {
  /**
   * the wrapper contains a method that allows you to alter the parameters of the api request (including the action and the entity)
   */
  public function fromApiInput($apiRequest) {
    return $apiRequest;
  }
  /**
   * alter the result before returning it to the caller.
   */
  public function toApiOutput($apiRequest, $result) {
    foreach ((array) $result['values'] as $k => $value) {
      foreach ($value as $key => $val) {
        if (strstr($key, 'custom_')) {
          $result['values'][$k][$key] = explode(', ', CRM_Core_BAO_CustomField::displayValue($val, $key));
        }
      }
    }
    return $result;
  }
}
