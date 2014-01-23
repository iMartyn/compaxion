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
        $this->listenerController = $di['ListenerController'];
        //This line simply allows mqtt publishing without actually causing a hook.
        $this->listenerController->listenEvent('member.status.changed',function (){},true);
    }

    public function checkAuthorisation(\Slim\Route $route) {
        //TODO: Actually verify auth
        return true;
    }

    public function checkMemberInOrOut($username,$in) {
        $document = $this->membersCollection->findOne(array('username'=>$username),array('username'=>true,'checked_in'=>true));
        if ($document['checked_in'] != $in) {
            $this->membersCollection->update(array('username'=>$username),array('$set'=>array('checked_in'=>$in)));
            $document['checked_in'] = $in;
            $this->listenerController->triggerEvent('member.status.changed',array('checked_in' => $in,'username' => $username));
        }
        return $document;
    }

    public function checkinMemberByUsername($username) {
        return $this->checkMemberInOrOut($username,true);
    }

    public function checkoutMemberByUsername($username) {
        return $this->checkMemberInOrOut($username,false);
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
