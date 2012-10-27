<?php
  $videoId=$_REQUEST['videoId'];
?>
<!DOCTYPE HTML>
<html>
  <head>
    <style>
      body {
        margin: 0px;
        padding: 0px;
      }
      canvas {
        border: 1px solid black;
      }
      #tango {
        position: absolute;
        top: 10px;
        left: 10px;
        padding: 10px;
      }
      #modalDiv {
        overflow-y: hidden;

      }
      #container {
        margin: 20px;
        background-color : white;
        display: inline-block;
        overflow: hidden;
        height: 452px;
        width: 702px;
        background-position: 1px 1px;
      }
      #overall {
        margin-top: 50px;
        margin-left: 40%;

      }
    </style>
    <script src="res/js/jquery-1.7.2.js"></script>
    <script src="res/js/jquery-ui.min.js"></script>
    <script src="res/fancybox/lib/jquery.mousewheel-3.0.6.pack.js"></script>


    <link rel="stylesheet" href="res/css/jquery-ui.css" type="text/css" media="screen" />
    <link rel="stylesheet" href="res/css/ui.theme.css" type="text/css" media="screen" />

    <!-- Add fancyBox -->
    <link rel="stylesheet" href="res/fancybox/source/jquery.fancybox.css?v=2.0.6" type="text/css" media="screen" />
    <script type="text/javascript" src="res/fancybox/source/jquery.fancybox.pack.js?v=2.0.6"></script>

    <!-- Optionally add helpers - button, thumbnail and/or media -->
    <link rel="stylesheet" href="res/fancybox/source/helpers/jquery.fancybox-buttons.css?v=1.0.2" type="text/css" media="screen" />
    <script type="text/javascript" src="res/fancybox/source/helpers/jquery.fancybox-buttons.js?v=1.0.2"></script>
    <script type="text/javascript" src="res/fancybox/source/helpers/jquery.fancybox-media.js?v=1.0.0"></script>

    <link rel="stylesheet" href="res/fancybox/source/helpers/jquery.fancybox-thumbs.css?v=2.0.6" type="text/css" media="screen" />
    <script type="text/javascript" src="res/fancybox/source/helpers/jquery.fancybox-thumbs.js?v=2.0.6"></script>

    <script src="res/js/kinetic-v3.10.4.js"></script>
    <script src="res/js/worldMap.js"></script>
    <script>
    var countries = [];
    function componentToHex(c) {
        var hex = c.toString(16);
        return hex.length == 1 ? "0" + hex : hex;
    }

    function rgbToHex(r, g, b) {
        return "#" + componentToHex(r) + componentToHex(g) + componentToHex(b);
    }
      window.onload = function() {
        var data = {
          videoId : <?php echo $videoId; ?>
        };
        $.post("mapstats.php", data, function(response){
            $.extend(countries,response);
            console.log(countries);
            winload();
        },"json");
      };

      function winload(){

        $("#modalDiv").dialog({
                modal: true,
                autoOpen: false,
                height: '500',
                width: '680',
                draggable: true,
                resizeable: true,   
                title: 'Histogram view of Statistics'
            });

        var stage = new Kinetic.Stage({
          container: 'container',
          width: 700,
          height: 450
        });
        var mapLayer = new Kinetic.Layer({
          y: 20,
          scale: 0.7
        });

        /*
         * loop through country data stroed in the worldMap
         * variable defined in the worldMap.js asset
         */
        var sum = 0; 
        for(var i =0;i<countries.length;i++){
          sum += parseInt(countries[i].count);
        }
        var name = function(code){
          for(var i =0;i<countries.length;i++){
            if(countries[i].countrycode == code){
              return countries[i].country;
            }
          }
          return "no name"; 
        }
        var hits = function(code){
          for(var i =0;i<countries.length;i++){
            if(countries[i].countrycode == code){
              return parseInt(countries[i].count);
            }
          }
          return 0; 
        }
        

        for(var key in worldMap.shapes) {
          var green = Math.ceil(255*hits(key)/(sum+0.0));
          console.log(key,"key",sum)
          console.log("a",green);
          var path = new Kinetic.Path({
            data: worldMap.shapes[key],
            fill: rgbToHex(0,green,0),
            stroke: '#555',
            strokeWidth: 1,
            name:key
          });
          path.on('mouseover', function(e) {
            this.setFill('darkgreen');
            mapLayer.draw();
          });

          path.on('mouseout', function() {
            var green = Math.ceil(255*hits(this.attrs.name)/(sum+0.0));
            this.setFill(rgbToHex(0,green,0));
            console.log(green);
            mapLayer.draw();
          });
  
      path.on('click', function() {
            this.setFill('#000');
            //console.log(name(this.attrs.name));
            //console.log(hits(this.attrs.name)/sum);
            if(name(this.attrs.name)){
              //window.location="viewstats.php?id=<?php echo $videoId;?>&countryCode="+this.attrs.name;
                url = "viewstats.php?id=<?php echo $videoId;?>&countryCode="+this.attrs.name;
                $("#modalDiv").dialog("open");
                $("#modalIFrame").attr('src',url);
                return false;
            }
            mapLayer.draw();
            
          });

      $("#overall").on('click', function() {
          url = "viewstats.php?id=<?php echo $videoId;?>&countryCode=all";
          $("#modalDiv").dialog("open");
          $("#modalIFrame").attr('src',url);
          return false;
      });
              
          mapLayer.add(path);
        }

        stage.add(mapLayer);

      }

    </script>
  </head>
  <body>    
    <a id="overall" href="#overall">Overall Stats</a>
    <div id="container"></div>
    <div id="modalDiv"><iframe id="modalIFrame" width="100%" height="100%" marginWidth="0" marginHeight="0" frameBorder="0" scrolling="no" title="Video Stats"></iframe></div>
  </body>
</html>
