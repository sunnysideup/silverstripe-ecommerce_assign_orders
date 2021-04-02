<?php

namespace Sunnysideup\EcommerceAssignOrders\Model;

use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Member;
use Sunnysideup\Ecommerce\Email\OrderInvoiceEmail;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;

class EcommerceAssignOrdersExtension extends DataExtension
{
    private static $has_one = [
        'AssignedAdmin' => Member::class,
    ];

    private static $casting = [
        'AssignedAdminNice' => 'Varchar',
    ];

    private static $summary_fields = [
        'AssignedAdminNice' => 'Admin',
    ];

    private static $searchable_fields = [
        'AssignedAdminID' => [
            'field' => TextField::class,
            'filter' => 'ExactMatchFilter',
            'title' => 'Assigned Admin',
        ],
    ];

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

    public function onAfterWrite()
    {
        if (Config::inst()->get(EcommerceAssignOrdersExtension::class, 'notify_by_email')) {
            if ($this->owner->AssignedAdminID) {
                if ($this->owner->isChanged('AssignedAdminID')) {
                    //$member = $this->owner->AssignedAdmin();
                    $member = Member::get()->byID($this->owner->AssignedAdminID);
                    if ($member && $member->exists() && $member->Email) {
                        $this->owner->sendEmail(
                            $emailClassName = OrderInvoiceEmail::class,
                            $subject = 'An order has been assigned to you on ' . Director::absoluteURL('/'),
                            $message = '<p>An order has been assigned to you:</p> <h1><a href="' . $this->owner->CMSEditLink() . '">Open Order</a></h1>',
                            $resend = true,
                            $adminOnlyOrToEmail = $member->Email
                        );
                    }
                }
            }
        }
    }

    protected function getAssignedAdminDropdown()
    {
        $shopAdminAndCurrentCustomerArray = EcommerceRole::list_of_admins(true);
        $titleArray = Config::inst()->get(EcommerceAssignOrdersExtension::class, 'searchable_fields');
        $title = isset($titleArray['AssignedAdminID']['title']) ? $titleArray['AssignedAdminID']['title'] : 'Admin';
        return DropdownField::create(
            'AssignedAdminID',
            $title,
            $shopAdminAndCurrentCustomerArray
        );
    }
}
