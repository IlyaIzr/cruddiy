Feature: Check admin tables page

  Scenario: Check for errors on Tables page
    Given I am on "/core/tables.php"
    Then I should see "All Available Tables"
    And I should not see "Parse error"
    And I should not see "Fatal error"
