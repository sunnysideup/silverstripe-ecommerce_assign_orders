<?php

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
            'title' => 'Admin'
        )
    );

    private static $notify_by_email = true;

    public function getAssignedAdminNice()
    {
        if($this->owner->AssignedAdminID) {
            if($admin = $this->owner->AssignedAdmin()) {
                if($admin->exists()) {
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
            'OrderSummaryHeader'
        );
    }

    public function scaffoldSearchFields($fieldList, $_params)
    {
        $fieldList->push($this->getAssignedAdminDropdown());
    }

    protected function getAssignedAdminDropdown()
    {
        $shopAdminAndCurrentCustomerArray = array_merge(
            EcommerceRole::list_of_admins(true)
        );
        $titleArray = Config::inst()->get('EcommerceAssignOrdersExtension', 'searchable_fields');
        $title = isset($titleArray['AssignedAdminID']['title']) ? $titleArray['AssignedAdminID']['title'] : 'Admin';
        return DropdownField::create(
            'AssignedAdminID',
            $title,
            $shopAdminAndCurrentCustomerArray
        );
    }

    public function onBeforeWrite()
    {
        if(Config::inst()->get('EcommerceAssignOrdersExtension', 'notify_by_email')) {
            if($this->owner->AssignedAdminID) {
                if($this->owner->isChanged('AssignedAdminID')) {
                    $member = $this->owner->AssignedAdmin();
                    if($member && $member->exists() && $member->Email) {
                        $this->owner->sendEmail(
                            $emailClassName = 'Order_InvoiceEmail',
                            $subject = 'An order has been assigned to you on '.Director::absoluteURL(),
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
