<?php
/**
 * Space Controller - Lazy Loaded by Pimple
 */

require_once 'Controller.php';

class SpaceController extends Controller {

    private $mongoDbConnection = null;
    private $mongoDatabase = null;
    private $spaceCollection = null;

    public function init(Pimple $di) {
        $this->mongoDbConnection = new MongoClient;
        $this->mongoDatabase = $this->mongoDbConnection->compaxion;
        $this->spaceCollection = $this->mongoDatabase->space;
    }

    public function getStatus() {
        $this->spaceCollection->findOne();
        $document = $this->spaceCollection->findOne();
        if (is_null($document)) {
            $document = array('status' => 'Open', 'temperature' => 'Like Hoth', 'members_here' => 2);
            $this->spaceCollection->insert($document);
        }
        return $document;
    }
}