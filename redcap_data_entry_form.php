<?php

$file = dirname(APP_PATH_DOCROOT).DS.'hooks/framework/resources/datecalc.php';
if (file_exists($file)) {
	include_once $file;
} else {
	error_log ("Unable to include required file $file while in " . __FILE__);
};
?>