<?php

defined('DISPATCHER_MODE') or die('Direct access is not allowed.');

if (empty($_REQUEST['numberSides'])) {
    $numberSides = 6;
} else {
  $numberSides = intval( $_REQUEST['numberSides']);
}

if ($numberSides < 1) {
    echo "0";
} else {
    echo rand(1, $numberSides);
}

?>