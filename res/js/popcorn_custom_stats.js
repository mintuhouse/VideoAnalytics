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

function createState(time, evt){
	return {
		currentTime : time,
		'event' 	: evt,
		bufferedStart : 0,
		bufferedEnd	  : 0,
		paused		  : 0,
		seeking 	  : 0
	};
}

function sendVideoEvents(video, close){
	return;
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
				var curTime = video.currentTime;
				var prevTime = video._model.states['prevTime'];
				video._model.states['curTime']=curTime;
				if(curTime>prevTime && curTime-prevTime<0.4){
					if(video._model.states['prevState']=='seekstart') {
						video._model.events.push(createState(prevTime,"seekend"));
						video._model.states['prevState'] = 'seekend';
					}
					if(video._model.states['prevpState']=='pause'){
						video._model.events.push(createState(prevTime,"play"));
						video._model.states['prevpState'] = 'play';
					}
					console.log("1","cur",curTime,"prev",prevTime);
				}else if(curTime>prevTime && curTime-prevTime>0.4){
					if(video._model.states['prevState']!='seekstart') {
						video._model.events.push(createState(prevTime,"seekstart"));
						video._model.states['prevState'] = 'seekstart';
					}
					console.log("2","cur",curTime,"prev",prevTime);
				}else if(curTime<prevTime){
					if(video._model.states['prevState']!='seekstart') {
						video._model.events.push(createState(prevTime,"seekstart"));
						video._model.states['prevState'] = 'seekstart';
					}
					console.log("3","cur",curTime,"prev",prevTime);
				}else{
					console.log("4","cur",curTime,"prev",prevTime);
				}
				video._model.states['prevTime']=curTime;
			});	

			video.addEventListener( "emptied", function(e){
				alert("Something went wrong! Please refresh your page...");
			});

			video.addEventListener( "canplay", function(e){
				var duration = video.duration;
				$.extend(videoInfo, {'duration' : duration });
				video._model.states['prevTime']=0;
				video._model.states['prevpState']='pause';
			});
				
			/*video.addEventListener( "playing", function(e){
				var videoState = getCurrentState(video);
				videoState['event']="play";
				video._model.events.push(videoState);
			});*/

			video.addEventListener( "pause", function(e){
				var videoState = getCurrentState(video);
				videoState['event']="pause";
				video._model.states['prevpState']='pause';
				video._model.events.push(videoState);
			});

			video.addEventListener( "ended", function(e){					
				video._model.events.push(createState(video.currentTime,"end"));
				sendVideoEvents(video,false);
			});
			
		}else if(renderType=="youtube"){
			//TODO: Extend it to youtube
		}else if(renderType=="vimeo"){
		
		}


		var postdata = {
			videoId : videoInfo.videoId,
			countryCode : document.getElementById("canvas_"+videoInfo.videoId).dataset.country_code
		};
		$.post("histogram.php", postdata, function(histData){
			var max = Math.max.apply(Math, histData);
			var f = 20/max;
			for(var i=0;i<histData.length;i++){
				histData[i]=histData[i]*f;
			}
			var bar = new RGraph.Bar('canvas_'+videoInfo.videoId, histData);
		    bar.Set('chart.gutter.left', 2);
		    bar.Set('chart.colors', ['blue']);
		    bar.Set('chart.strokestyle','darkblue');
		    //bar.Set('chart.ymax', 60);
		    bar.Set('chart.noyaxis',true);
		    bar.Set('chart.noxaxis',true);
		    bar.Set('chart.background.grid',false);
		    bar.Set('chart.tooltips',histData);
		    bar.Set('chart.tooltips.event','onmousemove');
		    bar.Set('chart.tooltips.css.class', 'bar_chart_tooltips_css');
		    //bar.Set('chart.labels', ['1', '2','3', '4', '5', '6', '7', '8', '9', '10', '11', '12']);
		    //bar.Set('chart.events.mousemove', function (e, b) {alert("number of times that part watched = " + b[0].getValue(e))});
		    RGraph.Effects.Bar.Wave2(bar);
		    bar.Draw();
		},"json");

	};

	$(window).on('beforeunload', function() {
		for (var i = 0; i < videos.length; i++){
			var video = videos[i];
			sendVideoEvents(video,true)
		}
		//return ("Thankyou for watching our video!"); //TODO: Prevent closing the browser until we receive a success
	});


	}, false );