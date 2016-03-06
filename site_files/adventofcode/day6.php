<?php
//require_once('./functions.php');
require_once('../connections/parameters.php');
require_once('../global_functions.php');

try {
    $input_file = './day6_input.txt';
    if (!is_file($input_file)) throw new Exception("Input put file missing!");
    $input = file_get_contents($input_file);
    if (empty($input)) throw new Exception("No input!");

    $input_array = array();

    $input_array = explode("\n", $input);

    $bigArray_part1 = $bigArray_part2 = array();

    //Part1
    foreach ($input_array as $key => $value) {
        $instructionLine = explode(' through ', $value);
        $endValueArray = explode(',', $instructionLine[1]);

        switch ($instructionLine[0]) {
            case substr_count($instructionLine[0], 'turn on ') > 0:
                $valueTemp = str_replace('turn on ', '', $instructionLine[0]);
                $startValueArray = explode(',', $valueTemp);

                $start_X = intval($startValueArray[0]);
                $start_Y = intval($startValueArray[1]);
                $end_X = intval($endValueArray[0]);
                $end_Y = intval($endValueArray[1]);

                //echo "Doing turn on for {$start_X} , {$end_X} || {$start_Y} , {$end_Y}";

                for ($x = $start_X; $x <= $end_X; $x++) {
                    for ($y = $start_Y; $y <= $end_Y; $y++) {
                        $bigArray_part1[$x . ',' . $y] = 1;
                    }
                }
                break;
            case substr_count($instructionLine[0], 'turn off ') > 0:
                $valueTemp = str_replace('turn off ', '', $instructionLine[0]);
                $startValueArray = explode(',', $valueTemp);

                $start_X = intval($startValueArray[0]);
                $start_Y = intval($startValueArray[1]);
                $end_X = intval($endValueArray[0]);
                $end_Y = intval($endValueArray[1]);

                //echo "Doing turn off for {$start_X} , {$end_X} || {$start_Y} , {$end_Y}";

                for ($x = $start_X; $x <= $end_X; $x++) {
                    for ($y = $start_Y; $y <= $end_Y; $y++) {
                        $bigArray_part1[$x . ',' . $y] = 0;
                    }
                }
                break;
            case substr_count($instructionLine[0], 'toggle ') > 0:
                $valueTemp = str_replace('toggle ', '', $instructionLine[0]);
                $startValueArray = explode(',', $valueTemp);

                $start_X = intval($startValueArray[0]);
                $start_Y = intval($startValueArray[1]);
                $end_X = intval($endValueArray[0]);
                $end_Y = intval($endValueArray[1]);

                //echo "Doing toggle for {$start_X} , {$end_X} || {$start_Y} , {$end_Y}";

                for ($x = $start_X; $x <= $end_X; $x++) {
                    for ($y = $start_Y; $y <= $end_Y; $y++) {
                        if (isset($bigArray_part1[$x . ',' . $y])) {
                            $bigArray_part1[$x . ',' . $y] = $bigArray_part1[$x . ',' . $y] == 0
                                ? 1
                                : 0;
                        } else {
                            $bigArray_part1[$x . ',' . $y] = 1;
                        }
                    }
                }
                break;
        }
    }

    //Part2
    foreach ($input_array as $key => $value) {
        $instructionLine = explode(' through ', $value);
        $endValueArray = explode(',', $instructionLine[1]);

        switch ($instructionLine[0]) {
            case substr_count($instructionLine[0], 'turn on ') > 0:
                $valueTemp = str_replace('turn on ', '', $instructionLine[0]);
                $startValueArray = explode(',', $valueTemp);

                $start_X = intval($startValueArray[0]);
                $start_Y = intval($startValueArray[1]);
                $end_X = intval($endValueArray[0]);
                $end_Y = intval($endValueArray[1]);

                //echo "Doing turn on for {$start_X} , {$end_X} || {$start_Y} , {$end_Y}";

                for ($x = $start_X; $x <= $end_X; $x++) {
                    for ($y = $start_Y; $y <= $end_Y; $y++) {
                        if (isset($bigArray_part2[$x . ',' . $y])) {
                            $bigArray_part2[$x . ',' . $y] = $bigArray_part2[$x . ',' . $y] + 1;
                        } else {
                            $bigArray_part2[$x . ',' . $y] = 1;
                        }
                    }
                }
                break;
            case substr_count($instructionLine[0], 'turn off ') > 0:
                $valueTemp = str_replace('turn off ', '', $instructionLine[0]);
                $startValueArray = explode(',', $valueTemp);

                $start_X = intval($startValueArray[0]);
                $start_Y = intval($startValueArray[1]);
                $end_X = intval($endValueArray[0]);
                $end_Y = intval($endValueArray[1]);

                //echo "Doing turn off for {$start_X} , {$end_X} || {$start_Y} , {$end_Y}";

                for ($x = $start_X; $x <= $end_X; $x++) {
                    for ($y = $start_Y; $y <= $end_Y; $y++) {
                        if (isset($bigArray_part2[$x . ',' . $y]) && $bigArray_part2[$x . ',' . $y] > 0) {
                            $bigArray_part2[$x . ',' . $y] = $bigArray_part2[$x . ',' . $y] - 1;
                        } else {
                            $bigArray_part2[$x . ',' . $y] = 0;
                        }
                    }
                }
                break;
            case substr_count($instructionLine[0], 'toggle ') > 0:
                $valueTemp = str_replace('toggle ', '', $instructionLine[0]);
                $startValueArray = explode(',', $valueTemp);

                $start_X = intval($startValueArray[0]);
                $start_Y = intval($startValueArray[1]);
                $end_X = intval($endValueArray[0]);
                $end_Y = intval($endValueArray[1]);

                //echo "Doing toggle for {$start_X} , {$end_X} || {$start_Y} , {$end_Y}";

                for ($x = $start_X; $x <= $end_X; $x++) {
                    for ($y = $start_Y; $y <= $end_Y; $y++) {
                        if (isset($bigArray_part2[$x . ',' . $y])) {
                            $bigArray_part2[$x . ',' . $y] = $bigArray_part2[$x . ',' . $y] + 2;
                        } else {
                            $bigArray_part2[$x . ',' . $y] = 2;
                        }
                    }
                }
                break;
        }
    }

    $numLightsLit_part1 = !empty($bigArray_part1)
        ? array_sum($bigArray_part1)
        : 0;
    $numLightsLit_part2 = array_sum($bigArray_part2);

    $result = "<strong>Part 1:</strong> Santa is expecting #<strong>{$numLightsLit_part1}</strong> lights to be lit.";
    $result .= !empty($numLightsLit_part2)
        ? "<br /><strong>Part 2:</strong> Santa is expecting #<strong>{$numLightsLit_part2}</strong> total brightness from the lights."
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