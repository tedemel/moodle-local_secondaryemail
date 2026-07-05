@local @local_secondaryemail @javascript
Feature: Secondary email notification preferences
  In order to control secondary email notifications
  As a user
  I can manage my notification preferences

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | one@example.com   |
    And the following config values are set as admin:
      | allowuserexclusions | 1 | local_secondaryemail |
      | enabledproviders    | moodle/instantmessage | local_secondaryemail |
    And I log in as "user1"

  Scenario: Preferences page shows enabled providers
    When I am on "/local/secondaryemail/preferences.php"
    Then I should see "Secondary email notifications"
    And I should see "System"
    And I should see "Save changes"

  Scenario: User can save notification preferences
    When I am on "/local/secondaryemail/preferences.php"
    And I click on "Personal messages between users" "checkbox"
    And I press "Save changes"
    Then I should see "Your notification preferences have been saved."
