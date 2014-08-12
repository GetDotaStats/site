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

if (!function_exists("GetHeroes")) {
    function getHeroes($steam_api_key){
        $url = 'http://api.steampowered.com/IEconDOTA2_570/GetHeroes/v0001/?language=en&key='.$steam_api_key;

        $matches = json_decode(curl($url), true);

        if(empty($matches)){
            sleep(1);
            $matches = json_decode(curl($url), true);
        }

        return $matches;
    }
}

if (!function_exists("grab_heroes")) {
    function grab_heroes($api_key, $time_to_store_secs = 600){
        global $memcache;

        $heroes = $memcache->get("d2_heroes");
        if(!$heroes){
            $heroes = GetHeroes(false, $api_key);

            if($heroes){
                $memcache->set("d2_heroes", $heroes, 0, $time_to_store_secs);
            }
        }

        return $heroes;
    }
}