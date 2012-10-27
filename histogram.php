<?php
/*
require("dbconnection.php");
$DBConn = new Connection();

function getHistData($conn, $videoId, $countryCode){
	$events = $conn->get_array("SELECT event.id, viewerId, videoId, event, currentTime, countryCode FROM event LEFT JOIN viewer ON event.viewerId = viewer.id WHERE videoId=? AND countryCode LIKE ? ORDER BY viewer.id ASC,event.id ASC ",array($videoId, $countryCode));
	$duration = $conn->get_array("SELECT duration FROM video WHERE id=?", array($videoId));
	$duration = $duration[0]['duration'];
	$MAX_BARS = 60;
	$nBars = $duration;
	$tick  = 1;
	if($duration > $MAX_BARS){
		$nBars = $MAX_BARS;
		$tick  = ceil($duration/$nBars);
	}
	$viewerIds = $conn->get_array("SELECT DISTINCT viewerId FROM event LEFT JOIN viewer ON event.viewerId = viewer.id WHERE videoId=? AND countryCode LIKE ? ORDER BY viewerId ASC", array($videoId, $countryCode) );
	$hist={};
	foreach ($viewerIds as $key => $viewerId) {
		$hist[$viewerId]={};
		$hist[$viewerId]['prevPos'] = 0;
		$hist[$viewerId]['prevpState'] = 'pause';
		$hist[$viewerId]['prevState'] = 'seekend';
		$hist[$viewerId]['histogram'] = array(
				0 => 0;
		);
	}
	for($i=0;$i<$nBars;$i++){
		//$histogram
	}
	foreach($events as $event){
		$vId = $event['viewerId'];
		$hist[$vId]['histogram']
		if($event['event']=="play" && $hist[$vId]['prevpState']=="pause"){
			if(isset($hist[$vid]['histogram'][ceil($event['currentTime']/$tick)])) {
				$hist[$vid]['histogram'][ceil($event['currentTime']/$tick)]++;
			}
			$hist[$vId]['prevpState']="play";
		}
	}
	print_r($viewerIds);
	print_r($events);
}

$videoId = 1;
$countryCode = "US";

getHistData($DBConn, $videoId, $countryCode);

unset($DBConn);
*/
?>

<?php
session_start();
require("dbconnection.php");
$DBConn = new Connection();

function generate_histogram($DBConn, $video_id, $country_code){
    $viewers = array();
    $duration = $DBConn->get_array("SELECT * FROM video WHERE id = ? ",array($video_id));
    $duration = $duration[0]['duration'];

	$max_intervals = 30;
	$no_of_intervals = 3*$duration;
	if($no_of_intervals>$max_intervals){
		$no_of_intervals = $max_intervals;
	}

    if ($country_code == 'all'){
      $viewers = $DBConn->get_array("SELECT * FROM viewer WHERE videoId = ?", array($video_id));
    } else {
      $viewers = $DBConn->get_array("SELECT * FROM viewer WHERE videoId = ? and countryCode = ?",array($video_id, $country_code));
    }
    //var_dump($viewers);
    $hist = array();
    for ($i = 0; $i < $no_of_intervals; $i++){
      array_push($hist, 0);
    }

    $no_of_viewers = 0;
    
    foreach ($viewers as $v){
	    $events = $DBConn->get_array("SELECT * FROM event WHERE viewerId = ? ORDER BY id", array($v["id"]));
	    //echo "asa";var_dump($events);
	    if (count($events) == 0){
			continue;
      	}
      	$no_of_viewers++;
      	$hist_one = generate_array($events, $no_of_intervals, $duration);
		//var_dump($hist_one);
      	for ($i = 0; $i < $no_of_intervals; $i++){
			$hist[$i] += $hist_one[$i];
      	}
    }
    
    for ($i = 0; $i < $no_of_intervals; $i++){
      	$hist[$i] = $hist[$i]/$no_of_viewers;
    }
	//var_dump($viewers);
    return $hist;
  }
  
  function generate_array($events, $no_of_intervals, $duration){
    $hist = array();
    for ($i = 0; $i < $no_of_intervals; $i++){
      array_push($hist, 0);
    }
    $no_of_events = count($events);
    $cur = 0;
    $k = 0;
    while ($k < $no_of_events){
    	if ($events[$k]['event'] == 'play'){
    		$cur = $events[$k]["currentTime"];
    		break;
    	}
    	$k++;
    }
    //var_dump($events);
    //var_dump($hist);
    for ($i = $k; $i < $no_of_events; $i++){
      	if ($events[$i]["event"] == 'seekstart' || $events[$i]["event"] == 'end'){
			$next = $events[$i]["currentTime"];
			$start_index = ($cur / $duration) * $no_of_intervals;
			$start_index = floor($start_index);
			$end_index = ($next / $duration) * $no_of_intervals;
			$end_index = floor($end_index);
			for ($j = $start_index; $j < $end_index; $j++){
	  			$hist[$j] += 1;
			}
			$cur = $next;
      	} else if($events[$i]["event"] == 'seekend'){
			$next = $events[$i]["currentTime"];
			$cur = $next;
	    }
    	//var_dump($hist);
    }
    return $hist;
  }

$videoId = addslashes($_REQUEST["videoId"]);
$countryCode = addslashes($_REQUEST["countryCode"]);

echo json_encode(generate_histogram($DBConn, $videoId, $countryCode));

?>