<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;
use Guzzle\Http\Client;

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

    private $mongoDbConnection = null;
    private $mongoDatabase = null;
    private $spaceCollection = null;
    private $membersCollection = null;
    private $restClient = null;
    private $arbitraryMember = null;

    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     *
     * @param array $parameters context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters)
    {
        // Initialize your context here
	$this->restClient = new Client('http://api.compaxion-vm.dev');
    }

    private function getRandomMember($checkedin = null) {
        if (is_null($checkedin)) {
            // We don't care if they're checked in or not
            $params = array();
        } else {
            // We do!
            $params = array('checked_in'=>$checkedin);
        }
        $cursor = $this->membersCollection->find($params);
        $record = $cursor->limit(-1)->skip(rand(0,$cursor->count()))->getNext();
        $this->arbitraryMember = $record;
        echo "We are using {$record['username']} as the arbritary member.";
        return $record; //don't have to but might as well.
    }

    /**
     * @BeforeScenario @database
     */
    public function cleanDatabase()
    {
        // clean database before @database scenarios
        if (is_null($this->mongoDatabase)) {
            $this->mongoDbConnection = new MongoClient;
            $this->mongoDatabase = $this->mongoDbConnection->compaxion;
            $this->spaceCollection = $this->mongoDatabase->space;
            $this->membersCollection = $this->mongoDatabase->members;
            $this->devicesCollection = $this->mongoDatabase->devices;
        }
        $document = $this->spaceCollection->remove();
        $defaultMembersPresent = 2;
	$document = array('status' => 'Open', 'temperature' => 'Like Hoth', 'members_here' => $defaultMembersPresent);
        $this->spaceCollection->insert($document);
        $document = $this->membersCollection->remove();
        for ($i=1;$i<=10;$i++) {
            $memberIsPresent = ($i <= $defaultMembersPresent);
            $memberUserName = '';
            for ($c=1;$c<=6;$c++) {
                $memberUserName .= chr(rand(ord('a'),ord('z')));
            }
            $deviceMac = '';
            for ($c=1;$c<15;$c++) {
                if (($c % 3) == 0) {
                    $deviceMac .= ':';
                } else {
                    $deviceMac .= dechex(rand(0,0xf));
                }
            }
            $document = array('username' => $memberUserName, 'checked_in' => $memberIsPresent, 'devices' => array('mac' => $deviceMac, 'desc' => $memberUserName . "'s phone", 'deviceIsVisible' => $memberIsPresent));
            $this->membersCollection->insert($document);
	}
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
        $this->membersCollection->update(array('devices.deviceIsVisible'=>true,'devices.deviceHiddenUntilUnseen'=>array('$ne'=>true)),array('$set'=>array('devices.deviceIsVisible'=>false)),array('multiple'=>true));
        for ($i=0;$i<$this->castNumberWordsToNumber($devicecount);$i++) {
            $doc = $this->membersCollection->findOne(array('devices.deviceIsVisible'=>false));
            $userName = $doc['username'];
	    $this->membersCollection->update(array('username'=>$userName),array('$set'=>array('devices.deviceIsVisible'=>true)));
        }
    }

    /**
     * @Given /^nobody is checked in$/
     */
    public function nobodyIsCheckedIn()
    {
        $this->membersCollection->update(array(),array('$set'=>array('checked_in'=>false)),array('multiple'=>true));
    }

    /**
     * @Then /^we are closed$/
     */
    public function weAreClosed()
    {
        $status = $this->restClient->get('/space/status.json')->send()->json();
        if (is_array($status) && array_key_exists('status',$status)) {
            if (strToLower($status['status']) !== 'closed') {
                throw new Exception('Expected status to be "closed" - got '.$status['status']);
            }
        } else {
            throw new Exception('Unexpected return from API');
        }
    }

    /**
     * @Given /^the device count is not (\d+|zero|one|two|three|four|five|six|seven|eight|nine|ten)$/
     */
    public function theDeviceCountIsNot($devicecount)
    {
        $this->membersCollection->update(array('devices.deviceIsVisible'=>true,'devices.deviceHiddenUntilUnseen'=>array('$ne'=>true)),array('$set'=>array('devices.deviceIsVisible'=>false)),array('multiple'=>true));
        for ($i=0;$i<=$this->castNumberWordsToNumber($devicecount);$i++) {
            $doc = $this->membersCollection->findOne(array('devices.deviceIsVisible'=>false));
            $userName = $doc['username'];
	    $this->membersCollection->update(array('username'=>$userName),array('$set'=>array('devices.deviceIsVisible'=>true)));
        }
    }

    /**
     * @Then /^we are open$/
     */
    public function weAreOpen()
    {
        $status = $this->restClient->get('/space/status.json')->send()->json();
        if (is_array($status) && array_key_exists('status',$status)) {
            if (strToLower($status['status']) !== 'open') {
                throw new Exception('Expected status to be "open" - got '.$status['status']);
            }
        } else {
            throw new Exception('Unexpected return from API');
        }
    }

    /**
     * @Given /^somebody is checked in$/
     */
    public function somebodyIsCheckedIn()
    {
        $this->nobodyIsCheckedIn();
        $member = $this->getRandomMember();
        $this->membersCollection->update(array('username'=>$member['username']),array('$set'=>array('checked_in'=>true)));
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
        $member = $this->getRandomMember(true);
	$status = $this->restClient->get('/member/'.$member['username'].'/checkout.json')->send()->json();
    }

    /**
     * @Then /^check out member$/
     */
    public function checkOutMember()
    {
        $status = $this->restClient->get('/member/'.$this->arbitraryMember['username'].'.json')->send()->json();
        if ($status['checked_in']) {
            throw new Exception('Expected '.$this->arbitraryMember['username'].' to be checked out but they were checked in!');
        }
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
        $membersHereCount = $this->membersCollection->find(array('checked_in'=>true))->count();
        if ($membersHereCount !== 1) {
            throw new Exception('Expecting exactly 1 member present, got '.$membersHereCount);
        }
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
        $this->getRandomMember(false);
        echo "We are using {$this->arbitraryMember['username']} for the user who is not checked in.\n";
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
        $this->theDeviceCountIs(1);
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
        $this->theDeviceCountIsNot(0);
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
