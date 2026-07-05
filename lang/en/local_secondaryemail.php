<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for secondary email local plugin.
 *
 * @package    local_secondaryemail
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['allowuserexclusions'] = 'Allow users to customize notifications';
$string['allowuserexclusions_help'] = 'When enabled, users can disable specific notification types from being sent to their secondary email. Users can only disable notifications that have been enabled by the admin.';
$string['availabletags'] = 'Available tags';
$string['availabletags_help'] = 'Enter one tag per line. These will be available as options when tagging secondary emails.';
$string['confirmationemailbody'] = 'Hello,

you have entered {$a->email} as a secondary email address for {$a->fullname} on {$a->sitename}.

Please confirm the address by clicking this link: {$a->link}

{$a->policyline}

If you did not request this, you can ignore this email.';
$string['confirmationemailpolicy'] = 'Privacy policy: {$a}';
$string['confirmationemailsubject'] = 'Please confirm the secondary email for {$a->fullname}';
$string['confirmationexpired'] = 'The confirmation link has expired. Please request a new confirmation email.';
$string['confirmationinvalid'] = 'The confirmation link is invalid or has expired.';
$string['confirmationpagetitle'] = 'Confirm secondary email';
$string['confirmationsuccess'] = 'The secondary email address has been verified.';
$string['editsecondaryemail'] = 'Edit secondary email';
$string['enabledproviders'] = 'Enable notifications';
$string['enabledproviders_help'] = 'Select which notification types should be sent to the secondary email address. Only checked notifications will be forwarded. Nothing is enabled by default.';
$string['enabletag'] = 'Enable tagging';
$string['enabletag_help'] = 'Allow admins to tag secondary emails (e.g., parent, guardian, employer).';
$string['fieldlockedbyplugin'] = 'Locked by plugin';
$string['invalidsecondaryemailnotice'] = 'The secondary email value was cleared because it is not a valid email address. Please enter a valid address here: {$a}';
$string['invalidtag'] = 'Invalid tag';
$string['noprovidersenabled'] = 'No notification types have been enabled by the administrator for secondary email forwarding.';
$string['notificationsettings'] = 'Notification filter';
$string['notificationsettings_desc'] = 'By default, no notifications are sent to the secondary email address (safe for privacy). Enable specific notification types below.';
$string['pluginname'] = 'Secondary email notifications';
$string['preferencessaved'] = 'Your notification preferences have been saved.';
$string['privacy:metadata:prefdisabled'] = 'Stores whether secondary email sending is disabled for the user.';
$string['privacy:metadata:prefdisabledproviders'] = 'Stores the user\'s disabled notification types for the secondary email.';
$string['privacy:metadata:prefpending'] = 'Stores the pending secondary email address waiting for verification.';
$string['privacy:metadata:prefrelationship'] = 'Stores the tag for the secondary email address.';
$string['privacy:metadata:preftoken'] = 'Stores the verification token for the secondary email address.';
$string['privacy:metadata:preftokentime'] = 'Stores when the verification token was issued.';
$string['privacy:metadata:prefverified'] = 'Stores the verified secondary email address.';
$string['profilecategory'] = 'Additional email';
$string['profilefieldlink'] = 'Profile field settings';
$string['quicklinks'] = 'Quick links';
$string['quicklinks_desc'] = 'Manage profile field visibility and view the user report:';
$string['relationshipemployer'] = 'Employer';
$string['relationshipfather'] = 'Father';
$string['relationshipguardian'] = 'Guardian';
$string['relationshipmother'] = 'Mother';
$string['relationshipother'] = 'Other';
$string['removetagaction'] = 'Remove tag';
$string['secondaryemail:configureown'] = 'Configure own secondary email';
$string['secondaryemail:manage'] = 'Manage secondary emails for other users';
$string['secondaryemail:viewreport'] = 'View the secondary email report';
$string['secondaryemailaddaction'] = 'Add secondary email';
$string['secondaryemailalreadyverified'] = 'The secondary email address is already verified.';
$string['secondaryemailblockaction'] = 'Disable secondary email';
$string['secondaryemailblocked'] = 'Secondary email sending has been disabled for this user.';
$string['secondaryemailblockedtag'] = 'blocked';
$string['secondaryemailcategorylocked'] = 'The secondary email profile category is locked and cannot be edited, moved, or deleted.';
$string['secondaryemailcategorywarning'] = 'This category is managed by the "Secondary Email" plugin. The name is locked.';
$string['secondaryemailconfirmationsent'] = 'Secondary email confirmation has been sent again.';
$string['secondaryemaildeleteaction'] = 'Delete secondary email';
$string['secondaryemaildeleteconfirm'] = 'Delete the secondary email address "{$a}"?';
$string['secondaryemaildeleted'] = 'The secondary email address has been deleted.';
$string['secondaryemailfielddesc'] = 'Additional email address used for verified notification copies.';
$string['secondaryemailfieldlocked'] = 'The secondary email profile field is locked and cannot be renamed or deleted.';
$string['secondaryemailfieldname'] = 'Secondary email';
$string['secondaryemailfieldwarning'] = 'This profile field is managed by the "Secondary Email" plugin. The marked fields are locked to ensure the plugin functions correctly.';
$string['secondaryemailinvalid'] = 'The secondary email address is invalid or not allowed.';
$string['secondaryemaillockedlabel'] = 'Locked';
$string['secondaryemailmissing'] = 'No secondary email address is set for this user.';
$string['secondaryemailnotverified'] = 'not verified yet';
$string['secondaryemailpendingtag'] = 'Pending';
$string['secondaryemailreport'] = 'Users with secondary email';
$string['secondaryemailresendaction'] = 'Resend secondary email confirmation';
$string['secondaryemailstatusfilter'] = 'Secondary email status';
$string['secondaryemailunblockaction'] = 'Enable secondary email';
$string['secondaryemailunblocked'] = 'Secondary email sending has been enabled for this user.';
$string['secondaryemailverifiedtag'] = 'Verified';
$string['settagaction'] = 'Set tag';
$string['taggingsettings'] = 'Tagging';
$string['tagremoved'] = 'Tag removed';
$string['tagset'] = 'Tag set to "{$a}".';
$string['userexclusionsdisabled'] = 'User notification customization is currently disabled by the administrator.';
$string['userpreferences_info'] = 'The following notification types are enabled by your administrator for the secondary email address. Check the ones you want to disable. Checked notifications will NOT be sent to your secondary email.';
$string['userpreferencestitle'] = 'Secondary email notifications';
$string['usersettings'] = 'User customization';
$string['verificationexpiry'] = 'Verification link expiry (hours)';
$string['verificationexpiry_help'] = 'Set how many hours the confirmation link remains valid. Use 0 for no expiry.';
$string['verificationsettings'] = 'Verification';
$string['verifiedemailbody'] = 'Hello,

the email address {$a->email} was successfully confirmed as a secondary email for {$a->fullname} on {$a->sitename}.

From now on, you will receive copies of selected notifications from {$a->sitename}.

If you no longer wish to receive these emails, please contact the site administrator to update the profile settings.';
$string['verifiedemailsubject'] = 'Secondary email confirmed for {$a->fullname}';
