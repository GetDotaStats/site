<?php
	require_once('./functions.php');
	require_once('./connections/parameters.php');
?>

				<?php
try{
	$db = new dbWrapper($hostname, $username, $password, $database, false);
		
	if($db){
		$memcache = new Memcache;
		$memcache->connect("localhost",11211); # You might need to set "localhost" to "127.0.0.1"
							
		$match_db_details = get_match_db_details($db);
							
		echo generate_header($match_db_details); //GENERATE THE CONSISTANT HEADER
	}
	else{
		echo 'No DB';
	}
}
catch (Exception $e){
	echo $e->getMessage();
}
				?>
