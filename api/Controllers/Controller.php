<?php
/**
 * Abstract controller class for dependency injections
 */

use Respect\Validation\Validator as validator;

abstract class Controller {
    protected $app;

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

    public function isAuthorised(\Slim\Route $route, \Slim\Slim $app) {
        return $this->checkAuthorisation($route, $app);
    }

    public abstract function checkAuthorisation(\Slim\Route $route, \Slim\Slim $app);

    public abstract function init(Pimple $di);

}
