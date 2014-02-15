<?php
/**
 * Controller for our observer (hook) model
 *
 * Does the MQTT stuff out of the box - maybe!
 */

require dirname(__FILE__).'/../../submodules/phpMQTT/phpMQTT.php';

class ListenerController extends Controller {

    private $hooks = array();
    private $mqttHooks = array();
    private $config = null;
    private $mqttConnection = null;


    public function __construct(Pimple $di) {
        /* Overriding in here to stop double-verifying the access which calls too many times at the moment */
        $this->app = $di['app'];
        $this->init($di);
    }

    public function init(Pimple $di) {
        $this->config = $this->readConfig();
        $anArray = $this->config->xpath('/config/mqtt');
        $mqttConfig = array_pop($anArray);
        $mqttBroker = (string)$mqttConfig->broker;
        $mqttPort = (string)$mqttConfig->port;
        $mqttClientName = (string)$mqttConfig->clientName;
        $this->mqttConnection = new phpMQTT($mqttBroker,$mqttPort,$mqttClientName);
    }

    public function checkAuthorisation(\Slim\Route $route, \Slim\Slim $app) {
        //This is actually not a route controller, just easier to not descend a new class
        return true;
    }

    /* Should be moved out to a separate controller perhaps */

    private function mergeXML(&$base, $add)
    {
        $new = $base->addChild($add->getName());
        foreach ($add->attributes() as $a => $b) {
            $new[$a] = $b;
        }
        foreach ($add->children() as $child) {
            $this->mergeXML($new, $child);
        }
    }

    private function readConfig() {
        $config = simplexml_load_file(dirname(__FILE__).'/../etc/defaults.xml');
        if (file_exists(dirname(__FILE__).'/../etc/local.xml')) {
            $localConfig = simplexml_load_file(dirname(__FILE__).'/../etc/local.xml');
            mergeXML($config,$localConfig);
        }
        return $config;
    }

    public function triggerEvent($triggeredHookName,$hookData) {
        $triggeredMQTT = false;
        foreach ($this->hooks as $hookdetail) {
            $hookName = $hookdetail['hookname'];
            $hook = $hookdetail['callable'];
            if ($triggeredHookName == $hookName) {
                if (is_callable($hook)) {
                    $hook($hookData);
                }
                if (in_array($hookName,$this->mqttHooks) &! $triggeredMQTT) {
                    if ($this->mqttConnection->connect()) {
                        $this->mqttConnection->publish($hookName,json_encode($hookData),0);
                        $this->mqttConnection->close();
                        $triggeredMQTT = true;
                    }
                }
            }
        }
    }

    public function listenEvent($hookName, $callable, $mqttJson = false) {
        $this->hooks[] = array('hookname'=>$hookName,'callable'=>$callable);
        if (!in_array($hookName,$this->mqttHooks) && $mqttJson) {
            $this->mqttHooks[$hookName] = true;
        } else if (in_array($hookName,$this->mqttHooks) &! $mqttJson) {
            unset($this->mqttHooks[$hookName]);
        }
    }

}