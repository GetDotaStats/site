<?php
//require_once('./functions.php');
require_once('../connections/parameters.php');
require_once('../global_functions.php');

try {
    $input_file = './day15_input.txt';
    if (!is_file($input_file)) throw new Exception("Input put file missing!");
    $input = file_get_contents($input_file);
    if (empty($input)) throw new Exception("No input!");

    function prettyPrint($dirtyArray)
    {
        echo '<pre>';
        print_r($dirtyArray);
        echo '</pre>';
    }

    function getIngredients($inputFileContents)
    {
        $ingredientsArray = array();
        $input_array = explode("\n", $inputFileContents);

        foreach ($input_array as $key => $value) {
            $ingredientTemp = explode(': ', $value);

            $ingredientName = $ingredientTemp[0];
            unset($ingredientTemp[0]);

            foreach ($ingredientTemp as $key2 => $value2) {
                $ingredientParameters = explode(', ', $value2);

                foreach ($ingredientParameters as $key3 => $value3) {
                    $ingredientParameterTemp = explode(' ', $value3);

                    $ingredientsArray[$ingredientName][$ingredientParameterTemp[0]] = $ingredientParameterTemp[1];
                }
            }
        }

        return $ingredientsArray;
    }

    /*
        [Frosting] => Array
            (
                [capacity] => 4
                [durability] => -2
                [flavor] => 0
                [texture] => 0
                [calories] => 5
            )
    */

    function mixIngredients($ingredientsArray)
    {
        $listParameters = array('capacity', 'durability', 'flavor', 'texture', 'calories');

        $returnArray = $keyNameArray = array();

        //For all of the ingredients given, do stuff
        foreach ($ingredientsArray as $key => $value) {
            //Do the servings shenanigans
            if (!isset($value['servings'])) $ingredientsArray[$key]['servings'] = 0;
            $returnArray['servings'][$key] = $ingredientsArray[$key]['servings'];
            //Make array of all of the ingredients with servings
            if (!empty($ingredientsArray[$key]['servings'])) $keyNameArray[] = "{$key}-{$ingredientsArray[$key]['servings']}";

            //For each of the parameters of the ingredients, do stuff
            foreach ($listParameters as $key2 => $value2) {
                if (!isset($value[$value2])) throw new Exception("Missing a required parameter [{$value2}] in the ingredients!");

                //Make the parameter value reflect the serving size
                $ingredientsArray[$key][$value2] = $ingredientsArray[$key][$value2] * $ingredientsArray[$key]['servings'];

                //Add the parameter value after serving size to the parameter aggregate
                $returnArray['parameters'][$value2] = isset($returnArray['parameters'][$value2])
                    ? $returnArray['parameters'][$value2] + $ingredientsArray[$key][$value2]
                    : $ingredientsArray[$key][$value2];
            }
        }

        //Give keyname
        $returnArray['keyname'] = implode('_', $keyNameArray);

        //For each of the parameters given
        foreach ($listParameters as $key => $value) {
            if ($value != 'calories') {
                //Multiply them together
                $returnArray['score'] = isset($returnArray['score'])
                    ? $returnArray['score'] * max($returnArray['parameters'][$value], 0)
                    : max($returnArray['parameters'][$value], 0);
            }
        }

        return $returnArray;
    }

    $part1_array = $part2_array = array();

    $ingredientsArray = getIngredients($input);

    $ingredientList = array_keys($ingredientsArray);

    //Do 100 iterations of the first ingredient
    for ($i = 100; $i >= 0; $i--) {
        $ingredientsArray[$ingredientList[0]]['servings'] = $i;

        for ($o = 0; $o <= 100, $i + $o <= 100; $o++) {
            $ingredientsArray[$ingredientList[1]]['servings'] = $o;

            for ($p = 0; $p <= 100, $i + $o + $p <= 100; $p++) {
                $ingredientsArray[$ingredientList[2]]['servings'] = $p;

                for ($l = 0; $l <= 100, $i + $o + $p + $l <= 100; $l++) {
                    if ($i + $o + $p + $l == 100) {
                        $ingredientsArray[$ingredientList[3]]['servings'] = $l;

                        $mixedIngredients = mixIngredients($ingredientsArray);

                        if (intval($mixedIngredients['score']) > 0) {
                            $part1_array[$mixedIngredients['keyname']] = intval($mixedIngredients['score']);

                            if (intval($mixedIngredients['parameters']['calories'] == 500)) {
                                $part2_array[$mixedIngredients['keyname']] = intval($mixedIngredients['score']);
                            }

                            //prettyPrint($mixedIngredients);
                            //echo '<hr />';
                        }
                    }
                }
            }
        }
    }

    arsort($part1_array);
    arsort($part2_array);

    $cutOff = 10;
    $part1_answer = $part2_answer = 0;

    $i = 1;
    echo '<h3>Part 1</h3>';
    foreach ($part1_array as $key => $value) {
        if ($i >= $cutOff) break;
        if ($i == 1) $part1_answer = $value;
        echo "Score: {$value} --> {$key}<br />";
        $i++;
    }

    $i = 1;
    echo '<h3>Part 2</h3>';
    foreach ($part2_array as $key => $value) {
        if ($i >= $cutOff) break;
        if ($i == 1) $part2_answer = $value;
        echo "Score: {$value} --> {$key}<br />";
        $i++;
    }


    echo '<hr />';

    $result = "<strong>Part 1:</strong> Highest score of #<strong>{$part1_answer}</strong> with 100 servings.";
    $result .= isset($part2_answer)
        ? "<br /><strong>Part 2:</strong> Highest score of #<strong>{$part2_answer}</strong> with 100 servings and max 500 calories."
        : '';

    echo $result;
} catch (Exception $e) {
    $result = 'Caught Exception: ' . $e->getMessage() . ' on line ' . $e->getLine() . '<br />';
    echo $result;
} finally {
    if (isset($memcache)) $memcache->close();
    if (!isset($result)) {
        echo 'Unknown error! #1' . '<br />';
    }
}