@local @local_secondaryemail @javascript
Feature: Secondary email field protection
  In order to protect the secondary email profile field
  As an admin
  I should see it locked in the profile field manager

  Background:
    Given the following "custom profile fields" exist:
      | datatype | shortname       | name             |
      | text     | secondaryemail  | Secondary email  |
    And I log in as "admin"

  Scenario: Locked badge appears for secondary email field
    When I am on "/user/profile/index.php"
    Then I should see "Locked"
