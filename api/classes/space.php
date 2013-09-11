<?php

Class Space {

	static function getData() {
		return array('status'=>'Open','temperature'=>'Like Hoth','members_here'=>2);
	}

	static public function statusHTML() {
		$data = self::getData();
		echo '<h1>The Space is '.$data['status'].'</h1>';
		echo '<p>Other detail :</p>';
		echo '<dl>';
		foreach ($data as $key=>$value) {
			echo "<dt>$key</dt><dd>$value</dd>";
		}
		echo '</dl>';
	}

	static public function statusJSON() {
		echo json_encode(self::getData());
	}
}
