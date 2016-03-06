<?php
//require_once('./functions.php');
require_once('../connections/parameters.php');
require_once('../global_functions.php');

try {
    $input_file = './day8_input.txt';
    if (!is_file($input_file)) throw new Exception("Input put file missing!");
    $input = file_get_contents($input_file);
    if (empty($input)) throw new Exception("No input!");

    $input_array = explode("\n", $input);

    $part1 = $part2 = 0;

    foreach($input_array as $line){
        eval('$str = ' . $line . ';');
        $part1 += strlen($line) - strlen($str);
        $part2 += strlen(addslashes($line)) + 2 - strlen($line);
    }

    $result = "<strong>Part 1:</strong> Difference of #<strong>{$part1}</strong> characters.";
    $result .= isset($part2)
        ? "<br /><strong>Part 2:</strong> Difference of #<strong>{$part2}</strong>characters."
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