@local @local_atrisk
Feature: At-risk students
  In order to help students before they fail
  As a manager of a course category
  I need to see who may be falling behind and record who contacted them

  Background:
    Given the following "categories" exist:
      | name    | category | idnumber |
      | Science | 0        | SCI      |
    And the following "courses" exist:
      | fullname  | shortname | category |
      | Biology 1 | BIO1      | SCI      |
    And the following "users" exist:
      | username | firstname | lastname |
      | away     | Ana       | Away     |
      | late     | Luis      | Late     |
      | low      | Lia       | Low      |
      | fine     | Fabio     | Fine     |
      | manager1 | Marta     | Manager  |
      | teacher1 | Tomas     | Teacher  |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | away     | BIO1   | student        |
      | late     | BIO1   | student        |
      | low      | BIO1   | student        |
      | fine     | BIO1   | student        |
      | teacher1 | BIO1   | editingteacher |
    And the following "role assigns" exist:
      | user     | role    | contextlevel | reference |
      | manager1 | manager | Category     | SCI       |
    And the following "last access times" exist:
      | user | course | lastaccess      |
      | away | BIO1   | ##20 days ago## |
      | late | BIO1   | ##1 day ago##   |
      | low  | BIO1   | ##1 day ago##   |
      | fine | BIO1   | ##1 day ago##   |
    And the following "activities" exist:
      | activity | course | name    | duedate        | assignsubmission_onlinetext_enabled |
      | assign   | BIO1   | Essay 1 | ##3 days ago## | 1                                   |
      | assign   | BIO1   | Essay 2 | ##2 days ago## | 1                                   |
    And the following "mod_assign > extensions" exist:
      | assign  | user | extensionduedate |
      | Essay 1 | away | ##+1 week##      |
      | Essay 2 | away | ##+1 week##      |
      | Essay 1 | low  | ##+1 week##      |
      | Essay 2 | low  | ##+1 week##      |
      | Essay 1 | fine | ##+1 week##      |
      | Essay 2 | fine | ##+1 week##      |
    And the following "grade items" exist:
      | itemname   | course | grademax |
      | Final exam | BIO1   | 100      |
    And the following "grade grades" exist:
      | gradeitem  | user | finalgrade |
      | Final exam | low  | 35.00      |
      | Final exam | fine | 80.00      |
    And I run the scheduled task "\local_atrisk\task\refresh_flags"

  Scenario: A manager sees who is at risk and why, and records a contact
    Given I log in as "manager1"
    When I visit "/local/atrisk/index.php"
    And I follow "Science"
    Then the following should exist in the "local-atrisk-table" table:
      | Student   | Course    | Why listed              |
      | Ana Away  | Biology 1 | 20 days without a visit |
      | Luis Late | Biology 1 | 2 missed                |
      | Lia Low   | Biology 1 | Course total 35%        |
    And I should not see "Fabio Fine"
    And "Ana Away" row "Last contact" column of "local-atrisk-table" table should contain "Not contacted yet"
    When I click on "Record contact Ana Away" "link"
    And I set the field "Note" to "Phoned. Back next week."
    And I press "Record contact"
    Then I should see "Contact with Ana Away recorded."
    And "Ana Away" row "Last contact" column of "local-atrisk-table" table should contain "Marta Manager"
    And "Ana Away" row "Last contact" column of "local-atrisk-table" table should contain "Phoned. Back next week."
    And "Ana Away" row "Why listed" column of "local-atrisk-table" table should contain "20 days without a visit"

  Scenario: A teacher without the capability cannot open the page
    Given I log in as "teacher1"
    When I visit "/local/atrisk/index.php"
    Then I should see "You cannot see at-risk students in any category"
    And I should not see "Science"
