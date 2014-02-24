<?php
/**
 * Controller for members
 */

class MembersController extends Controller {

    protected $listenerController;

    public function registerListeners(Pimple $di) {
        $this->listenerController = $di['ListenerController'];
        $this->listenerController->listenEvent('device.appear',function($data) { $this->membersDeviceAppears($data); },true);
        $this->listenerController->listenEvent('device.disappear',function($data) { $this->membersDeviceDisappears($data); },true);
        //This line simply allows mqtt publishing without actually causing a hook.
        $this->listenerController->listenEvent('member.status.changed',function (){},true);
    }

    public function loadOtherRequiredControllers(Pimple $di) {
        // need to initialise the space controller so it can respond to events
        $this->spaceController = $di['SpaceController'];
    }

    private function isMemberCheckedIn($username) {
        $member = $this->getMemberByUsername($username);
        if (is_null($member) || (!array_key_exists('checked_in',$member)) || !$member['checked_in']) {
            return false;
        } else {
            return true;
        }
    }

    private function membersDeviceAppears($data) {
        if (!$this->isMemberCheckedIn($data['member']))
            $this->checkMemberInOrOut($data['member'],true);
    }

    private function membersDeviceDisappears($data) {
        if ($this->isMemberCheckedIn($data['member']))
            $this->checkMemberInOrOut($data['member'],false);
    }

    private function ignoreDevicesUntilGone($username) {
        $member = $this->getMemberByUsername($username);
        $devices = $member['devices'];
        foreach ($devices as $index=>$device) {
            if ($device['deviceIsVisible']) {
                $devices[$index]['deviceHiddenUntilUnseen'] = true;
            }
        }
        if ($devices !== $member['devices']) {
            $this->membersCollection->findAndModify(array('username'=>$username),array('$set'=>array('devices'=>$devices)));
        }
    }

    public function checkMemberInOrOut($username,$in) {
        $document = $this->membersCollection->findOne(array('username'=>$username),array('username'=>true,'checked_in'=>true));
        if ($document['checked_in'] != $in) {
            $this->membersCollection->update(array('username'=>$username),array('$set'=>array('checked_in'=>$in)));
            $document['checked_in'] = $in;
            $this->listenerController->triggerEvent('member.status.changed',array('checked_in' => $in,'username' => $username));
            if (!$in) {
                $this->ignoreDevicesUntilGone($username);
            }
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

    public function userOfCard($cardid) {
        $doc = $this->membersCollection->findOne(array('cards.id'=>$cardid),array('username'=>1));
        return $doc['username'];
    }

    public function verifyMemberPin($username,$pin) {
        $member = $this->membersCollection->findOne(array('username'=>$username),array('pin'=>true));
        if (password_verify($pin,$member['pin'])) {
            return array('pin_correct'=>true,'member'=>$username);
        } else {
            return array('pin_correct'=>false);
        }
    }

    public function setMemberPin($username,$pin) {
        $this->membersCollection->update(array('username'=>$username),array('$set'=>array('pin'=>password_hash($pin, PASSWORD_DEFAULT))));
        return NULL; // You shouldn't be checking the outcome of this function!
    }

    public function setMemberPassword($username,$password) {
        $this->membersCollection->update(array('username'=>$username),array('$set'=>array('password'=>password_hash($password, PASSWORD_DEFAULT))));
        return NULL; // You shouldn't be checking the outcome of this function!
    }

    public function getAllMembers() {
        $cursor = $this->membersCollection->find();
        foreach ($cursor as $member) {
            $results[(string)$member['_id']] = $member;
        }
        return $results;
    }

    public function checkOutAllMembers() {
        $cursor = $this->membersCollection->find(array('checked_in'=>true));
        foreach ($cursor as $member) {
            $this->checkoutMemberByUsername($member['username']);
        }
    }

    public function loginMember($username,$password) {
        $member = $this->membersCollection->findOne(array('username'=>$username),array('password'=>true));
        if (!array_key_exists('password',$member)) {
            return false;
        }
        if (password_verify($password,$member['password'])) {
            $sessionkey = preg_replace('/[\$,\.]/','',password_hash(date('r'), PASSWORD_DEFAULT));
            $expirytime = time() + $this::sessionTimeOut;
            $this->membersCollection->update(array('username'=>$username),array('$set'=>array('sessionkey'=>$sessionkey,'sessionexpiry'=>$expirytime)));
            return array('logged_in'=>true,'session_key'=>$sessionkey,'expires'=>date('r',$expirytime));
        } else {
            return false;
        }
    }

}
