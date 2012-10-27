<?php
	session_start();
	if(isset($_GET['id'])){
	$_SESSION['videoId'] = addslashes($_GET['id']);
	$_SESSION['status']  = "begin";
	$_SESSION['position']= "0s";
	$_SESSION['renderType'] = "";
	$countryCode = "all";
	if(isset($_REQUEST['countryCode'])){
		$countryCode = $_REQUEST['countryCode'];
	}
	
	$u_agent = $_SERVER['HTTP_USER_AGENT'];
	if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)) $ub = "MSIE";
    elseif(preg_match('/Firefox/i',$u_agent)) $ub = "Firefox"; 
    elseif(preg_match('/Chrome/i',$u_agent)) $ub = "Chrome"; 
    elseif(preg_match('/Safari/i',$u_agent)) $ub = "Safari"; 
	elseif(preg_match('/Opera/i',$u_agent)) $ub = "Opera";
	
	require("dbconnection.php");
	$DBConn = new Connection();	
?>
<html>
<head>
	<title>VideoAnalysis</title>
	<meta charset="utf-8">
	<script src="res/js/jquery-1.7.2.js"></script>
	<script src="res/js/popcorn-complete.js"></script>
	<script src="res/js/popcorn_custom_stats.js"></script>
    <script src="res/js/RGraph.common.core.js" ></script>
    <script src="res/js/RGraph.common.dynamic.js" ></script>
    <script src="res/js/RGraph.common.effects.js" ></script>
    <script src="res/js/RGraph.common.tooltips.js" ></script>
    <script src="res/js/RGraph.common.key.js" ></script>
    <script src="res/js/RGraph.bar.js" ></script>

 	<link href="res/css/viewstats.css" media="screen" rel="stylesheet" type="text/css">
</head>
<body>
	<div id="events" class="_AksharaNotifications" style="float:right"></div>
	<span clear="both"></span>
	<?php
		$videos = $DBConn->get_array("SELECT * FROM video LEFT OUTER JOIN source ON video.id = source.videoId WHERE video.id=? ORDER BY source.type", array($_SESSION['videoId']));		
		$tutors = $DBConn->get_array("SELECT * FROM tutor WHERE id=?", array($videos[0]['tutorId']));
		$tutor = $tutors[0];
	?>
	<?php 
	if(count($videos)>0){
	
		/** Determining the type of video **/
		$youtube=0;
		$vimeo  =0;
		foreach ($videos as $key => $video){
			if ((in_array($ub,array("MSIE","Chrome","Safari"))&&($video['type']=="video/mp4"))
				|| (in_array($ub,array("Firefox","Opera")) && $video['type']=="video/webm")
				|| (in_array($ub,array("Opera","Chrome")) && $video['type']=="video/ogv")){
				$_SESSION['renderType']="html5";
			}
			elseif ( $video['type'] == "youtube") $youtube=1;
			elseif ( $video['type'] == "vimeo")   $vimeo=1;	
		}
		if($_SESSION['renderType']==""){
			if(youtube  ==1) $_SESSION['renderType']="youtube";
			elseif(vimeo==1) $_SESSION['renderType']="vimeo";			
		}
	?>
	<?php 
	if($_SESSION['renderType']=="html5") { 
	/** HTML5 */
	?>	
	<canvas class="_AksharaCanvas" id="<?php echo "canvas_".$video['videoId'];?>"
		 width="493.5" height="200" data-country_code="<?php echo $countryCode;?>"></canvas>
    
	<video height="400" width="600" class="_AksharaVideo" controls="controls"
		id="<?php echo "video_".$video['videoId']; ?>"
		data-video_id="<?php echo $video['videoId']; ?>"
		data-tutor_id="<?php echo $video['tutorId']; ?>"
		data-type="<?php echo $video['type']; ?>"
		data-source="<?php echo $video['source'];?>"
		data-render_type="<?php echo $_SESSION['renderType'];?>"
		onmouseover="document.getElementById('canvas_<?php echo $video['videoId'];?>').style.display = 'block'; return true;"
		> 
		<?php 
		foreach ($videos as $key => $video){
			if(in_array($video['type'],array("video/mp4","video/webm","video/ogv"))) {
			/** Fallback incase format is not supported in the browser */
		?>	<source src="<?php echo $video['source'];?>" type="<?php echo $video['type']; ?>" />
		<?php }} ?>
		Your browser does not support the video tag. Upgrade to a Modern Browser!
	</video>
	<div class="_AksharaInformation" id="info_<?php echo $video['videoId']; ?>"></div>
	<?php } 
	elseif($_SESSION['renderType']=="youtube") { 
		/** Youtube */
	}
	elseif($_SESSION['renderType']=="vimeo") { 
		/** Vimeo */
	}
	?>
	<!--
	<div id="video" style="width: 360px; height: 300px;" ></div>
	<div id="video1" style="width: 360px; height: 300px;" ></div>
	-->

<?php
	} else {
		echo "No video found";
	}
	unset($DBConn);
	} else {
		echo "Search:"; 
	}
?>	
</body>
</html>