<?php
defined('DISPATCHER_MODE') or die('Access denied');

if (!isset($_REQUEST['a']) && !isset($_REQUEST['b'])) {
    echo <<<HTML
<form action="index.php" method="get">
    a: <input name="a" type="number" step="any"><br>
    b: <input name="b" type="number" step="any"><br>
    <input type="submit" value="Add">
</form>
HTML;
    exit(0);
}

$a = isset($_REQUEST['a']) ? (float) $_REQUEST['a'] : 0.0;
$b = isset($_REQUEST['b']) ? (float) $_REQUEST['b'] : 0.0;

$sum = $a + $b;
echo "$sum";
