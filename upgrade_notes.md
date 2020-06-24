2020-06-24 01:43

# running php upgrade upgrade see: https://github.com/silverstripe/silverstripe-upgrader
cd /var/www/upgrades/ecommerce_assign_orders
php /var/www/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code upgrade /var/www/upgrades/ecommerce_assign_orders/ecommerce_assign_orders  --root-dir=/var/www/upgrades/ecommerce_assign_orders --write -vvv
Writing changes for 3 files
Running upgrades on "/var/www/upgrades/ecommerce_assign_orders/ecommerce_assign_orders"
[2020-06-24 13:43:22] Applying RenameClasses to EcommerceAssignOrdersTest.php...
[2020-06-24 13:43:22] Applying ClassToTraitRule to EcommerceAssignOrdersTest.php...
[2020-06-24 13:43:22] Applying UpdateConfigClasses to config.yml...
[2020-06-24 13:43:22] Applying RenameClasses to EcommerceAssignOrdersExtension.php...
[2020-06-24 13:43:22] Applying ClassToTraitRule to EcommerceAssignOrdersExtension.php...
[2020-06-24 13:43:22] Applying RenameClasses to _config.php...
[2020-06-24 13:43:22] Applying ClassToTraitRule to _config.php...
modified:	tests/EcommerceAssignOrdersTest.php
@@ -1,4 +1,6 @@
 <?php
+
+use SilverStripe\Dev\SapphireTest;

 class EcommerceAssignOrdersTest extends SapphireTest
 {

modified:	_config/config.yml
@@ -3,8 +3,7 @@
 Before: 'app/*'
 After: 'framework/*','cms/*','ecommerce/*'
 ---
+Sunnysideup\Ecommerce\Model\Order:
+  extensions:
+    - Sunnysideup\EcommerceAssignOrders\Model\EcommerceAssignOrdersExtension

-Order:
-  extensions:
-    - EcommerceAssignOrdersExtension
-

modified:	src/Model/EcommerceAssignOrdersExtension.php
@@ -2,13 +2,23 @@

 namespace Sunnysideup\EcommerceAssignOrders\Model;

-use DataExtension;
-use FieldList;
-use EcommerceRole;
-use Config;
-use DropdownField;
-use Member;
-use Director;
+
+
+
+
+
+
+
+use SilverStripe\Security\Member;
+use SilverStripe\Forms\FieldList;
+use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
+use SilverStripe\Core\Config\Config;
+use Sunnysideup\EcommerceAssignOrders\Model\EcommerceAssignOrdersExtension;
+use SilverStripe\Forms\DropdownField;
+use Sunnysideup\Ecommerce\Email\OrderInvoiceEmail;
+use SilverStripe\Control\Director;
+use SilverStripe\ORM\DataExtension;
+



@@ -23,7 +33,7 @@
 class EcommerceAssignOrdersExtension extends DataExtension
 {
     private static $has_one = array(
-        'AssignedAdmin' => 'Member'
+        'AssignedAdmin' => Member::class
     );

     private static $casting = array(
@@ -77,7 +87,7 @@
     protected function getAssignedAdminDropdown()
     {
         $shopAdminAndCurrentCustomerArray = EcommerceRole::list_of_admins(true);
-        $titleArray = Config::inst()->get('EcommerceAssignOrdersExtension', 'searchable_fields');
+        $titleArray = Config::inst()->get(EcommerceAssignOrdersExtension::class, 'searchable_fields');
         $title = isset($titleArray['AssignedAdminID']['title']) ? $titleArray['AssignedAdminID']['title'] : 'Admin';
         return DropdownField::create(
             'AssignedAdminID',
@@ -88,14 +98,14 @@

     public function onAfterWrite()
     {
-        if (Config::inst()->get('EcommerceAssignOrdersExtension', 'notify_by_email')) {
+        if (Config::inst()->get(EcommerceAssignOrdersExtension::class, 'notify_by_email')) {
             if ($this->owner->AssignedAdminID) {
                 if ($this->owner->isChanged('AssignedAdminID')) {
                     //$member = $this->owner->AssignedAdmin();
                     $member = Member::get()->byID($this->owner->AssignedAdminID);
                     if ($member && $member->exists() && $member->Email) {
                         $this->owner->sendEmail(
-                            $emailClassName = 'OrderInvoiceEmail',
+                            $emailClassName = OrderInvoiceEmail::class,
                             $subject = 'An order has been assigned to you on '.Director::absoluteURL('/'),
                             $message = '<p>An order has been assigned to you:</p> <h1><a href="'.$this->owner->CMSEditLink().'">Open Order</a></h1>',
                             $resend = true,

Writing changes for 3 files
✔✔✔