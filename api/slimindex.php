<?php

require '../submodules/slim/Slim/Slim.php';
\Slim\Slim::registerAutoloader();

function getRequestedFormat($app) {
    $contentType = $app->request->headers->get('Content-Type');
    $originalServerRequestPath = $app->config('server.originalrequest');
    $requestFormatParam = $app->request->params('format');
    $firstRequestedFormat = whatFormatIsWanted(array('text/html', 'application/json', 'application/javascript'));
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
    return 'html';
}

function jsonToHTML($json,$excludeKeys = null,$excludeRegex = false) {
    if (!is_null($excludeKeys)) {
        if (!is_array($excludeKeys)) {
            $excludeKeys = array($excludeKeys);
        }
    } else {
        $excludeKeys = array();
    }
    $returnString = '<dl>';
    foreach (json_decode($json,true) as $key => $value) {
        $displayKey = true;
        if ($excludeRegex) {
            foreach ($excludeKeys as $pattern) {
                if (preg_match($pattern,$key)) {
                    $displayKey = false;
                }
            }
        } else {
            if (in_array($key,$excludeKeys)) {
                $displayKey = false;
            }
        }
        if ($displayKey) {
            if (is_array($value)) {
                $returnString .= $this->jsonToHTML(json_encode($value));
            } else {
                $returnString .= "<dt>$key</dt><dd>$value</dd>";
            }
        }
    }
    $returnString .= '</dl>';
    return $returnString;
}

function parseAcceptHeader() {
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

function whatFormatIsWanted(Array $whatWeCanProvide) {
    $whatTheClientWants = parseAcceptHeader();
    foreach ($whatTheClientWants as $aRequestedFormat) {
        if (in_array($aRequestedFormat, $whatWeCanProvide)) {
            return $aRequestedFormat;
        }
    }
    return null;
}

// Because we want to strip .json before the router has to deal with it
$originalRequestURI = $_SERVER['REQUEST_URI'];
if (preg_match('/\.json$/',$_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = preg_replace('/.json$/','',$_SERVER['REQUEST_URI']);
}
$app = new \Slim\Slim();
$app->config('server.originalrequest',$originalRequestURI);

$app->get('/hello/:name', function ($name) {
    global $app;
    $returnJson = json_encode(array('Hello' => $name));
    switch (getRequestedFormat($app)) {
        case 'json' :
            echo $returnJson;
            break;
        case 'html' :
            echo jsonToHTML($returnJson);
            break;
        default :
            $app->halt(406, 'Json or html are only currently known output formats');
            break;
    }
});

$app->get('/', phpinfo);

$app->get('/test/:what', function ($what) { var_dump($what); });

$app->run();
