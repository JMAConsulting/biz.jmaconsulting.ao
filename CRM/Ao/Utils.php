<?php

class CRM_Ao_Utils {

  public static function ao_dashboard_angular_settings() {
    $dashlets = CRM_Core_BAO_Dashboard::getContactDashlets();
    try {
      $item = CRM_Utils_Array::value(0, civicrm_api3('Dashboard', 'get', [
        'label' => ['LIKE' => "%My Activities%"],
        'sequential' => 1,
        'options' => ['limit' => 1]
      ])['values']);
      $dashboardContact = civicrm_api3('DashboardContact', 'get', [
        'dashboard_id' => $item['id'],
        'contact_id' => CRM_Core_Session::singleton()->getLoggedInContactID(),
        'sequential' => 1,
      ]);
      $dashlet = [
        'id' => $item['id'],
        'name' => $item['name'],
        'label' => $item['label'],
        'url' => self::parseUrl($item['url']),
        'cache_minutes' => $item['cache_minutes'],
        'fullscreen_url' => self::parseUrl($item['fullscreen_url']),
        'domain_id' => 1,
        'permission' => ['access CiviReport'],
        'permission_operator' => NULL,
        'is_active' => 1,
        'is_reserved' => 0,
        'directive' => NULL,
        'dashboard_contact.id' => NULL,
        'dashboard_contact.dashboard_id' => $item['id'],
        'dashboard_contact.contact_id' => CRM_Core_Session::singleton()->getLoggedInContactID(),
        'dashboard_contact.column_no' => 1,
        'dashboard_contact.is_active' => 1,
        'dashboard_contact.weight' => 0,
      ];
      if (!empty($dashboardContact['values'])) {
        $dashlet['dashboard_contact.id'] = $dashboardContact['values'][0]['id'];
      }
      $dashlets[] = $dashlet;
    }
    catch (API_Exception $e) {
    }
    return [
      'dashlets' => $dashlets,
    ];
  }

  /**
   * @param $url
   * @return string
   */
  public static function parseUrl($url) {
    // Check if it is already a fully-formed url
    if ($url && substr($url, 0, 4) != 'http' && $url[0] != '/') {
      $urlParam = explode('?', $url);
      $url = CRM_Utils_System::url($urlParam[0], CRM_Utils_Array::value(1, $urlParam), FALSE, NULL, FALSE);
    }
    return $url;
  }

}
