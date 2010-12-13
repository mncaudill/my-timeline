<?php

    require_once 'include/config.php';
    require_once 'include/db.php';

    // Get last 20 Flickr points
    $query = "SELECT * FROM geopoints WHERE user_id=1 LIMIT 50";
    $results = db_query($query);

    $maps_js = 'var points = [';
    foreach($results as $result) {
        $html = "<a href=\"{$result['url']}\">{$result['title']}</a><br><img src=\"{$result['image_url']}\"/>";
        $result['html'] = $html;
        $maps_js .= json_encode($result) . ',';            
    }
    $maps_js .= '];';

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
  <title>CloudMade JavaScript API example</title>
</head>
<body style="height:100%;">

  <div id="cm-example" style="width:100%; height: 100%;position:absolute;top:0;left:0;"></div>

  <script type="text/javascript" src="http://tile.cloudmade.com/wml/latest/web-maps-lite.js"></script>
  <script type="text/javascript">
    var cloudmade = new CM.Tiles.CloudMade.Web({styleId: 998, key: '<?=$services_auth['cloudmade']['api_key']?>'});
    var map = new CM.Map('cm-example', cloudmade);
    map.addControl(new CM.LargeMapControl());

    <?=$maps_js?>

    var latlngs = [];

    for(i = 0; i < points.length; i++) {
        latlng = new CM.LatLng(points[i].lat, points[i].lon);
        latlngs.push(latlng);
    }

    var bounds = new CM.LatLngBounds(latlngs);
    map.zoomToBounds(bounds);

    var markers = [];

    function marker_click(marker, point) {
        return function(latlng) {
            marker.openInfoWindow(point.html, {maxWidth: 100});
        }
    }

    for(i = 0; i < latlngs.length; i++) {
        marker = new CM.Marker(latlngs[i]);
        CM.Event.addListener(marker, 'click', marker_click(marker, points[i]));
        markers.push(marker);
    }

    var clusterer = new CM.MarkerClusterer(map, {clusterRadius: 70});
    clusterer.addMarkers(markers);

  </script>

</body>
</html>
