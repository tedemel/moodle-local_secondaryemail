@local @local_secondaryemail @javascript
Feature: Secondary email management report
  In order to manage secondary email addresses
  As an admin
  I can see status tags and actions in the secondary email report

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | user1    | User      | One      | one@example.com      |
      | user2    | User      | Two      | two@example.com      |
      | user3    | User      | Three    | three@example.com    |
      | user4    | User      | Four     | four@example.com     |
    And the secondary email for user "user1" is set to "verified@example.com"
    And the secondary email for user "user1" is verified
    And the secondary email for user "user2" is set to "pending@example.com"
    And the secondary email for user "user2" has a pending confirmation
    And the secondary email for user "user3" is set to "blocked@example.com"
    And the secondary email for user "user3" is blocked
    And I log in as "admin"

  Scenario: Show secondary email status tags and add action
    When I navigate to "Users > Accounts > Users with secondary email" in site administration
    Then I should see "Verified" in the "User One" "table_row"
    And I should see "Pending" in the "User Two" "table_row"
    And I should see "blocked" in the "User Three" "table_row"
    And I should see "Add secondary email" in the "User Four" "table_row"

  Scenario: Block a user's secondary email
    When I navigate to "Users > Accounts > Users with secondary email" in site administration
    And I click on "Actions" "button" in the "User One" "table_row"
    And I click on "Disable secondary email" "link"
    Then I should see "Secondary email sending has been disabled"
    And I should see "blocked" in the "User One" "table_row"

  Scenario: Unblock a user's secondary email
    Given the secondary email for user "user1" is blocked
    When I navigate to "Users > Accounts > Users with secondary email" in site administration
    And I click on "Actions" "button" in the "User One" "table_row"
    And I click on "Enable secondary email" "link"
    Then I should see "Secondary email sending has been enabled"

  Scenario: Resend confirmation email for pending secondary email
    When I navigate to "Users > Accounts > Users with secondary email" in site administration
    And I click on "Actions" "button" in the "User Two" "table_row"
    And I click on "Resend secondary email confirmation" "link"
    Then I should see "Secondary email confirmation has been sent again"

  Scenario: Delete a user's secondary email
    When I navigate to "Users > Accounts > Users with secondary email" in site administration
    And I click on "Actions" "button" in the "User One" "table_row"
    And I click on "Delete secondary email" "link"
    And I click on "Delete" "button" in the "Confirm" "dialogue"
    Then I should see "The secondary email address has been deleted"
    And I should see "Add secondary email" in the "User One" "table_row"

  Scenario: Resend not available for already verified email
    When I navigate to "Users > Accounts > Users with secondary email" in site administration
    And I click on "Actions" "button" in the "User One" "table_row"
    Then I should not see "Resend secondary email confirmation"

  Scenario: Filter report by verification status
    When I navigate to "Users > Accounts > Users with secondary email" in site administration
    And I click on "Filters" "button"
    And I set the field "Secondary email status operator" to "Is equal to"
    And I set the field "Secondary email status value" to "Verified"
    And I click on "Apply" "button" in the "[data-region='report-filters']" "css_element"
    Then I should see "User One"
    And I should not see "User Two"
    And I should not see "User Three"
    And I should not see "User Four"

  Scenario: Set relationship tag from report
    Given the following config values are set as admin:
      | enablerelationshiptag | 1 | local_secondaryemail |
      | relationshiptags      | mother | local_secondaryemail |
    When I navigate to "Users > Accounts > Users with secondary email" in site administration
    And I click on "Actions" "button" in the "User One" "table_row"
    And I click on "Set tag: Mother" "link"
    Then I should see "Mother" in the "User One" "table_row"
