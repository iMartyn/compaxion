<?php
/**
 * Abstract controller class for dependency injections
 */

use Respect\Validation\Validator as validator;

abstract class Controller {

    const sessionTimeOut = 300; //5*60; (5 mins)
    protected $app;
    protected $apiUsers;
    protected $mongoDatabase;

    public function init(Pimple $di) {
        $this->mongoDbConnection = new MongoClient;
        $this->mongoDatabase = $this->mongoDbConnection->compaxion;
        $this->membersCollection = $this->mongoDatabase->members;
        $this->spaceCollection = $this->mongoDatabase->members;
        $this->apiUsers = $this->mongoDatabase->apiUsers;
        $this->accessGroupsCollection = $this->mongoDatabase->accessGroups;
        $this->accessRoutesCollection = $this->mongoDatabase->accessRoutes;
        $this->registerListeners($di);
        $this->loadOtherRequiredControllers($di);
    }

    public function registerListeners(Pimple $di) {
        // Specifically not making this abstract because controllers don't NEED to define any listeners.
    }

    public function loadOtherRequiredControllers(Pimple $di) {
        // Specifically not making this abstract because controllers don't NEED to require any other controllers.
    }

    public function __construct(Pimple $di) {
        $this->app = $di['app'];
        $this->init($di);
        $route = $this->app->router->getCurrentRoute();
        if (!$this->isAuthorised($route,$this->app)) {
            $this->app->halt(403, 'You do not have authorisation to access this.');
        }
        if (!$this->validateRequest()) {
            $this->app->halt(400, 'You have submitted an invalid request or invalid data');
        }
    }

    public function validateRequest() {
        /*
         * We need to validate various types of input - json and url-encoded is fine, but we need to be
         * able to extend this and have some kind of field verification
         */
        return true;
    }

    public function getRequestedFormat() {
        $originalServerRequestPath = $this->app->config('server.originalrequest');
        $requestFormatParam = $this->app->request->params('format');
        $firstRequestedFormat = $this->whatFormatIsWanted(array('text/html', 'application/json', 'application/javascript', 'text/comma-separated-values', 'text/csv', 'application/csv'));
        if (
            // .json ending
            preg_match('/.json$/', $originalServerRequestPath) ||
            // ?format=json
            $requestFormatParam == 'json' ||
            // Accept: header
            $firstRequestedFormat == 'application/json' || $firstRequestedFormat == 'application/javascript'
        ) {
            return 'json';
        }
        if (
            // .json ending
            preg_match('/.csv$/', $originalServerRequestPath) ||
            // ?format=json
            $requestFormatParam == 'csv' ||
            // Accept: header - text/comma-separated-values, text/csv, application/csv
            $firstRequestedFormat == 'text/comma-separated-values' || $firstRequestedFormat == 'text/csv' || $firstRequestedFormat == 'application/csv'
        ) {
            return 'csv';
        }
        return 'html';
    }

    public function parseAcceptHeader() {
        if (!array_key_exists('HTTP_ACCEPT',$_SERVER)) {
            $hdr = '*/*';
        } else {
            $hdr = $_SERVER['HTTP_ACCEPT'];
        }
        $accept = array();
        foreach (preg_split('/\s*,\s*/', $hdr) as $i => $term) {
            $o = new \stdclass;
            $o->pos = $i;
            if (preg_match(",^(\S+)\s*;\s*(?:q|level)=([0-9\.]+),i", $term, $M)) {
                $o->type = $M[1];
                $o->q = (double)$M[2];
            } else {
                $o->type = $term;
                $o->q = 1;
            }
            $accept[] = $o;
        }
        usort($accept, function ($a, $b) {
            /* first tier: highest q factor wins */
            $diff = $b->q - $a->q;
            if ($diff > 0) {
                $diff = 1;
            } else if ($diff < 0) {
                $diff = -1;
            } else {
                /* tie-breaker: first listed item wins */
                $diff = $a->pos - $b->pos;
            }
            return $diff;
        });
        $accept_data = array();
        foreach ($accept as $a) {
            $accept_data[$a->type] = $a->type;
        }
        return $accept_data;
    }

    public function whatFormatIsWanted(Array $whatWeCanProvide) {
        $whatTheClientWants = $this->parseAcceptHeader();
        foreach ($whatTheClientWants as $aRequestedFormat) {
            if (in_array($aRequestedFormat, $whatWeCanProvide)) {
                return $aRequestedFormat;
            }
        }
        return null;
    }

    private function isLoggedIn() {
        $headers = $this->app->request()->headers;
        // The next two lines are because headers is some weird non-array-thing.
        $copyheaders = array();
        foreach ($headers as $key=>$value) $copyheaders[$key] = $value;
        // The request has a session id assigned to a user
        if (array_key_exists('X-Sessionid',$copyheaders) && array_key_exists('X-Username',$copyheaders)) {
            $document = $this->membersCollection->findOne(array('username'=>$headers['X-Username']),array('sessionkey'=>true,'sessionexpiry'=>true,'admin'=>true));
            if (array_key_exists('sessionkey',$document) &&
                array_key_exists('sessionexpiry',$document) &&
                $document['sessionexpiry'] > time() &&
                $document['sessionkey'] == $copyheaders['X-Sessionid']) {
                // Session key is valid, expiry is in the future, so we continue the session (resetting the session timeout)
                $expirytime = time() + $this::sessionTimeOut;
                $this->membersCollection->update(array('username'=>$copyheaders['X-Username']),array('$set'=>array('sessionexpiry'=>$expirytime)));
                return true;
            }
        }
        return false;
    }

    private function isValidApiUser() {
        $headers = $this->app->request()->headers;
        // The next two lines are because headers is some weird non-array-thing.
        $copyheaders = array();
        foreach ($headers as $key=>$value) $copyheaders[$key] = $value;
        try {
            if (array_key_exists('X-Api-Key',$copyheaders) && array_key_exists('description',$this->apiUsers->findOne(array('apiKey'=>$copyheaders['X-Api-Key']),array('description'=>1)))) {
                return true;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    private function canUseThisRoute() {
        //TODO: check the route's validity for that api or user.
        // for now just say yes
        return true;
    }

    public function isAuthorised(\Slim\Route $route, \Slim\Slim $app) {

        $publicRegExRoutes = Array('#/space/status#','#/member/[^/]/login$#');
        foreach ($publicRegExRoutes as $aPublicRegExRoute) {
            if (preg_match($aPublicRegExRoute,$app->request()->getPath())) {
                return true; // public route
            }
        }
        //TODO: Check private routes against the DB $this->isLoggedIn() ||
        if ($this->isValidApiUser()) {
            return $this->canUseThisRoute();
        } else {
            return false;
        }
    }

}
