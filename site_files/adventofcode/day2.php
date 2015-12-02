<?php
//require_once('./functions.php');
require_once('../connections/parameters.php');
require_once('../global_functions.php');

try {
    $input_file = './day2_input.txt';
    if (!is_file($input_file)) throw new Exception("Input put file missing!");
    $input = file_get_contents($input_file);
    if (empty($input)) throw new Exception("No input!");

    $input_array = $value_pair = array();
    $wrappingPaper = $ribbonRibbon = 0;

    $input_array = explode("\n", $input);

    foreach ($input_array as $key => $value) {
        $value_pair = explode("x", $value);
        sort($value_pair, SORT_NUMERIC);

        $face1 = (2 * $value_pair[0] * $value_pair[1]);
        $face2 = (2 * $value_pair[1] * $value_pair[2]);
        $face3 = (2 * $value_pair[2] * $value_pair[0]);
        $spare = ($value_pair[0] * $value_pair[1]);

        $wrappingPaper += $face1 + $face2 + $face3 + $spare;

        $ribbonPerimeter = (2 * $value_pair[0]) + (2 * $value_pair[1]);
        $ribbonBow = $value_pair[0] * $value_pair[1] * $value_pair[2];
        $ribbonRibbon += $ribbonPerimeter + $ribbonBow;
    }

    $result = "<strong>Part 1:</strong> Square feet of wrapping paper required #<strong>{$wrappingPaper}</strong> feet.";
    $result .= !empty($ribbonRibbon)
        ? "<br /><strong>Part 2:</strong> Ribbon required #<strong>{$ribbonRibbon}</strong> feet."
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