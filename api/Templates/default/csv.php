<?php
$headers = array();
foreach ($data as $document) {
    foreach (array_keys($document) as $key) {
        if (!in_array($key,$headers)) {
            $headers[] = $key;
        }
    }
}
foreach ($headers as $header) {
    echo $header.',';
}
echo "\n";
foreach ($data as $document) {
    foreach ($headers as $header) {
        if (array_key_exists($header,$document)) {
            echo $document[$header].',';
        } else {
            echo ',';
        }
    }
    echo "\n";
}