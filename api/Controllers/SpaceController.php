<?php
/**
 * Space Controller - Lazy Loaded by Pimple
 */

require_once 'Controller.php';

class SpaceController extends Controller {

    // Php doesn't allow class constants of arrays!
    private $defaultStatus = array('status' => 'Open', 'temperature' => 'Like Hoth', 'members_here' => 2);

    private $mongoDbConnection = null;
    private $mongoDatabase = null;
    private $spaceCollection = null;
    private $membersCollection = null;
    private $listenerController = null;
    private $membersController = null;
    private $di = null;

    public function init(Pimple $di) {
        $this->mongoDbConnection = new MongoClient;
        $this->mongoDatabase = $this->mongoDbConnection->compaxion;
        $this->spaceCollection = $this->mongoDatabase->space;
        $this->membersCollection = $this->mongoDatabase->members;
        $this->listenerController = $di['ListenerController'];
        $this->listenerController->listenEvent('member.status.changed',function ($data){ $this->closeOrOpenIfWeShouldbe(); },true);
        $this->listenerController->listenEvent('device.disappear',function ($data){ $this->closeOrOpenIfWeShouldbe(); },true);
        $this->listenerController->listenEvent('device.appear',function ($data){ $this->closeOrOpenIfWeShouldbe(); },true);
        //This line simply allows mqtt publishing without actually causing a hook.
        $this->listenerController->listenEvent('space.status.changed',function ($data){},true);
        $this->di = $di;
    }

    private function closeOrOpenIfWeShouldbe() {
        if ($this->areWeOpen()) {
            $this->setStatus('open');
        } else {
            $this->setStatus('closed');
        }
    }

    private function areWeOpen() {
        $checkedInUsers = $this->membersCollection->find(array('checked_in'=>true));
        $presentDevices = $this->membersCollection->find(array('devices.deviceIsVisible'=>true,'devices.deviceHiddenUntilUnseen'=>array('$ne'=>true)));
        return ($presentDevices->count() > 0 || $checkedInUsers->count() > 0);
    }

    public function getStatus($trustTheDB = false) {
        $this->spaceCollection->findOne();
        $document = $this->spaceCollection->findOne();
        if (is_null($document)) {
            $document = $this->defaultStatus;
            $this->spaceCollection->insert($document);
        }
        if (!$trustTheDB) {
             if ($this->areWeOpen()) {
                $this->setStatus('open');
                $document['status'] = 'Open';
             } else {
                $this->setStatus('closed');
                $document['status'] = 'Closed';
            }
        }
        return $document;
    }

    public function setStatus($to) {
        $this->spaceCollection->findOne();
        $document = $this->spaceCollection->findOne();
        if (is_null($document)) {
            $document = $this->defaultStatus;
            $this->spaceCollection->insert($document);
        }
        unset($document['_id']);
        // "Closed", "Close" and "False" are strings that evaluate to true but
        // we want as closed, all else should be true i.e. Open.
        if (strtolower($to) == 'closed' || strtolower($to) == 'close' || strtolower($to) == 'false') {
            $status = 'Closed';
        } else if ($to) {
            $status = 'Open';
        } else {
            $status = 'Closed';
        }
        if ($status == 'Closed') {
            //We just closed.  Force close button pressed.
            $membersController = $this->di['MembersController'];
            $devicesController = $this->di['DevicesController'];
            // Set the devices hidden first or the user will check back in!
            $presentDevices = $this->membersCollection->find(array('devices.deviceIsVisible'=>true,'devices.deviceHiddenUntilUnseen'=>array('$ne'=>true)));
            foreach ($presentDevices as $actuallyAMemberRecord) {
                $devicesController->hideUsersDevices($actuallyAMemberRecord['username']);
            }
            $membersController->checkOutAllMembers();
            $this->listenerController->triggerEvent('space.status.changed',array('status' => $status));
        }
        $document['status'] = $status;
        $this->spaceCollection->findAndModify(null,$document);
        return $this->getStatus(true);
    }

    public function checkAuthorisation(\Slim\Route $route) {
        //TODO: Actually verify auth
        return true;
    }
}
