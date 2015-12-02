<?php
//require_once('./functions.php');
require_once('../connections/parameters.php');
require_once('../global_functions.php');

try {
    $input_file = './day1_input.txt';
    if (!is_file($input_file)) throw new Exception("Input put file missing!");
    $input = file_get_contents($input_file);
    if (empty($input)) throw new Exception("No input!");

    $input_array = array();
    $input_array = str_split($input, 1);

    $currentFloor = $location = 0;
    $part2done = false;

    foreach ($input_array as $key => $value) {
        if ($value == '(') {
            $currentFloor++;
        } else if ($value == ')') {
            $currentFloor--;
        }

        if ($currentFloor == -1 && !$part2done) {
            $location = $key + 1;
            $part2done = true;
        }
    }

    $result = "<strong>Part 1:</strong> The ending floor is #<strong>{$currentFloor}</strong>";
    $result .= !empty($location)
        ? "<br /><strong>Part 2:</strong> The character is at position #<strong>{$location}</strong>"
        : '';

    echo $result;
} catch (Exception $e) {
    $result = 'Caught Exception: ' . $e->getMessage() . '<br />';
    echo $result;
} finally {
    if (isset($memcache)) $memcache->close();
    if (!isset($result)) {
        echo 'Unknown error! #1' . '<br />';
    }
}