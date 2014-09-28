<?php

$query = '';
$i = 0;
foreach($_POST as $key => $value){
    if($i == 0){
        $query .= $key . '=' . $value;
    }
    else{
        $query .= '&'.$key . '=' . $value;
    }
    $i++;
}

//echo json_encode($_POST);
header("Location: ../#backpack/?".$query);
