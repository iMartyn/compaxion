<?php

require '../submodules/slim/Slim/Slim.php';
require_once '../submodules/pimple/lib/Pimple.php';
require_once 'Controllers/SpaceController.php';
require_once 'Controllers/MembersController.php';
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
$pimple['MembersController'] = $pimple->share(function ($pimple) {
    return new MembersController($pimple);
});

function outputJsonOrHTML(Controller $controller,Pimple $pimple,$data) {
    $format = $controller->getRequestedFormat();
    if (!is_array($data)) {
        $data = array("data"=>$data);
    }
    $data = json_encode($data);
    $html = jsonToHTML($data,'/^_.*/',true);
    if (!file_exists('Templates'.DIRECTORY_SEPARATOR.$format.'.php')) {
        $pimple['app']->halt(406, 'Unknown request format');
    }
    if ($format == 'json') {
        echo json_decode($data);
    }
    $pimple['app']->render($format.'.php',array('data'=>$data,'html'=>$html));
}

$app->config('server.originalrequest',$originalRequestURI);
$app->config('templates.path',dirname(__FILE__).DIRECTORY_SEPARATOR.'Templates');

$app->get('/space/status', function () use ($pimple) {
    outputJsonOrHTML($pimple['SpaceController'],$pimple,$pimple['SpaceController']->getStatus());
});

$app->get('/member/count', function () use ($pimple) {
    outputJsonOrHTML($pimple['MembersController'],$pimple,$pimple['MembersController']->getMemberCount());
});
$app->get('/', function() use ($pimple) { var_dump($pimple['app']->router->getCurrentRoute()); phpinfo(); });

$app->get('/test/:what', function ($what) { var_dump($what); });

$app->run();
