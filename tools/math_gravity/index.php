<?php
# math_gravity - Calculates gravitational force between two masses at a given distance.
# Input parameters: mass1 (required), mass2 (required), distance (required)
# Output: Gravitational force in Newtons.  Returns an error message if input is invalid.

defined('DISPATCHER_MODE') or die('This script must be run from within the LLM tool service.');

$mass1 = isset($_REQUEST['mass1']) ? floatval($_REQUEST['mass1']) : null;
$mass2 = isset($_REQUEST['mass2']) ? floatval($_REQUEST['mass2']) : null;
$distance = isset($_REQUEST['distance']) ? floatval($_REQUEST['distance']) : null;

if ($mass1 === null || $mass2 === null || $distance === null) {
echo "Error: All three parameters (mass1, mass2, distance) are required.\n";
exit; // Stop execution and return error message.  Important for tool service.
}

if ($distance <= 0) {
echo "Error: Distance must be a positive value.\n";
exit;
}

$G = 6.674e-11; // Gravitational constant
$force = ($G * $mass1 * $mass2) / pow($distance, 2);

echo number_format($force, 2) . "\n"; 
?>