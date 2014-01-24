<?php
/**
 * Controller for members' devices
 */

//TODO: track unknown devices so the network isn't flooded.

class DevicesController extends Controller {

    private $mongoDbConnection = null;
    private $mongoDatabase = null;
    private $membersCollection = null;

    public function init(Pimple $di) {
        $this->mongoDbConnection = new MongoClient;
        $this->mongoDatabase = $this->mongoDbConnection->compaxion;
        $this->membersCollection = $this->mongoDatabase->members;
        $this->listenerController = $di['ListenerController'];
        // need to initialise the members controller so it can respond to events
        $this->membersController = $di['MembersController'];
        // need to initialise the space controller so it can respond to events
        $this->spaceController = $di['SpaceController'];
        //This line simply allows mqtt publishing without actually causing a hook.
        $this->listenerController->listenEvent('device.appear',function (){},true);
        $this->listenerController->listenEvent('device.disappear',function (){},true);
        $this->listenerController->listenEvent('device.unknown.appear',function (){},true);
    }

    public function checkAuthorisation(\Slim\Route $route) {
        //TODO: Actually verify auth
        return true;
    }

    private function getMemberByMac($mac,$fields = Array()) {
        return $this->membersCollection->findOne(array("devices.mac"=>$mac),$fields);

    }

    public function deviceAppears($mac = null) {
        $member = $this->getMemberByMac($mac,Array('username','devices'));
        if (is_null($member)) {
            $this->listenerController->triggerEvent('device.unknown.appear',array('mac'=>$mac));
            return null;
        }
        $membersDevices = $member['devices'];
        foreach ($membersDevices as $arrayindex=>$device) {
            if (
                ($device['mac'] == $mac) &!
                ((array_key_exists('deviceHiddenUntilUnseen',$device) && $device['deviceHiddenUntilUnseen'])) &!
                $device['deviceIsVisible']
            ) {
                $membersDevices[$arrayindex]['deviceIsVisible'] = true;
            }
        }
        if ($membersDevices != $member['devices']) {
            $this->membersCollection->findAndModify(array('username'=>$member['username']),array('$set'=>array('devices'=>$membersDevices)));
            $member['devices'] = $membersDevices;
            $this->listenerController->triggerEvent('device.appear',array('mac' => $mac,'member' => $member['username']));
        }
        return $member;
    }

    public function deviceDisappears($mac = null) {
        $member = $this->getMemberByMac($mac,Array('username','devices'));
        if (is_null($member)) {
            $this->listenerController->triggerEvent('device.unknown.disappear',array('mac'=>$mac));
            return null;
        }
        $membersDevices = $member['devices'];
        foreach ($membersDevices as $arrayindex=>$device) {
            if (($device['mac'] == $mac) && $device['deviceIsVisible']) {
                $membersDevices[$arrayindex]['deviceIsVisible'] = false;
                $membersDevices[$arrayindex]['deviceHiddenUntilUnseen'] = false;
            }
        }
        if ($membersDevices != $member['devices']) {
            $this->membersCollection->findAndModify(array('username'=>$member['username']),array('$set'=>array('devices'=>$membersDevices)));
            $member['devices'] = $membersDevices;
            $this->listenerController->triggerEvent('device.disappear',array('mac' => $mac,'member' => $member['username']));
        }
        return $member;
    }

    public function getDeviceCount() {
        $totalDeviceCount = 0;
        $membersWithDevices = $this->membersCollection->find(array("devices"=>array('$exists'=>1)));
        foreach ($membersWithDevices as $member) {
            $totalDeviceCount += count($member['devices']);
        }
        return $totalDeviceCount;
    }

    public function getDeviceList($member = null) {
        if (!is_null($member)) {
            // first we look at the id
            try {
                $memberDocument = $this->membersCollection->findOne(array('_id' => new MongoId($member)));
            } catch (Exception $e) {
                $memberDocument = null;
            }
            // then the username
            if (is_null($memberDocument)) {
                $memberDocument = $this->membersCollection->findOne(array('username' => $member));
            }
            // if not found, we're looking for devices belonging to someone who isn't a member!
            if (is_null($memberDocument)) {
                //TODO: perhaps a correct status?
                return null;
            }
/*            $cursor =  $this->devicesCollection->find(array('username'=>$memberDocument['username']));
            foreach ($cursor as $device) {
                $results[(string)$device['_id']] = $device;
            }*/
            return $results;
        }
/*        $cursor =  $this->devicesCollection->find();
        foreach ($cursor as $device) {
            $results[(string)$device['_id']] = $device;
        }*/
        return $results;
    }

}
