<?php
//require_once('./functions.php');
require_once('../connections/parameters.php');
require_once('../global_functions.php');

try {
    $input_file = './day5_input.txt';
    if (!is_file($input_file)) throw new Exception("Input put file missing!");
    $input = file_get_contents($input_file);
    if (empty($input)) throw new Exception("No input!");

    $input_array = array();
    $vowelArray_Part1 = array('a', 'e', 'i', 'o', 'u');
    $badStrings_Part1 = array('ab', 'cd', 'pq', 'xy');
    $niceStrings_Part1 = $niceStrings_Part2 = 0;

    $input_array = explode("\n", $input);

    //Part1
    foreach ($input_array as $key => $value) {
        //Check1
        $vowelCount = 0;
        foreach ($vowelArray_Part1 as $key2 => $value2) {
            $vowelCount += substr_count($value, $value2);
        }
        $passedCheck1 = !empty($vowelCount) && $vowelCount >= 3
            ? true
            : false;

        //Check2
        $passedCheck2 = false;
        $value_char_split = str_split($value, 1);
        foreach ($value_char_split as $key2 => $value2) {
            if (isset($value_char_split[$key2 + 1])) {
                if ($value2 == $value_char_split[$key2 + 1]) {
                    $passedCheck2 = true;
                }
            }
        }

        //Check3
        $passedCheck3 = true;
        foreach ($badStrings_Part1 as $key2 => $value2) {
            if (!empty(substr_count($value, $value2))) {
                $passedCheck3 = false;
            }
        }

        if ($passedCheck1 && $passedCheck2 && $passedCheck3) $niceStrings_Part1++;
    }


    //Part2
    foreach ($input_array as $key => $value) {
        //Check 1
        $passedCheck1 = false;
        $stringPartsArray = array();
        $stringLength = strlen($value);
        //Split word into groups of 2
        for ($i = 0; $i < ($stringLength - 2); $i++) {
            $stringPartsArray[] = substr($value, $i, 2);
        }

        $stringPartsCount = count($stringPartsArray);
        foreach ($stringPartsArray as $key2 => $value2) {
            //For each pair, compare to all other pairs except itself and the one before and after
            for ($i = 0; $i < $stringPartsCount; $i++) {
                if ($value2 == $stringPartsArray[$i] && $i != $key2 && $i != ($key2 + 1) && $i != ($key2 - 1)) {
                    $passedCheck1 = true;
                }
            }
        }

        //Check 2
        $passedCheck2 = false;
        $stringPartsArray = str_split($value, 1);

        foreach ($stringPartsArray as $key2 => $value2) {
            //For each letter, compare to letter 2 spaces before
            if(isset($stringPartsArray[$key2 - 2])){
                if ($value2 == $stringPartsArray[$key2 - 2]) {
                    $passedCheck2 = true;
                }
            }

            //For each letter, compare to letter 2 spaces after
            if(isset($stringPartsArray[$key2 + 2])){
                if ($value2 == $stringPartsArray[$key2 + 2]) {
                    $passedCheck2 = true;
                }
            }
        }


        if ($passedCheck1 && $passedCheck2) $niceStrings_Part2++;
    }

    $result = "<strong>Part 1:</strong> Santa has #<strong>{$niceStrings_Part1}</strong> nice strings.";
    $result .= !empty($niceStrings_Part2)
        ? "<br /><strong>Part 2:</strong> Santa has #<strong>{$niceStrings_Part2}</strong> nice strings."
        : '';

    echo $result;
} catch (Exception $e) {
    $result = 'Caught Exception: ' . $e->getMessage() . '<br />';
    echo $result;
} finally {
    if (isset($memcached)) $memcached->close();
    if (!isset($result)) {
        echo 'Unknown error! #1' . '<br />';
    }
}