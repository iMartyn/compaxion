<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

//
// Require 3rd-party libraries here:
//
//   require_once 'PHPUnit/Autoload.php';
//   require_once 'PHPUnit/Framework/Assert/Functions.php';
//

/**
 * Features context.
 */
class FeatureContext extends BehatContext
{
    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     *
     * @param array $parameters context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters)
    {
        // Initialize your context here
    }

    /**
     * @Transform /^(\d+)$/
     */
    public function castStringToNumber($string)
    {
        return intval($string);
    }

    /**
     * @Transform /^(zero|one|two|three|four|five|six|seven|eight|nine|ten)$/
     */
    public function castNumberWordsToNumber($string)
    {
        $number = $string;
        switch (strtolower($string)) {
              case "zero" : $number = 0; break;
              case "one" : $number = 1; break;
              case "two" : $number = 2; break;
              case "three" : $number = 3; break;
              case "four" : $number = 4; break;
              case "five" : $number = 5; break;
              case "six" : $number = 6; break;
              case "seven" : $number = 7; break;
              case "eight" : $number = 8; break;
              case "nine" : $number = 9; break;
              case "ten" : $number = 10; break;
        }
        return $number;
    }

    /**
     * @Given /^the device count is (\d+|zero|one|two|three|four|five|six|seven|eight|nine|ten)$/
     */
    public function theDeviceCountIs($devicecount)
    {
        throw new PendingException();
    }

    /**
     * @Given /^nobody is checked in$/
     */
    public function nobodyIsCheckedIn()
    {
        throw new PendingException();
    }

    /**
     * @Then /^we are closed$/
     */
    public function weAreClosed()
    {
        throw new PendingException();
    }

    /**
     * @Given /^the device count is not (\d+|zero|one|two|three|four|five|six|seven|eight|nine|ten)$/
     */
    public function theDeviceCountIsNot($devicecount)
    {
        throw new PendingException();
    }

    /**
     * @Then /^we are open$/
     */
    public function weAreOpen()
    {
        throw new PendingException();
    }

    /**
     * @Given /^somebody is checked in$/
     */
    public function somebodyIsCheckedIn()
    {
        throw new PendingException();
    }

    /**
     * @When /^someone unlocks the upstairs door$/
     */
    public function someoneUnlocksTheUpstairsDoor()
    {
        throw new PendingException();
    }

    /**
     * @Then /^check in member$/
     */
    public function checkInMember()
    {
        throw new PendingException();
    }

    /**
     * @Given /^open the space$/
     */
    public function openTheSpace()
    {
        throw new PendingException();
    }

    /**
     * @When /^someone clocks out$/
     */
    public function someoneClocksOut()
    {
        throw new PendingException();
    }

    /**
     * @Then /^check out member$/
     */
    public function checkOutMember()
    {
        throw new PendingException();
    }

    /**
     * @Given /^all their devices are flagged as "([^"]*)"$/
     */
    public function allTheirDevicesAreFlaggedAs($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Given /^they are the last member present$/
     */
    public function theyAreTheLastMemberPresent()
    {
        throw new PendingException();
    }

    /**
     * @Given /^close the space$/
     */
    public function closeTheSpace()
    {
        throw new PendingException();
    }

    /**
     * @Given /^a member is not checked in$/
     */
    public function aMemberIsNotCheckedIn()
    {
        throw new PendingException();
    }

    /**
     * @When /^a device appears$/
     */
    public function aDeviceAppears()
    {
        throw new PendingException();
    }

    /**
     * @Given /^device belongs to that user$/
     */
    public function deviceBelongsToThatUser()
    {
        throw new PendingException();
    }

    /**
     * @Given /^device belongs to a member$/
     */
    public function deviceBelongsToAMember()
    {
        throw new PendingException();
    }

    /**
     * @Given /^there is only one device in range$/
     */
    public function thereIsOnlyOneDeviceInRange()
    {
        throw new PendingException();
    }

    /**
     * @When /^a device disappears$/
     */
    public function aDeviceDisappears()
    {
        throw new PendingException();
    }

    /**
     * @Given /^there are devices visible$/
     */
    public function thereAreDevicesVisible()
    {
        throw new PendingException();
    }

    /**
     * @When /^the force close button is pressed$/
     */
    public function theForceCloseButtonIsPressed()
    {
        throw new PendingException();
    }

    /**
     * @Then /^set all visible devices to "([^"]*)"$/
     */
    public function setAllVisibleDevicesTo($arg1)
    {
        throw new PendingException();
    }

}
