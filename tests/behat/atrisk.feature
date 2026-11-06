@local @local_atrisk
Feature: At-risk students
  One page per course category listing students who may be falling behind: no visit to a course for a while, missed assignment deadlines, or a course total under a threshold. Staff mark who they contacted, so nobody is chased twice and nobody is forgotten.

  # TODO acceptance: A manager opens a category and sees a student who has not opened a course in 20 days, with the reason.
  # TODO acceptance: A student with two missed assignment deadlines is listed with "2 missed".
  # TODO acceptance: A student whose course total is under the threshold is listed with their grade.
  # TODO acceptance: A student with no problems is not listed.
  # TODO acceptance: A manager marks a student as contacted with a note; the row shows who and when, and the student stays listed until the reason clears.
  # TODO acceptance: A teacher without the capability cannot open the page.

  Scenario: An admin opens the main page
    Given I log in as "admin"
    When I visit "/local/atrisk/index.php"
    Then I should see "At-risk students" in the "page-header" "region"
