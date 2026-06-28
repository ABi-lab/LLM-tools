<?php
defined('DISPATCHER_MODE') || exit;

// Function to compute Nth Fibonacci number iteratively
function fibonacci($n) {
    if ($n < 0) return null; // invalid input
    if ($n === 0) return 0;
    if ($n === 1) return 1;
    
    $a = 0;
    $b = 1;
    for ($i = 2; $i <= $n; $i++) {
        $temp = $b;
        $b = $a + $b;
        $a = $temp;
    }
    return $b;
}

// Ensure PHP receives input via $_REQUEST
if (isset($_REQUEST['n']) && filter_var($_REQUEST['n'], FILTER_VALIDATE_INT, array('min' => 0))) {
    echo fibonacci((int)$_REQUEST['n']);
} else {
    // Provide a self-submitting HTML form for manual testing
    echo <<<HTML
<form method="GET" action="" style="display:inline;">
  <label>N (non-negative integer):</label>
  <input type="number" name="n" min="0" value="0"/>
  <button type="submit">Calculate</button>
</form>
HTML;
}
