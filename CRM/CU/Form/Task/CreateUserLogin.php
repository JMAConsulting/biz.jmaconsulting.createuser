<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * This class generates form components generic to useradd.
 */
class CRM_CU_Form_Task_CreateUserLogin extends CRM_Contact_Form_Task {

  public function preProcess() {
    parent::preProcess();
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $label = ts('Create User Login(s)?');
    $this->addDefaultButtons($label, 'done');
  }

  /**
   * Post process function.
   */
  public function postProcess() {
    foreach ($this->_contactIds as $cid) {
      $name = CRM_Core_DAO::executeQuery("SELECT LOWER(CONCAT(first_name, '.', last_name)) as name, display_name FROM civicrm_contact WHERE id = %1", [1 => [$cid, "Integer"]])->fetchAll()[0];
      if (self::usernameRule($cid)) {
        $ufExists[] = $name['display_name'];
        continue;
      }
      if (self::emailRule($cid)) {
        $noEmail[] = $name['display_name'];
        continue;
      }
      $params = [
        'cms_name' => $name['name'],
        'cms_pass' => 'changeme',
        'cms_confirm_pass' => 'changeme',
        'email' => CRM_Core_DAO::singleValueQuery("SELECT email FROM civicrm_email WHERE contact_id = %1 AND is_primary = 1", [1 => [$cid, "Integer"]]),
        'contactID' => $cid,
        'name' => $name['display_name'],
        'notify' => TRUE,
      ];
      $ufs[] = CRM_Core_BAO_CMSUser::create($params, 'email');
    }
    if (!empty($ufExists)) {
      $message = '<ul><li>' . implode('</li><li>', $ufExists) . '</li></ul>';
      CRM_Core_Session::singleton()->setStatus(ts("The following contacts already had Drupal user accounts, and thus did not have new ones created for them: %1", array(1 => $message)), 'Cannot Create User login', 'error');
    }
    if (!empty($noEmail)) {
      $message = '<ul><li>' . implode('</li><li>', $noEmail) . '</li></ul>';
      CRM_Core_Session::singleton()->setStatus(ts("The following contacts did not have a primary email that was not On Hold, and thus did not have Drupal user accounts created for them: %1", array(1 => $message)), 'Cannot Create User login', 'error');
    }
    if (!empty($ufs)) {
      CRM_Core_Session::setStatus('', ts('CMS User(s) Added'), 'success');
    }
  }

  /**
   * Validation Rule.
   *
   * @param array $params
   *
   * @return array|bool
   */
  public static function usernameRule($cid) {
    // Check if there is a UFMatch, if there is, that means there is a CMS ID associated the account.
    $ufId = CRM_Core_DAO::singleValueQuery("SELECT uf_id FROM civicrm_uf_match WHERE contact_id = %1", [1 => [$cid, "Integer"]]);
    if (!empty($ufId)) {
      return TRUE;
    }
    // Check if the CMS has an account with the same email.
    $email = CRM_Core_DAO::singleValueQuery("SELECT email FROM civicrm_email WHERE contact_id = %1 AND is_primary = 1", [1 => [$cid, "Integer"]]);
    if (!empty($email)) {
      $config = CRM_Core_Config::singleton();
      $errors = array();
      $check_params = array(
        'mail' => $email,
      );
      $config->userSystem->checkUserNameEmailExists($check_params, $errors, 'mail');

      return !empty($errors) ? TRUE : FALSE;
    }
    return FALSE;
  }

  /**
   * Validation Rule.
   *
   * @param array $params
   *
   * @return array|bool
   */
  public static function emailRule($cid) {
    // Check if the user has a valid email.
    $email = CRM_Core_DAO::singleValueQuery("SELECT email FROM civicrm_email WHERE contact_id = %1 AND is_primary = 1 AND on_hold <> 1", [1 => [$cid, "Integer"]]);
    if (!empty($email)) {
      return FALSE;
    }
    return TRUE;
  }

}
