<?php
//require_once('./functions.php');
require_once('../connections/parameters.php');
require_once('../global_functions.php');

try {
    $input_file = './day3_input.txt';
    if (!is_file($input_file)) throw new Exception("Input put file missing!");
    $input = file_get_contents($input_file);
    if (empty($input)) throw new Exception("No input!");

    $input_array = $houseArray_part1 = $houseArray_part2 = array();
    $x_santa = $y_santa = $x_santaR = $y_santaR = 0;

    $input_array = str_split($input, 1);

    $houseArray_part1['0,0'] = 1;
    $houseArray_part2['0,0'] = 2;

    //Part1
    foreach ($input_array as $key => $value) {
        switch ($value) {
            case '^':
                $y_santa++;
                break;
            case 'v':
                $y_santa--;
                break;
            case '>':
                $x_santa++;
                break;
            case '<':
                $x_santa--;
                break;
        }

        $houseArray_part1[$x_santa . ',' . $y_santa] = empty($houseArray_part1[$x_santa . ',' . $y_santa]) ? 1 : $houseArray_part1[$x_santa . ',' . $y_santa] + 1;
    }

    $x_santa = $y_santa = $x_santaR = $y_santaR = 0;

    //Part2
    foreach ($input_array as $key => $value) {
        switch ($value) {
            case '^':
                $key % 2 == 0 ? $y_santa++ : $y_santaR++;
                break;
            case 'v':
                $key % 2 == 0 ? $y_santa-- : $y_santaR--;
                break;
            case '>':
                $key % 2 == 0 ? $x_santa++ : $x_santaR++;
                break;
            case '<':
                $key % 2 == 0 ? $x_santa-- : $x_santaR--;
                break;
        }

        if ($key % 2 == 0) {
            $houseArray_part2[$x_santa . ',' . $y_santa] = empty($houseArray_part2[$x_santa . ',' . $y_santa]) ? 1 : $houseArray_part2[$x_santa . ',' . $y_santa] + 1;
        } else {
            $houseArray_part2[$x_santaR . ',' . $y_santaR] = empty($houseArray_part2[$x_santaR . ',' . $y_santaR]) ? 1 : $houseArray_part2[$x_santaR . ',' . $y_santaR] + 1;
        }
    }


    $numHousesPart1 = count($houseArray_part1);
    $numHousesPart2 = count($houseArray_part2);

    $result = "<strong>Part 1:</strong> Santa visits #<strong>{$numHousesPart1}</strong> houses.";
    $result .= !empty($numHousesPart2)
        ? "<br /><strong>Part 2:</strong> Santa and Robot Santa vist #<strong>{$numHousesPart2}</strong> houses."
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