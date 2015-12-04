<?php
//require_once('./functions.php');
require_once('../connections/parameters.php');
require_once('../global_functions.php');

try {
    $input_file = './day4_input.txt';
    if (!is_file($input_file)) throw new Exception("Input put file missing!");
    $input = file_get_contents($input_file);
    if (empty($input)) throw new Exception("No input!");

    $hash = $part1_hash = $part2_hash = '';
    $max_rounds = 10000000000;
    $part1_solved = $part2_solved = false;
    $part1_integer = $part2_integer = 0;

    for ($i = 0; $i < $max_rounds; $i++) {
        $hash = md5($input . $i);
        $firstPart = substr($hash, 0, 5);

        if (substr($hash, 0, 5) === '00000' && !$part1_solved) {
            $part1_integer = $i;
            $part1_hash = $hash;
            $part1_solved = true;
        }

        if (substr($hash, 0, 6) === '000000' && !$part2_solved) {
            $part2_integer = $i;
            $part2_hash = $hash;
            $part2_solved = true;
        }

        if ($part1_solved && $part2_solved) break;
    }


    $result = "<strong>Part 1:</strong> Hash of {$part1_hash} created from Integer #<strong>{$part1_integer}</strong>";
    $result .= !empty($part2_hash)
        ? "<br /><strong>Part 2:</strong> Hash of {$part2_hash} created from Integer #<strong>{$part2_integer}</strong>"
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