<?php

require '../submodules/slim/Slim/Slim.php';
require_once '../submodules/pimple/lib/Pimple.php';
require_once 'Controllers/SpaceController.php';
require_once 'Controllers/MembersController.php';
require_once 'Controllers/ListenerController.php';
require_once 'Controllers/DevicesController.php';
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

function outputJsonOrHTML(Controller $controller,Pimple $pimple,$data) {
    $format = $controller->getRequestedFormat();
    if (!is_array($data)) {
        $data = array("data"=>$data);
    }
    $json = json_encode($data);
    $html = jsonToHTML($json,'/^_.*/',true);
    $classTemplate = str_replace('Controller','',get_class($controller));
    $templateFolder = dirname(__FILE__).'/Templates';
    if (!is_dir($templateFolder.'/'.$classTemplate) || !file_exists($templateFolder.'/'.$classTemplate.'/'.$format.'.php')) {
        $classTemplate = 'default';
    }
    if (!file_exists($templateFolder.'/'.$classTemplate.'/'.$format.'.php')) {
        $pimple['app']->halt(406, 'Unknown request format');
    }
    $pimple['app']->render($classTemplate.'/'.$format.'.php',array('json'=>$json,'data'=>$data,'html'=>$html));
}

// Because we want to strip .json before the router has to deal with it
$originalRequestURI = $_SERVER['REQUEST_URI'];
if (preg_match('/\.json$/',$_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = preg_replace('/.json$/','',$_SERVER['REQUEST_URI']);
}
// Same for csv, 'cos why not!
if (preg_match('/\.csv$/',$_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = preg_replace('/.csv$/','',$_SERVER['REQUEST_URI']);
}
$app = new \Slim\Slim();
$pimple = new Pimple();
$pimple['app'] = $app;
$pimple['ListenerController'] = $pimple->share(function ($pimple) {
    return new ListenerController($pimple);
});
$pimple['SpaceController'] = $pimple->share(function ($pimple) {
    return new SpaceController($pimple);
});
$pimple['MembersController'] = $pimple->share(function ($pimple) {
    return new MembersController($pimple);
});
$pimple['DevicesController'] = $pimple->share(function ($pimple) {
    return new DevicesController($pimple);
});

$app->config('server.originalrequest',$originalRequestURI);
$app->config('templates.path',dirname(__FILE__).DIRECTORY_SEPARATOR.'Templates');

$app->get('/space/status', function () use ($pimple) {
    outputJsonOrHTML($pimple['SpaceController'],$pimple,$pimple['SpaceController']->getStatus());
});

$app->get('/space/status/:setto', function ($setTo) use ($pimple) {
    outputJsonOrHTML($pimple['SpaceController'],$pimple,$pimple['SpaceController']->setStatus($setTo));
});

$app->get('/member/count', function () use ($pimple) {
    outputJsonOrHTML($pimple['MembersController'],$pimple,$pimple['MembersController']->getMemberCount());
});

$app->get('/member/:username', function ($username) use ($pimple) {
    outputJsonOrHTML($pimple['MembersController'],$pimple,$pimple['MembersController']->getMemberByUsername($username));
});

$app->get('/member/:username/checkin', function ($username) use ($pimple) {
    outputJsonOrHTML($pimple['MembersController'],$pimple,$pimple['MembersController']->checkinMemberByUsername($username));
});

$app->get('/member/:username/checkout', function ($username) use ($pimple) {
    outputJsonOrHTML($pimple['MembersController'],$pimple,$pimple['MembersController']->checkoutMemberByUsername($username));
});

$app->get('/member/:username/verifypin/:pin', function ($username,$pin) use ($pimple) {
    outputJsonOrHTML($pimple['MembersController'],$pimple,$pimple['MembersController']->verifyMemberPin($username,$pin));
});

$app->get('/member/:username/setpin/:pin', function ($username,$pin) use ($pimple) {
    outputJsonOrHTML($pimple['MembersController'],$pimple,$pimple['MembersController']->setMemberPin($username,$pin));
});

$app->post('/member/:username/setpassword', function ($username) use ($pimple,$app) {
    outputJsonOrHTML($pimple['MembersController'],$pimple,$pimple['MembersController']->setMemberPassword($username,$app->request->post('password')));
});

$app->post('/member/:username/login', function ($username) use ($pimple,$app) {
    outputJsonOrHTML($pimple['MembersController'],$pimple,$pimple['MembersController']->loginMember($username,$app->request->post('password')));
});

$app->get('/member', function () use ($pimple) {
    outputJsonOrHTML($pimple['MembersController'],$pimple,$pimple['MembersController']->getAllMembers());
});

$app->get('/device', function () use ($pimple) {
    outputJsonOrHTML($pimple['DevicesController'],$pimple,$pimple['DevicesController']->getDeviceList());
});

$app->get('/device/count', function () use ($pimple) {
    outputJsonOrHTML($pimple['DevicesController'],$pimple,$pimple['DevicesController']->getDeviceCount());
});

$app->get('/device/:mac/appear', function ($mac) use ($pimple) {
    outputJsonOrHTML($pimple['DevicesController'],$pimple,$pimple['DevicesController']->deviceAppears($mac));
});

$app->get('/device/:mac/disappear', function ($mac) use ($pimple) {
    outputJsonOrHTML($pimple['DevicesController'],$pimple,$pimple['DevicesController']->deviceDisappears($mac));
});

$app->get('/devices/:username', function ($username) use ($pimple) {
    outputJsonOrHTML($pimple['DevicesController'],$pimple,$pimple['DevicesController']->getDeviceList($username));
});

$app->get('/devices/:username/hide', function ($username) use ($pimple) {
    outputJsonOrHTML($pimple['DevicesController'],$pimple,$pimple['DevicesController']->hideUsersDevices($username));
});

$app->get('/verifypin/:cardid/:pin', function ($cardid,$pin) use ($pimple) {
    $username = $pimple['MembersController']->userOfCard($cardid);
    outputJsonOrHTML($pimple['MembersController'],$pimple,$pimple['MembersController']->verifyMemberPin($username,$pin));
});

$app->get('/', function() use ($pimple) { var_dump($pimple['app']->router->getCurrentRoute()); phpinfo(); });

$app->get('/test/:what', function ($what) { var_dump($what); });

$app->get('/test/:what/thehell', function ($what) { var_dump($what); echo "<br/>THE HELL!"; });

$app->run();

