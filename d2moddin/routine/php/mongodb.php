<?php
require_once('../../../connections/parameters.php');
require_once('../../../global_functions.php');

try {
    // connect
    $m = new MongoClient("mongodb://$d2moddin_mongo_username:$d2moddin_mongo_password@$d2moddin_mongo_host/$d2moddin_mongo_database");

    // select a database
    $db = $m->d2moddin;

    // select a collection (analogous to a relational database's table)
    $collection = $db->matchResults;

    //$d2moddin_con = MongoConnect($d2moddin_mongo_username, $d2moddin_mongo_password, $d2moddin_mongo_database, $d2moddin_mongo_host);

    // find everything in the collection
    $cursor = $collection->find(array('mod' => "pudgewars"))->limit(2)->sort(array("_id" => -1));//->sort(array('date' => -1));

    // iterate through the results
    echo '<pre>';
    foreach ($cursor as $document) {
        print_r($document);
    }
    echo '</pre>';

} catch (Exception $e) {
    echo $e->getMessage();
}