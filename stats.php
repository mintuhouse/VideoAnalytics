<?php
	session_start();
	require("dbconnection.php");
	$DBConn = new Connection();	

	function getRealIpAddr()
	{
	    /*if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
	    {
	      $ip=$_SERVER['HTTP_CLIENT_IP'];
	    }
	    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
	    {
	      $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
	    }
	    else
	    {*/
	      $ip=$_SERVER['REMOTE_ADDR'];
	    //}
	    return $ip;
	}

	function curl($url){
		//echo $url;
	    $ch = curl_init();
	    //curl_setopt($ch, CURLOPT_PROXY, "http://netmon.iitb.ac.in:80");
	    //curl_setopt($ch, CURLOPT_PROXYUSERPWD, "hasankumar_reddy:hasan&");
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    	curl_setopt($ch, CURLOPT_URL, $url);
	    $v=curl_exec($ch);
    	curl_close($ch);
    	return $v;
	}


	if(!isset($_POST["latitude"])){
		$text = getRealIpAddr();
		//echo $text;
		//$text = "58.146.96.6"; //TODO: Change it if we are accessing from outside IITB
	}else {
		$text = $_POST["latitude"]." , ".$_POST["longitude"];
	}
	$url = 'http://query.yahooapis.com/v1/public/yql?q=select%20countrycode%2C%20country%20from%20geo.placefinder%20where%20text%3D%22'.$text.'%22&format=json&diagnostics=true&callback=';
	$data = curl($url);
	$json = json_decode($data,true);
	$countryFromIP = $json["query"]["results"]["Result"];
	if(is_null($countryFromIP)){
		$countryFromIP["country"]="unknown";
		$countryFromIP["countrycode"]="WN";
	}
	$videoInfo = $_POST['videoInfo'];
	$events	   = $_POST['events'];
	//print_r($events);

	if($videoInfo['duration']>0){
		$duration = $DBConn->get_array("SELECT duration FROM video WHERE id=?",array($videoInfo['videoId']));
		if($duration[0]['duration']==0)
			$rc = $DBConn->run_query("UPDATE video SET duration=? WHERE id=?", array($videoInfo['duration'], $videoInfo['videoId']));
	}
	$viewer = $DBConn->get_array("SELECT id FROM viewer WHERE sessionId=?",array(session_id()));
	$insert_id="";
	if(count($viewer)>0) {
		$insert_id = $viewer[0]['id'];
	}else{
		$rc = $DBConn->run_query("INSERT INTO `viewer` (`sessionId`,`countryCode`,`country`,`videoId`) VALUES (?,?,?,?)",array(session_id(),$countryFromIP['countrycode'],$countryFromIP['country'],$videoInfo['videoId']));
		if($rc){
			$insert_id = $DBConn->get_insert_id($rc);
		}
	}
	if($_POST['endSession']=="true") session_regenerate_id(true);
	$query = "INSERT INTO `event` ( viewerId, event, currentTime, bufferedStart, bufferedEnd, paused, seeking) VALUES ";
	$values = array();
	foreach($events as $key => $event){
		$query = $query." (?,?,?,?,?,?,?),";
		array_push($values, $insert_id, $event['event'], $event['currentTime'], $event['bufferedStart'], $event['bufferedEnd'], $event['paused'], $event['seeking']);
	}
	$query = substr($query, 0, -1);
	$rc1 = $DBConn->run_query($query,$values);
	if($rc1){
		$return["status"]="success";
	}
	else $return["status"]="failure";

	if($return["status"]=="success") {}
	echo json_encode($return);
	unset($DBConn);
?>
