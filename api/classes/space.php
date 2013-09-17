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
            echo '<div class="debug">Setting default space status</div>';
            $document = array('status' => 'Open', 'temperature' => 'Like Hoth', 'members_here' => 2);
            $this->spaceCollection->insert($document);
        }
        return $document;
    }

    public function statusHTML() {
        $data = self::getData();
        echo '<h1>The Space is ' . $data['status'] . '</h1>';
        echo '<p>Other detail :</p>';
        echo '<dl>';
        foreach ($data as $key => $value) {
            if ($key[0] !== '_') {
                echo "<dt>$key</dt><dd>$value</dd>";
            }
        }
        echo '</dl>';
    }

    public function statusJSON() {
        echo json_encode(self::getData());
    }
}
