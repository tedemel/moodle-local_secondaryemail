@local @local_secondaryemail @javascript
Feature: Secondary email field protection
  In order to protect the secondary email profile field
  As an admin
  I should see it locked in the profile field manager

  Background:
    Given I log in as "admin"

  Scenario: Locked badge appears for secondary email field
    When I visit "/user/profile/index.php"
    Then ".secondaryemail-locked-badge" "css_element" should exist
