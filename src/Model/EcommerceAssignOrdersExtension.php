<?php

namespace Sunnysideup\EcommerceAssignOrders\Model;

use SilverStripe\Core\Extension;
use Sunnysideup\Ecommerce\Model\Order;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Security\Member;
use Sunnysideup\Ecommerce\Email\OrderInvoiceEmail;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;

/**
 * Class \Sunnysideup\EcommerceAssignOrders\Model\EcommerceAssignOrdersExtension
 *
 * @property Order|EcommerceAssignOrdersExtension $owner
 * @property int $AssignedAdminID
 * @method Member AssignedAdmin()
 */
class EcommerceAssignOrdersExtension extends Extension
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
        if ($this->getOwner()->AssignedAdminID) {
            $admin = $this->getOwner()->AssignedAdmin();
            if ($admin && $admin->exists()) {
                return $admin->getTitle();
            }
        }

        return 'n/a';
    }

    /**
     * Update Fields.
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab(
            'Root.Process',
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
        if (Config::inst()->get(EcommerceAssignOrdersExtension::class, 'notify_by_email') && $this->getOwner()->AssignedAdminID && $this->getOwner()->isChanged('AssignedAdminID')) {
            //$member = $this->getOwner()->AssignedAdmin();
            $member = Member::get_by_id($this->getOwner()->AssignedAdminID);
            if ($member && $member->exists() && $member->Email) {
                $this->getOwner()->sendEmail(
                    $emailClassName = OrderInvoiceEmail::class,
                    $subject = 'An order has been assigned to you on ' . Director::absoluteURL('/'),
                    $message = '<p>An order has been assigned to you:</p> <h1><a href="' . $this->getOwner()->CMSEditLink() . '">Open Order</a></h1>',
                    $resend = true,
                    $adminOnlyOrToEmail = $member->Email
                );
            }
        }
    }

    protected function getAssignedAdminDropdown()
    {
        $shopAdminAndCurrentCustomerArray = EcommerceRole::list_of_admins(true);
        $titleArray = Config::inst()->get(EcommerceAssignOrdersExtension::class, 'searchable_fields');
        $title = $titleArray['AssignedAdminID']['title'] ?? 'Admin';

        return DropdownField::create(
            'AssignedAdminID',
            $title,
            $shopAdminAndCurrentCustomerArray
        );
    }
}
