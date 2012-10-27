var loc = {
	status : "error",
	position : {
		latitude : "",
		longitude: ""
	}
}

function round1decimal( k){
	return Math.round(k*10) /10;
};

function ceil1decimal(k){
	return Math.ceil(k*10) /10;
}

function floor1decimal(k){
	return Math.floor(k*10) /10;
}

function getIP(){
	/*navigator.geolocation.getCurrentPosition(function(position){
		//console.log('Your Coordinates are'+position.coords.latitude+'&'+position.coords.longitude);
		return {
			latitude : position.coords.latitude,
			longitude: position.coords.longitude
		}
	});*/
	if (navigator.geolocation) {
    	navigator.geolocation.getCurrentPosition(successIP, errorIP);
	}
}

function successIP(position){
	console.log(position);
	loc.status="success";
	loc.position.latitude =position.coords.latitude;
	loc.position.longitude=position.coords.longitude;
}

function errorIP(){
	location.status="error";
}

function getCurrentState(video){
	return  {
				currentTime    : video.currentTime,
				bufferedStart : video.buffered.start(video.buffered.length-1), //TODO: Remove if not needed
				bufferedEnd   : video.buffered.end(video.buffered.length-1),   //TODO: Remove if not needed
				paused		   : video.paused,
				seeking	       : video.seeking
			};
}


function sendVideoEvents(video, close){
	getIP();
	var data = {
		videoInfo : video._model.videoInfo,
		events 	: video._model.events
	};
	if(loc.status!="error"){
		$.extend(data,loc.position);
	}
	$.extend(data,{'endSession':close});
	console.log(data);
	$.post("stats.php", data, function(response){
		if(response.status!="success"){
			sendVideoEvents(video);
		} else {
			video._model.events = []; //Once Events are posted clear them from browser.
			console.log(video._model);
		}
	},"json");
}

document.addEventListener( "DOMContentLoaded", function(){
	var videos = document.getElementsByClassName("_AksharaVideo");
	for (var i = 0; i < videos.length; i++){
		var video = videos[i];
		var renderType = video.dataset.render_type;
		var videoInfo = {
				videoId : video.dataset.video_id,
				tutorId	: video.dataset.tutor_id,
				type	: video.dataset.type,
				source	: video.dataset.source
			};
		video._model = {};
		video._model.states = {};
		video._model.events = [];
		video._model.videoInfo = videoInfo;
		if(renderType=="html5"){
			video.addEventListener( "timeupdate",function(e){
				//console.log( video.buffered, video.currentTime, video.buffered.start(video.buffered.length-1));
				if(video.seeking && !video.pause) {
					video.pause();
					video._model.states['beforeseek']='play';
				}
			});	

			video.addEventListener( "emptied", function(e){
				alert("Something went wrong! Please refresh your page...");
			});

			video.addEventListener( "canplay", function(e){
				var duration = Math.round(video.duration);
			});			

			video.addEventListener( "playing", function(e){
				var videoState = getCurrentState(video);
				videoState['event']="play";
				video._model.events.push(videoState);
			});

			video.addEventListener( "pause", function(e){
				var videoState = getCurrentState(video);
				videoState['event']="pause";
				video._model.events.push(videoState);
				//console.log(video._model);
			});

			video.addEventListener( "seeking", function(e){
				var videoState = getCurrentState(video);
				videoState['event']="seekstart";
				if(videoState['seeking']==true) console.log(videoState);
				video._model.events.push(videoState);
			});

			video.addEventListener( "seeked", function(e){
				var videoState = getCurrentState(video);
				videoState['event']="seekend";
				video._model.events.push(videoState);
				if(video._model.states['beforeseek']=='play'){
					video.play();
				}
			});
				
			video.addEventListener( "ended", function(e){				
				var videoState = getCurrentState(video);
				videoState['event']="end";
				video._model.events.push(videoState);
				//alert("Video ended");
				sendVideoEvents(video,false);
			});
			
		}else if(renderType=="youtube"){
			//TODO: Extend it to youtube
		}else if(renderType=="vimeo"){
		
		}
	};

	$(window).on('beforeunload', function() {
		for (var i = 0; i < videos.length; i++){
			var video = videos[i];
			sendVideoEvents(video,true)
		}
		return ("Thankyou for watching our video!"); //TODO: Prevent closing the browser until we receive a success
	});

	}, false );