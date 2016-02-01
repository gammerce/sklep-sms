<?php

define('IN_SCRIPT', "1");
define('SCRIPT_NAME', "js");

require_once "global.php";

if ($_GET['script'] == "language") {
	$output = eval($templates->render("js/language.js", true, false));
}

output_page($output, 1);