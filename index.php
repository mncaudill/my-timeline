<?php

    require_once 'include/config.php';
    require_once 'include/db.php';

    // Get last 20 Flickr points
    $query = "SELECT * FROM geopoints WHERE user_id=1 order by posted desc";
    $results = db_query($query);

    $maps_js = 'var points = [';
    foreach($results as $result) {
        $image_url = str_replace('_s.jpg', '_m.jpg', $result['image_url']);
        $html = "<a href=\"{$result['url']}\">{$result['title']}</a><br><img style='width:240px;height:160px;' src=\"{$image_url}\"/>";
        $result['html'] = $html;
        $maps_js .= json_encode($result) . ',';            
    }
    $maps_js .= '];';

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
  <title>myTimeline</title>
</head>
<body style="height:100%;">

  <div id="map" style="width:100%; height: 100%;position:absolute;top:0;left:0;"></div>

  <script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
  <script type="text/javascript">
    function initialize() {
        var latlng = new google.maps.LatLng(-34.397, 150.644);
        var myOptions = {
            zoom: 8,
            center: latlng,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            navigationControl: true,
            navigationControlOptions: {
              style: google.maps.NavigationControlStyle.SMALL
            },
            
        };
        var map = new google.maps.Map(document.getElementById("map"), myOptions);

        <?=$maps_js?>

        var infowindow = new google.maps.InfoWindow();

        function marker_click(marker, point) {
            return function() {
                infowindow.close();
                infowindow.setContent(point.html);
                infowindow.open(map, marker);     
            }
        }

        var bounds = new google.maps.LatLngBounds();
        
        for(i = 0; i < points.length; i++) {
            latlng =  new google.maps.LatLng(points[i].lat, points[i].lon);
            marker = new google.maps.Marker({position: latlng});
            google.maps.event.addListener(marker, 'click', marker_click(marker, points[i]));
            bounds.extend(latlng);
            marker.setMap(map);
        }

        map.fitBounds(bounds);
    }
    initialize();
  </script>

</body>
</html>
