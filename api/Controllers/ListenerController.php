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
        $mqttConfig = array_pop($this->config->xpath('/config/mqtt'));
        $mqttBroker = (string)$mqttConfig->broker;
        $mqttPort = (string)$mqttConfig->port;
        $mqttClientName = (string)$mqttConfig->clientName;
        $this->mqttConnection = new phpMQTT($mqttBroker,$mqttPort,$mqttClientName);
    }

    public function checkAuthorisation(\Slim\Route $route) {
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
        foreach ($this->hooks as $hookName => $hook) {
            if ($triggeredHookName == $hookName) {
                if (in_array($hookName,$this->mqttHooks)) {
                    if ($this->mqttConnection->connect()) {
                        $this->mqttConnection->publish($hookName,json_encode($hookData),0);
                        $this->mqttConnection->close();
                    }
                }
                if (is_callable($hook)) {
                    $hook($hookData);
                }
            }
        }
    }

    public function listenEvent($hookName, $callable, $mqttJson = false) {
        $this->hooks[$hookName] = $callable;
        if (!in_array($hookName,$this->mqttHooks) && $mqttJson) {
            $this->mqttHooks[$hookName] = true;
        } else if (in_array($hookName,$this->mqttHooks) &! $mqttJson) {
            unset($this->mqttHooks[$hookName]);
        }
    }

    public function getField($field)
    {
        // TODO: Implement getField() method.
    }

    public function setField($field, $setTo)
    {
        // TODO: Implement setField() method.
    }
}