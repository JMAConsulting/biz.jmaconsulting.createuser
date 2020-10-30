<?php

use CRM_CU_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_CU_Form_CreateUser extends CRM_Core_Form {

  /**
   * The contact id, used when adding user
   *
   * @var int
   */
  protected $_contactId;

  /**
   * Primary email of contact for whom we are adding user.
   *
   * @var int
   */
  public $_email;

  public function buildQuickForm() {
    $defaults = $params = $formDefaults = $ids = [];
    $checksum = CRM_Utils_Request::retrieve('cs', 'String', $this, TRUE);
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    if (!CRM_Contact_BAO_Contact_Utils::validChecksum($this->_contactId, $checksum)) {
      CRM_Core_Error::statusBounce(E::ts("Oops. It looks like you have an incorrect or incomplete link (URL). Please make sure you've copied the entire link, and try again. Contact the site administrator if this error persists."));
    }
    if (self::userAlreadyHasUserAccount($this->_contactId)) {
      $userDetails = self::getUserDetails($this->_contactId);
      CRM_Utils_System::setUFMessage(E::ts('Hi ' . $userDetails['display_name'] . ',  your username is ' . $userDetails['username'] . '.'));
      CRM_Utils_System::redirect(CRM_Core_Config::singleton()->userSystem->getLoginURL() . '?createUserRedirect=true&name=' . $userDetails['display_name'] . '&user=' . $userDetails['username']);
    }
    $params['id'] = $params['contact_id'] = $formDefaults['contactID'] = $this->_contactId;
    $contact = CRM_Contact_BAO_Contact::retrieve($params, $defaults, $ids);
    $this->_email = $contact->email;
    $formDefaults['email'] = $this->_email[1]['email'];
    $this->add('text', 'cms_name', E::ts('Username'), NULL, TRUE);
    $emailField = $this->add('text', 'email', E::ts('Email address'));
    $this->add('hidden', 'contactID');
    $this->setDefaults($formDefaults);
    $emailField->freeze();
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    //add a rule to check username uniqueness
    $this->addFormRule(['CRM_Contact_Form_Task_Useradd', 'usernameRule']);
    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  /**
   * Post process function.
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->exportValues();

    if (CRM_Core_BAO_CMSUser::create($params, 'email') === FALSE) {
      CRM_Core_Error::statusBounce(ts('Error creating CMS user account.'));
    }
    else {
      CRM_Core_Session::setStatus(ts('User Added'), '', 'success');
    }
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

  /**
   * Determine if a contact already has a uf account
   */
  public static function userAlreadyHasUserAccount($contactID): bool {
    $count = CRM_Core_DAO::singleValueQuery("SELECT count(id) FROM civicrm_uf_match WHERE contact_id = %1 AND domain_id = %2", [1 => [$contactID, 'Positive'], 2 => [CRM_Core_Config::domainID(), 'Positive']]);
    if (!empty($count)) {
      return TRUE;
    }
    return FALSE;
  }

  public static function getUserDetails($contactID): array {
    $contact = civicrm_api3('Contact', 'get', ['id' => $contactID])['values'][$contactID];
    $ufId = CRM_Core_DAO::singleValueQuery("SELECT uf_id FROM civicrm_uf_match WHERE contact_id = %1 AND domain_id = %2", [1 => [$contactID, 'Positive'], 2 => [CRM_Core_Config::domainID(), 'Positive']]);
    $uf = CRM_Core_Config::singleton()->userFramework;
    switch ($uf) {
      case 'Drupal':
        $user = user_load($ufId);
        $username = $user->name;
        break;

      case 'Drupal8':
        $user = \Drupal::entityManager()->getStorage('user')->load($ufId);
        $username = $user->name;
        break;

      case 'WordPress':
        $user = get_user_by('id', $ufId);
        $username = $user->user_login;
        break;

      case 'Joomla':
        $user = JUser::getInstance($ufID);
        $username = $user->name;
        break;

    }
    return [
      'username' => $username,
      'display_name' => $contact['display_name'],
    ];
  }

}
