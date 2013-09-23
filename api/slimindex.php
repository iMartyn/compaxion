<?php

require '../submodules/slim/Slim/Slim.php';
require_once '../submodules/pimple/lib/Pimple.php';
require_once 'Controllers/SpaceController.php';
\Slim\Slim::registerAutoloader();

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
                $returnString .= "<dl><dt>$key</dt><dd>".jsonToHTML(json_encode($value))."</dd></dl>";
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
$pimple = new Pimple();
$pimple['app'] = $app;
$pimple['SpaceController'] = $pimple->share(function ($pimple) {
    return new SpaceController($pimple);
});

$app->config('server.originalrequest',$originalRequestURI);

$app->get('/space/status', function () use ($pimple) {
    $format = $pimple['SpaceController']->getRequestedFormat();
    $data = json_encode($pimple['SpaceController']->getStatus());
    switch ($format) {
        case 'json' :
            echo $data;
            break;
        case 'html' :
            echo jsonToHTML($data, '/^\_.*/', true);
            break;
        default :
            $pimple['app']->halt(406, 'Json or html are only currently known output formats');
            break;
    }
});

$app->get('/', function() { phpinfo(); });

$app->get('/test/:what', function ($what) { var_dump($what); });

$app->run();
