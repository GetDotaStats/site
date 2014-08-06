<?php
///////////////////////////////////////
// MONGO DB FUNCTIONS
///////////////////////////////////////

//find the most recent match
if (!function_exists('mostRecentMatch')) {
    function mostRecentMatch($db)
    {
        $mostRecentMatch = $db->q('SELECT MAX(`match_date`) as match_date FROM match_stats;');
        $mostRecentMatch = !empty($mostRecentMatch[0]['match_date'])
            ? $mostRecentMatch[0]['match_date']
            : 0;

        return $mostRecentMatch;
    }
}

//run a query or check the number of results
if (!function_exists('searchMongoD2moddin')) {
    function searchMongoD2moddin($tableRef, $mostRecentMatch, $check = 0, $limit = 10)
    {
        if ($check) {
            //'mod' => "lod",
            $cursor = $tableRef->find(array('date' => array('$gt' => $mostRecentMatch)))->limit(1)->sort(array("date" => 1))->count();
        } else {
            $cursor = $tableRef->find(array('date' => array('$gt' => $mostRecentMatch)))->limit($limit)->sort(array("date" => 1));
        }
        return $cursor;
    }
}