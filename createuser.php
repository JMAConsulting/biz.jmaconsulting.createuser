<?php

require_once 'createuser.civix.php';
use CRM_CU_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function createuser_civicrm_config(&$config) {
  _createuser_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function createuser_civicrm_xmlMenu(&$files) {
  _createuser_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function createuser_civicrm_install() {
  _createuser_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function createuser_civicrm_uninstall() {
  _createuser_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function createuser_civicrm_enable() {
  _createuser_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function createuser_civicrm_disable() {
  _createuser_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function createuser_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _createuser_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function createuser_civicrm_managed(&$entities) {
  _createuser_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function createuser_civicrm_caseTypes(&$caseTypes) {
  _createuser_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function createuser_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _createuser_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

function createuser_civicrm_searchTasks($objectName, &$tasks) {
  if ($objectName == 'contact') {
    $tasks[] = [
      'title' => ts('Create User login'),
      'class' => 'CRM_CU_Form_Task_CreateUserLogin',
    ];
  }
}

function createuser_civicrm_tokens(&$tokens) {
  $tokens['createuser'] = [
    'createuser.selfCreateLink' => E::ts('Token to create an url for user to be able to create their own CMS User account'),
  ];
}

function createuser_civicrm_tokenValues(&$values, $cids, $job = NULL, $tokens = [], $context = NULL) {
  $group = 'createuser';
  if (isset($tokens[$group])) {
    $token = 'selfCreateLink';
    if (!createuser_isTokenRequired($tokens, $group, $token)) {
      return;
    }
    foreach ($cids as $cid) {
      $values[$cid][$group . '.' . $token] = CRM_Utils_System::url('civicrm/create-user-account', ['cid' => $cid, 'cs' => CRM_Contact_BAO_Contact_Utils::generateChecksum($cid)], TRUE, NULL, TRUE, TRUE);
    }
  }
}

/**
 * "Send an Email" and "CiviMail" send different parameters to the tokenValues hook (in CiviCRM 5.x).
 * This works around that.
 *
 * @param array $tokens
 * @param string $group
 * @param string $token
 *
 * @return bool
 */
function createuser_isTokenRequired($tokens, $group, $token) {
  // CiviMail sets $tokens to the format:
  //   [ 'group' => [ 'token_name' => 1 ] ]
  // "Send an email" sets $tokens to the format:
  //  [ 'group' => [ 0 => 'token_name' ] ]
  if (array_key_exists($token, $tokens[$group]) || in_array($token, $tokens[$group])) {
    return TRUE;
  }
  return FALSE;
}
