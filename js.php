<?php

define('IN_SCRIPT', "1");
define('SCRIPT_NAME', "js");

require_once "global.php";

if ($_GET['script'] == "language") {
	eval("\$output = \"" . get_template("js/language.js", 0, 1, 0) . "\";");
}

output_page($output, "Content-type: text/plain; charset=\"UTF-8\"");