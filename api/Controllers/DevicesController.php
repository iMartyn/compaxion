<?php
/**
 * Controller for members' devices
 */

class DevicesController extends Controller {

    private $mongoDbConnection = null;
    private $mongoDatabase = null;
    private $membersCollection = null;
    private $devicesCollection = null;

    public function init(Pimple $di) {
        $this->mongoDbConnection = new MongoClient;
        $this->mongoDatabase = $this->mongoDbConnection->compaxion;
        $this->membersCollection = $this->mongoDatabase->members;
        $this->devicesCollection = $this->mongoDatabase->devices;
    }

    public function checkAuthorisation(\Slim\Route $route) {
        //TODO: Actually verify auth
        return true;
    }

    public function getDeviceCount() {
        return $this->devicesCollection->count();
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
            $cursor =  $this->devicesCollection->find(array('username'=>$memberDocument['username']));
            foreach ($cursor as $device) {
                $results[(string)$device['_id']] = $device;
            }
            return $results;
        }
        $cursor =  $this->devicesCollection->find();
        foreach ($cursor as $device) {
            $results[(string)$device['_id']] = $device;
        }
        return $results;
    }

}