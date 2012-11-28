<?php

class index {

	function __construct() {
	}

	function main() {
		if (!isset($_REQUEST['-s'])) {
			require_once('shiritori.php');
		} else {
			require_once('s.inc');
		}
	}
}
$index = new index();
$index->main();