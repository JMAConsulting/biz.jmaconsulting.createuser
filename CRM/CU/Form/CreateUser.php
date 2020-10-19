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

}
