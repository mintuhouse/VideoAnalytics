<?php
	require("dbconnection.php");
	$DBConn = new Connection();	

	$videoId=$_REQUEST['videoId'];

	$users = $DBConn->get_array("SELECT count(id) AS count, countryCode, country FROM  viewer WHERE videoId=? GROUP BY (countryCode)",array($videoId));

	$data = array();

	foreach($users as $user){
		$obj["countrycode"] = $user["countryCode"];
		$obj["country"] = $user["country"];
		$obj["count"]	= $user["count"];
		array_push($data, $obj);
	}

	echo json_encode($data);

	unset($DBConn);
?>