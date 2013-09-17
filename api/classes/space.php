<?php

if (!class_exists('MongoClient')) {
    throw new Exception('Mongo class does not exist!');
}

Class Space {

    private $mongoDbConnection = null;
    private $mongoDatabase = null;
    private $spaceCollection = null;

    function getData() {
        if (is_null($this->mongoDatabase)) {
            $this->mongoDbConnection = new MongoClient;
            $this->mongoDatabase = $this->mongoDbConnection->compaxion;
            $this->spaceCollection = $this->mongoDatabase->space;
        }
        $document = $this->spaceCollection->findOne();
        if (is_null($document)) {
            $document = array('status' => 'Open', 'temperature' => 'Like Hoth', 'members_here' => 2);
            $this->spaceCollection->insert($document);
        }
        return $document;
    }

    private function jsonToHTML($json,$excludeKeys = null,$excludeRegex = false) {
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

    public function statusHTML() {
        $data = self::getData();
        echo '<h1>The Space is ' . $data['status'] . '</h1>';
        echo '<p>Other detail :</p>';
        echo $this->jsonToHTML(json_encode($data),array('/^_.*/','/^status$/'),true);
    }

    public function setStatus($status) {
        $document = self::getData();
        unset($document['_id']);
        $document['status'] = $status;
        $this->spaceCollection->findAndModify(null,$document);

    }

    public function statusJSON() {
        echo json_encode(self::getData());
    }
}
