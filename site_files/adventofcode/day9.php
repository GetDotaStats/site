<?php
//require_once('./functions.php');
require_once('../connections/parameters.php');
require_once('../global_functions.php');

try {
    $input_file = './day9_input.txt';
    if (!is_file($input_file)) throw new Exception("Input put file missing!");
    $input = file_get_contents($input_file);
    if (empty($input)) throw new Exception("No input!");

    function prettyPrint($dirtyArray)
    {
        echo '<pre>';
        print_r($dirtyArray);
        echo '</pre>';
    }

    function getInput($inputFileContents)
    {
        $finalArray = array();
        $input_array = explode("\n", $inputFileContents);

        foreach ($input_array as $key => $value) {
            $arrayTemp = explode(' to ', $value);
            $locationStart = trim($arrayTemp[0]);

            $arrayTemp2 = explode(' = ', $arrayTemp[1]);
            $locationEnd = trim($arrayTemp2[0]);
            $locationDistance = trim($arrayTemp2[1]);

            $finalArray['paths'][$locationStart][$locationEnd] = $locationDistance;
            $finalArray['paths'][$locationEnd][$locationStart] = $locationDistance;
            $finalArray['destinations'][$locationStart] = 0;
            $finalArray['destinations'][$locationEnd] = 0;
        }

        return $finalArray;
    }

    $inputArray = getInput($input);
    prettyPrint($inputArray);

    echo '<hr />';

    $arrayDistances = array();

    foreach ($inputArray['destinations'] as $key => $value) {
        $visited = array();

        //Check if there are further paths available
        if (isset($inputArray['paths'][$key]) && !in_array($key, $visited)) {

            //Loop through all of the destinations available from Node1
            foreach ($inputArray['paths'][$key] as $key2 => $value2) {
                $visited = array($key);
                if (isset($inputArray['paths'][$key2]) && !in_array($key2, $visited)) {

                    //Loop through all of the destinations available from Node2
                    foreach ($inputArray['paths'][$key2] as $key3 => $value3) {
                        $visited = array($key, $key2);
                        if (isset($inputArray['paths'][$key3]) && !in_array($key3, $visited)) {

                            //Loop through all of the destinations available from Node3
                            foreach ($inputArray['paths'][$key3] as $key4 => $value4) {
                                $visited = array($key, $key2, $key3);
                                if (isset($inputArray['paths'][$key4]) && !in_array($key4, $visited)) {

                                    //Loop through all of the destinations available from Node4
                                    foreach ($inputArray['paths'][$key4] as $key5 => $value5) {
                                        $visited = array($key, $key2, $key3, $key4);
                                        if (isset($inputArray['paths'][$key5]) && !in_array($key5, $visited)) {

                                            //Loop through all of the destinations available from Node5
                                            foreach ($inputArray['paths'][$key5] as $key6 => $value6) {
                                                $visited = array($key, $key2, $key3, $key4, $key5);
                                                if (isset($inputArray['paths'][$key6]) && !in_array($key6, $visited)) {

                                                    //Loop through all of the destinations available from Node6
                                                    foreach ($inputArray['paths'][$key6] as $key7 => $value7) {
                                                        $visited = array($key, $key2, $key3, $key4, $key5, $key6);
                                                        if (isset($inputArray['paths'][$key7]) && !in_array($key7, $visited)) {

                                                            //Loop through all of the destinations available from Node7
                                                            foreach ($inputArray['paths'][$key7] as $key8 => $value8) {
                                                                $visited = array($key, $key2, $key3, $key4, $key5, $key6, $key7);
                                                                if (isset($inputArray['paths'][$key8]) && !in_array($key8, $visited)) {


                                                                    $distanceTaken = $value2 + $value3 + $value4 + $value5 + $value6 + $value7 + $value8;
                                                                    $arrayKeyTemp = array($key, $key2, $key3, $key4, $key5, $key6, $key7, $key8);
                                                                    $arrayKey = implode('_', $arrayKeyTemp);

                                                                    $arrayDistances[$arrayKey] = $distanceTaken;
                                                                    unset($arrayKeyTemp, $arrayKey, $distanceTaken);
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    asort($arrayDistances);
    //prettyPrint($arrayDistances);

    $part1_answerA = 0;
    $part1_answerB = '';

    foreach ($arrayDistances as $key => $value) {
        $part1_answerA = $value;
        $part1_answerB = $key;

        break;
    }

    arsort($arrayDistances);
    //prettyPrint($arrayDistances);

    $part2_answerA = 0;
    $part2_answerB = '';

    foreach ($arrayDistances as $key => $value) {
        $part2_answerA = $value;
        $part2_answerB = $key;

        break;
    }


    echo '<hr />';

    $result = "<strong>Part 1:</strong> Shortest distance of #<strong>{$part1_answerA}</strong> beteween <strong>{$part1_answerB}</strong> cities.";
    $result .= isset($part2_answerA)
        ? "<br /><strong>Part 2:</strong> Shortest distance of #<strong>{$part2_answerA}</strong> beteween <strong>{$part2_answerB}</strong> cities."
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