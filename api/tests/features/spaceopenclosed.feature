@database
Feature: Space opens and closes based on occupancy
  In order for the space to be opened or closed
  The occupancy based on checkins/outs and devices should be at cetain levels

  Scenario: When there is nobody here, we're closed
    Given the device count is zero
    And nobody is checked in
    Then we are closed

  Scenario: When there are devices here, we're open
    Given the device count is not zero
    Then we are open

  Scenario: When there are people here, we're open
    Given somebody is checked in
    Then we are open

  Scenario: When someone unlocks the door, the space opens
    Given nobody is checked in
    When someone unlocks the upstairs door
    Then they are checked in
    And we are open

  Scenario: When user clocks out, ignore their devices until unseen
    Given somebody is checked in
    When someone clocks out
    Then they are checked out
    And all their visible devices are flagged as "ignored until unseen"

  Scenario: When the last user clocks out, the space closes
    Given somebody is checked in
    And they are the last member present
    When they clock out
    Then they are checked out
    And all their visible devices are flagged as "ignored until unseen"
    And we are closed

  Scenario: When a device appears, that member is here
    Given a member is not checked in
    When a device appears belonging to them
    Then they are checked in

  Scenario: When the first device is seen and the space is closed, people are here, so we're open
    Given the device count is zero
    And nobody is checked in
    When a device appears belonging to a member
    Then they are checked in
    And we are open

  Scenario: When the last unignored device vanishes, the space closes if there is noone checked in
    Given nobody is checked in
    And there is only one device in range
    When a device disappears
    Then we are closed

  Scenario: When the space is open, and the "Force close" button is pressed, close
    Given somebody is checked in
    And there are devices visible
    When the force close button is pressed
    Then set all visible devices to "ignored until unseen"
    And we are closed
