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
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function createuser_civicrm_install() {
  _createuser_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function createuser_civicrm_enable() {
  _createuser_civix_civicrm_enable();
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

if (function_exists('add_filter')) {
  add_filter('login_message', 'createuser_wp_login_message');
}
function createuser_wp_login_message($message) {
  $action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';
  $errors = new WP_Error();
  switch ($action) {
    case 'register':
    case 'checkemail':
    case 'lostpassword':
      continue;

    default:
      if (!empty($_REQUEST['createUserRedirect'])) {
        $message .= '<p class="message">' .__('Hi ' . $_REQUEST['name'] . ',  your username is ' . $_REQUEST['user'] . '.', 'text_domain') . '</p>';
      }
      break;

  }
  return $message;
}
