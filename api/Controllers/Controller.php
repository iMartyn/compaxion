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
        if (!$this->isAuthorised($this->app->router->getCurrentRoute())) {
            $this->app->halt(403, 'You do not have authorisation to access this.');
        }
        if (!$this->validateRequest()) {
            $this->app->halt(400, 'You have submitted an invalid request or invalid data');
        }
    }

    public function getDataAsAssoc() {
        //TODO: Add some way of checking if the data is post data as json or form-urlencoded
        return $this->app->request()->getBody();
    }

    /**
     * Validates a particular field.
     * @param $field the field being checked
     * @param $data the data being validated
     * @return bool
     */
    public function validateField($field,$data) {
        return true;
    }

    /**
     * Validates an entire request via the validateField function
     * TODO: write me!
     * @return bool
     */
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
        $hdr = $_SERVER['HTTP_ACCEPT'];
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

    public function isAuthorised(\Slim\Route $route) {
        return $this->checkAuthorisation($route);
    }

    public abstract function checkAuthorisation(\Slim\Route $route);

    public abstract function init(Pimple $di);

    public abstract function getField($field);

    public abstract function setField($field,$setTo);
}