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
    private $listenerController = null;

    public function init(Pimple $di) {
        $this->mongoDbConnection = new MongoClient;
        $this->mongoDatabase = $this->mongoDbConnection->compaxion;
        $this->spaceCollection = $this->mongoDatabase->space;
        $this->listenerController = $di['ListenerController'];
        //This line simply allows mqtt publishing without actually causing a hook.
        $this->listenerController->listenEvent('space.status.changed',function (){},true);
    }

    public function getStatus() {
        $this->spaceCollection->findOne();
        $document = $this->spaceCollection->findOne();
        if (is_null($document)) {
            $document = $this->defaultStatus;
            $this->spaceCollection->insert($document);
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
        $originalStatus = $document['status'];
        // "Closed", "Close" and "False" are strings that evaluate to true but
        // we want as closed, all else should be true i.e. Open.
        if (strtolower($to) == 'closed' || strtolower($to) == 'close' || strtolower($to) == 'false') {
            $status = 'Closed';
        } else if ($to) {
            $status = 'Open';
        } else {
            $status = 'Closed';
        }
        if ($document['status'] != $status) {
            $this->listenerController->triggerEvent('space.status.changed',array('status' => $status));
        }
        $document['status'] = $status;
        $this->spaceCollection->findAndModify(null,$document);
        return $this->getStatus();
    }

    public function checkAuthorisation(\Slim\Route $route) {
        //TODO: Actually verify auth
        return true;
    }
}