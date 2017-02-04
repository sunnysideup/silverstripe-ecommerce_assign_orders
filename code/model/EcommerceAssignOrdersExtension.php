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

}
