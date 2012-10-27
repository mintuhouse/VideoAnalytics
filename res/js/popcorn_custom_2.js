function round1decimal( k){
	return Math.round(k*10) /10;
};

function ceil1decimal(k){
	return Math.ceil(k*10) /10;
}

function floor1decimal(k){
	return Math.floor(k*10) /10;
}

function getCurrentState(video){
	return  {
				currentTime    : video.currentTime,
				buffered_start : video.buffered.start(video.buffered.length-1),
				buffered_end   : video.buffered.end(video.buffered.length-1),
				paused		   : video.paused
			};

}

document.addEventListener( "DOMContentLoaded", function(){
	var videos = document.getElementsByClassName("_AksharaVideo");
	for (var i = 0; i < videos.length; i++){
		var video = videos[i];
		video._model = {};
		video._model.events = [];
		//video._model.timeline = [];
		//video._model.pauseplay = [];
		//video._model.duration = [];
		var renderType = video.dataset.render_type;
		var videoInfo = {
				videoId 	: video.dataset.video_id,
				tutorId	: video.dataset.tutor_id,
				type		: video.dataset.type,
				source	: video.dataset.source
			};
		if(renderType=="html5"){
			video.addEventListener( "timeupdate",function(e){
				//console.log( video.buffered, video.currentTime, video.buffered.start(video.buffered.length-1));
				var url = "stats.php";
				var videoState = getCurrentState(video);
				video._model.timeline.push(videoState);
				$.extend(videoState, videoInfo);
				/*$.post(url, videoState, function(response){ 
					if(response.status != "success"){
						$("#info_"+video.id).html("Couldn't update information!");
						setInterval(function(){$("#info_"+video.id).html("")},250); //TODO: Check it!
					}
				},"json");*/
				//TODO: Remove
			});	

			video.addEventListener( "emptied", function(e){
				alert("Something went wrong! Please refresh your page...");
			});

			video.addEventListener( "canplay", function(e){
				var duration = Math.round(video.duration);
				for(var i=0;i<duration;i++){
					video._model.duration.push(0);
				}
			});			

			video.addEventListener( "pause", function(e){
				var videoState = getCurrentState(video);
				//videoState['event']="pause";
				video._model.pauseplay.push(videoState);
				console.log(video._model);
			});

			video.addEventListener( "playing", function(e){
				var videoState = getCurrentState(video);
				//videoState['event']="play";
				video._model.pauseplay.push(videoState);
			});
				
			video.addEventListener( "ended", function(e){
				alert("Video ended");
				$.data(video, "model", video._model);
			});
			
		}else if(renderType=="youtube"){
		
		}else if(renderType=="vimeo"){
		
		}
	};

	$(window).on('beforeunload', function() {
		for (var i = 0; i < videos.length; i++){
			var video = videos[i];
			$.post("stats.php", video._model, function(response){

			},"json");
		}
		return ("Thankyou for watching our video!"); //TODO: Prevent closing the browser until we receive a success
	});

	
	/*
	var custom = Popcorn( "#video_1" );
	custom.footnote({
		start: 2,
		end: 5,
		target: "testinfo",
		text: "Pop!"
	});
	document.getElementById("video_1").addEventListener("play",function(){
		$("#events").append("Play!<br/>");
	});
	var youtube = Popcorn.youtube(
		'#video',
		'http://www.youtube.com/watch?v=CxvgCLgwdNk' );
	youtube.footnote({
		start: 2,
		end: 6,
		text: "Pop!",
		target: "testinfo"
	});
	
	youtube.play();
	
	var vimeo = Popcorn.vimeo(
         '#video1',
         'http://player.vimeo.com/video/25107077');
         vimeo.footnote({
           start: 1,
           end: 3,
           text: "Pop!",
           target: "testinfo"
         });
         vimeo.play();
	*/
	}, false );