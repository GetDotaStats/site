<?php
//require_once('./functions.php');
require_once('../connections/parameters.php');
require_once('../global_functions.php');

try {
    $input_file = './day10_input.txt';
    if (!is_file($input_file)) throw new Exception("Input put file missing!");
    $input = file_get_contents($input_file);
    if (empty($input)) throw new Exception("No input!");
    $input = trim($input);

    function prettyPrint($dirtyArray)
    {
        echo '<pre>';
        print_r($dirtyArray);
        echo '</pre>';
    }


    echo '<hr />';

    $part1_answer = $part2_answer = 0;

    $result = "<strong>Part 1:</strong> Shortest distance of #<strong>{$part1_answer}</strong> cities.";
    $result .= isset($part2_answer)
        ? "<br /><strong>Part 2:</strong> Shortest distance of #<strong>{$part2_answer}</strong> cities."
        : '';

    echo $result;
} catch (Exception $e) {
    $result = 'Caught Exception: ' . $e->getMessage() . ' on line ' . $e->getLine() . '<br />';
    echo $result;
} finally {
    if (isset($memcached)) $memcached->close();
    if (!isset($result)) {
        echo 'Unknown error! #1' . '<br />';
    }
}