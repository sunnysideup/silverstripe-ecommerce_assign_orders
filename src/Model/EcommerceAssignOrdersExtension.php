<?php

namespace Sunnysideup\EcommerceAssignOrders\Model;

use DataExtension;
use FieldList;
use EcommerceRole;
use Config;
use DropdownField;
use Member;
use Director;



/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD:  extends DataExtension (ignore case)
  * NEW:  extends DataExtension (COMPLEX)
  * EXP: Check for use of $this->anyVar and replace with $this->anyVar[$this->owner->ID] or consider turning the class into a trait
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
class EcommerceAssignOrdersExtension extends DataExtension
{
    private static $has_one = array(
        'AssignedAdmin' => 'Member'
    );

    private static $casting = array(
        'AssignedAdminNice' => 'Varchar'
    );

    private static $summary_fields = array(
        'AssignedAdminNice' => 'Admin'
    );

    private static $searchable_fields = array(
        "AssignedAdminID" => array(
            'field' => 'TextField',
            'filter' => 'ExactMatchFilter',
            'title' => 'Assigned Admin'
        )
    );

    private static $notify_by_email = true;

    public function getAssignedAdminNice()
    {
        if ($this->owner->AssignedAdminID) {
            if ($admin = $this->owner->AssignedAdmin()) {
                if ($admin->exists()) {
                    return $admin->getTitle();
                }
            }
        }
        return 'n/a';
    }

    /**
     * Update Fields
     * @return FieldList
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab(
            'Root.Next',
            $this->getAssignedAdminDropdown(),
            'OrderSummary'
        );
    }

    public function scaffoldSearchFields($fieldList, $_params)
    {
        $fieldList->push($this->getAssignedAdminDropdown());
    }

    protected function getAssignedAdminDropdown()
    {
        $shopAdminAndCurrentCustomerArray = EcommerceRole::list_of_admins(true);
        $titleArray = Config::inst()->get('EcommerceAssignOrdersExtension', 'searchable_fields');
        $title = isset($titleArray['AssignedAdminID']['title']) ? $titleArray['AssignedAdminID']['title'] : 'Admin';
        return DropdownField::create(
            'AssignedAdminID',
            $title,
            $shopAdminAndCurrentCustomerArray
        );
    }

    public function onAfterWrite()
    {
        if (Config::inst()->get('EcommerceAssignOrdersExtension', 'notify_by_email')) {
            if ($this->owner->AssignedAdminID) {
                if ($this->owner->isChanged('AssignedAdminID')) {
                    //$member = $this->owner->AssignedAdmin();
                    $member = Member::get()->byID($this->owner->AssignedAdminID);
                    if ($member && $member->exists() && $member->Email) {
                        $this->owner->sendEmail(
                            $emailClassName = 'OrderInvoiceEmail',
                            $subject = 'An order has been assigned to you on '.Director::absoluteURL('/'),
                            $message = '<p>An order has been assigned to you:</p> <h1><a href="'.$this->owner->CMSEditLink().'">Open Order</a></h1>',
                            $resend = true,
                            $adminOnlyOrToEmail = $member->Email
                        );
                    }
                }
            }
        }
    }
}

