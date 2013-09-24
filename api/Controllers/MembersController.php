<?php
/**
 * Controller for members
 */

class MembersController extends Controller {

    private $mongoDbConnection = null;
    private $mongoDatabase = null;
    private $membersCollection = null;

    public function init(Pimple $di) {
        $this->mongoDbConnection = new MongoClient;
        $this->mongoDatabase = $this->mongoDbConnection->compaxion;
        $this->membersCollection = $this->mongoDatabase->members;
    }

    public function checkAuthorisation(\Slim\Route $route) {
        //TODO: Actually verify auth
        return true;
    }

    public function getMemberCount() {
        return $this->membersCollection->count();
    }

    public function getMemberByUsername($username) {
        return $this->membersCollection->findOne(array('username'=>$username));
    }

    public function getAllMembers() {
        $cursor = $this->membersCollection->find();
        foreach ($cursor as $member) {
            $results[(string)$member['_id']] = $member;
        }
        return $results;
    }

}