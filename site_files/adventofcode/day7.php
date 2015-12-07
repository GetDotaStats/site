<?php
//require_once('./functions.php');
require_once('../connections/parameters.php');
require_once('../global_functions.php');

try {
    $input_file = './day7_input.txt';
    if (!is_file($input_file)) throw new Exception("Input put file missing!");
    $input = file_get_contents($input_file);
    if (empty($input)) throw new Exception("No input!");

    $input_array = array();

    $input_array = $input_arrayPart2 = explode("\n", $input);

    $wireArray = array();

    function setWireValue($wireName, $wireValue)
    {
        global $wireArray;
        if (!isset($wireArray[$wireName])) {
            $wireArray[$wireName] = $wireValue & 0xFFFF;
            return $wireArray;
        }
    }

    function getWireValue($wireValue, $caseNum)
    {
        global $wireArray;
        if (is_numeric($wireValue)) {
            //if it's a simple assignment, just do it!
            //if ($caseNum > 3) echo "<strong>Value ({$wireValue}) is numeric!</strong><br />";
            return intval($wireValue);
        } else if (isset($wireArray[$wireValue])) {
            //if the value turns out to be a reference to another wire, check if it's already set!
            //if ($caseNum > 3) echo "<strong>Value ({$wireValue}) is a reference to a wire already set!</strong><br />";
            return intval($wireArray[$wireValue]);
        }

        //echo "Value ({$wireValue}) is a reference to a wire not yet set!<br />";

        return FALSE;
    }

    //Part1
    $loops = 0;
    while (count($input_array) > 0) {
        $loops++;
        $inputArrayCount = count($input_array);
        //echo '<hr />';
        //echo "Loop #{$loops} [{$inputArrayCount}]<br />";
        if (isset($wireArray['a'])) echo $wireArray['a'] . '<br />';

        foreach ($input_array as $key => $value) {
            $linePart = explode(' ', $value);
            $solvedLine = FALSE;

            switch (count($linePart)) {

                //19138 -> b
                case 3:
                    $wireValue = trim($linePart[0]);
                    $wireName = trim($linePart[2]);

                    //echo "Attempting to set {$wireName} with value of {$wireValue}<br />";

                    $wireValue = getWireValue($wireValue, 3);

                    if ($wireValue !== FALSE && is_numeric($wireValue)) {
                        //echo "Setting wire value ({$wireValue})<br />";
                        setWireValue($wireName, $wireValue);
                        $solvedLine = TRUE;
                    }

                    //echo "<br />";
                    break;

                //NOT kt -> ku
                case 4:
                    $wire1 = trim($linePart[1]);
                    $wire2 = trim($linePart[3]);

                    //echo "Attempting to NOT {$wire2} with value of {$wire1}<br />";

                    $wire1Value = getWireValue($wire1, 4);

                    if ($wire1Value !== FALSE && is_numeric($wire1Value)) {
                        //echo "<strong>Notting wire value ({$wire1Value})</strong><br />";
                        setWireValue($wire2, ~$wire1Value);
                        $solvedLine = TRUE;
                    }

                    break;

                //bi LSHIFT 15 -> bm
                case 5;
                    $wire1 = trim($linePart[0]);
                    $wire2 = trim($linePart[2]);
                    $wire3 = trim($linePart[4]);
                    $wireOperation = trim($linePart[1]);

                    switch ($wireOperation) {
                        case 'AND':
                            $wire1Value = getWireValue($wire1, 5);
                            $wire2Value = getWireValue($wire2, 5);

                            if ($wire1Value !== FALSE && $wire2Value !== FALSE) {
                                //echo "<strong>ANDing wire [{$wire3}] with value ({$wire3Value})</strong><br />";
                                setWireValue($wire3, $wire1Value & $wire2Value);
                                $solvedLine = TRUE;
                            }

                            break;

                        case 'OR':
                            $wire1Value = getWireValue($wire1, 5);
                            $wire2Value = getWireValue($wire2, 5);

                            if ($wire1Value !== FALSE && $wire2Value !== FALSE) {
                                //echo "<strong>ORing wire [{$wire3}] with value ({$wire3Value})</strong><br />";
                                setWireValue($wire3, $wire1Value | $wire2Value);
                                $solvedLine = TRUE;
                            }

                            break;

                        case 'LSHIFT':
                            $wire1Value = getWireValue($wire1, 5);
                            $wire2Value = getWireValue($wire2, 5);

                            if ($wire1Value !== FALSE && $wire2Value !== FALSE) {
                                //echo "<strong>LSHIFTing wire [{$wire3}] with value ({$wire3Value})</strong><br />";
                                setWireValue($wire3, $wire1Value << $wire2Value);
                                $solvedLine = TRUE;
                            }

                            break;

                        case 'RSHIFT':
                            $wire1Value = getWireValue($wire1, 5);
                            $wire2Value = getWireValue($wire2, 5);

                            if ($wire1Value !== FALSE && $wire2Value !== FALSE) {
                                //echo "<strong>RSHIFTing wire [{$wire3}] with value ({$wire3Value})</strong><br />";
                                setWireValue($wire3, $wire1Value >> $wire2Value);
                                $solvedLine = TRUE;
                            }

                            break;
                    }

                    break;
            }

            if ($solvedLine) {
                unset($input_array[$key]);
            }
        }

        if ($loops >= 1000) break;
    }

    echo "It took #{$loops} loops to do Part1!<br />";

    $part1 = isset($wireArray['a'])
        ? $wireArray['a']
        : 0;

    ////////////////////////////////////////////////////////

    //Part2
    $input_array = $input_arrayPart2;
    $wireArray = array("b" => $part1);
    $loops = 0;
    while (count($input_array) > 0) {
        $loops++;
        $inputArrayCount = count($input_array);
        //echo '<hr />';
        //echo "Loop #{$loops} [{$inputArrayCount}]<br />";
        if (isset($wireArray['a'])) echo $wireArray['a'] . '<br />';

        foreach ($input_array as $key => $value) {
            $linePart = explode(' ', $value);
            $solvedLine = FALSE;

            switch (count($linePart)) {

                //19138 -> b
                case 3:
                    $wireValue = trim($linePart[0]);
                    $wireName = trim($linePart[2]);

                    //echo "Attempting to set {$wireName} with value of {$wireValue}<br />";

                    $wireValue = getWireValue($wireValue, 3);

                    if ($wireValue !== FALSE && is_numeric($wireValue)) {
                        //echo "Setting wire value ({$wireValue})<br />";
                        setWireValue($wireName, $wireValue);
                        $solvedLine = TRUE;
                    }

                    //echo "<br />";
                    break;

                //NOT kt -> ku
                case 4:
                    $wire1 = trim($linePart[1]);
                    $wire2 = trim($linePart[3]);

                    //echo "Attempting to NOT {$wire2} with value of {$wire1}<br />";

                    $wire1Value = getWireValue($wire1, 4);

                    if ($wire1Value !== FALSE && is_numeric($wire1Value)) {
                        //echo "<strong>Notting wire value ({$wire1Value})</strong><br />";
                        setWireValue($wire2, ~$wire1Value);
                        $solvedLine = TRUE;
                    }

                    break;

                //bi LSHIFT 15 -> bm
                case 5;
                    $wire1 = trim($linePart[0]);
                    $wire2 = trim($linePart[2]);
                    $wire3 = trim($linePart[4]);
                    $wireOperation = trim($linePart[1]);

                    switch ($wireOperation) {
                        case 'AND':
                            $wire1Value = getWireValue($wire1, 5);
                            $wire2Value = getWireValue($wire2, 5);

                            if ($wire1Value !== FALSE && $wire2Value !== FALSE) {
                                //echo "<strong>ANDing wire [{$wire3}] with value ({$wire3Value})</strong><br />";
                                setWireValue($wire3, $wire1Value & $wire2Value);
                                $solvedLine = TRUE;
                            }

                            break;

                        case 'OR':
                            $wire1Value = getWireValue($wire1, 5);
                            $wire2Value = getWireValue($wire2, 5);

                            if ($wire1Value !== FALSE && $wire2Value !== FALSE) {
                                //echo "<strong>ORing wire [{$wire3}] with value ({$wire3Value})</strong><br />";
                                setWireValue($wire3, $wire1Value | $wire2Value);
                                $solvedLine = TRUE;
                            }

                            break;

                        case 'LSHIFT':
                            $wire1Value = getWireValue($wire1, 5);
                            $wire2Value = getWireValue($wire2, 5);

                            if ($wire1Value !== FALSE && $wire2Value !== FALSE) {
                                //echo "<strong>LSHIFTing wire [{$wire3}] with value ({$wire3Value})</strong><br />";
                                setWireValue($wire3, $wire1Value << $wire2Value);
                                $solvedLine = TRUE;
                            }

                            break;

                        case 'RSHIFT':
                            $wire1Value = getWireValue($wire1, 5);
                            $wire2Value = getWireValue($wire2, 5);

                            if ($wire1Value !== FALSE && $wire2Value !== FALSE) {
                                //echo "<strong>RSHIFTing wire [{$wire3}] with value ({$wire3Value})</strong><br />";
                                setWireValue($wire3, $wire1Value >> $wire2Value);
                                $solvedLine = TRUE;
                            }

                            break;
                    }

                    break;
            }

            if ($solvedLine) {
                unset($input_array[$key]);
            }
        }

        if ($loops >= 1000) break;
    }

    echo "It took #{$loops} loops to do Part2!<br />";

    $part2 = isset($wireArray['a'])
        ? $wireArray['a']
        : 0;

    $result = "<strong>Part 1:</strong> Wire A has value of #<strong>{$part1}</strong>";
    $result .= isset($part2)
        ? "<br /><strong>Part 2:</strong> Wire A has a new value of #<strong>{$part2}</strong>"
        : '';

    echo $result;

    echo '<hr />';

    ksort($wireArray);

    echo '<pre>';
    print_r($wireArray);
    echo '</pre>';
} catch (Exception $e) {
    $result = 'Caught Exception: ' . $e->getMessage() . ' on line ' . $e->getLine() . '<br />';
    echo $result;
} finally {
    if (isset($memcache)) $memcache->close();
    if (!isset($result)) {
        echo 'Unknown error! #1' . '<br />';
    }
}