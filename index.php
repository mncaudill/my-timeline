<?php
    require_once 'include/config.php';
    require_once 'include/db.php';
    require_once 'include/flickr.php';
    require_once 'include/twitter.php';

    // Who's here?
    if(!isset($_GET['user'])) {
        echo "Must pass in user";
        exit;
    }

    $username = $_GET['user'];

    $sql = "SELECT user_id FROM users WHERE urlname='" . addslashes($username) . "'";
    $result = db_query($sql);

    if(!$result) {
        echo "No user found";
        exit;
    }

    $user_id = $result[0]['user_id'];

    // Get month
    $month = isset($_GET['month']) && intval($_GET['month']) ? $_GET['month'] : date('n');
    $year = isset($_GET['year']) && intval($_GET['year']) ? $_GET['year'] : date('Y');

    // Get last 20 Flickr points
    $query = "SELECT *, DATE_FORMAT(event_time, '%Y-%c') ym FROM geopoints WHERE user_id=$user_id ORDER BY event_time";
    $results = db_query($query);

    $points = array();
    $seen_latlngs = array();
    foreach($results as $result) {

        $orig_lat = $result['lat'];
        $orig_lon = $result['lon'];
        $latlng_hash = "{$result['lat']}={$result['lon']}";
        if(isset($seen_latlngs[$latlng_hash])) {
            $count = 0;
            while(isset($seen_latlngs[$latlng_hash]) && $count < 100) {
                // Randomly place it really close. The little algorithm below is fuzzy, but gives satisfactory results.
                // This gives you 100 nearby points to try. If you have 100 all within the same spot, oh well.

                // Come up with a lat
                $lat_diff = mt_rand(3, 7) / 100000;
                if(mt_rand(0, 1)) {
                    $result['lat'] = $orig_lat + $lat_diff;
                } else {
                    $result['lat'] = $orig_lat - $lat_diff;
                }

                // Come up with a lon
                $lon_diff = mt_rand(3, 7) / 10000;
                if(mt_rand(0, 1)) {
                    $result['lon'] = $orig_lon + $lon_diff;
                } else {
                    $result['lon'] = $orig_lon - $lon_diff;
                }
                
                $latlng_hash = "{$result['lat']}={$result['lon']}";
                $count++;
            }

            $seen_latlngs[$latlng_hash] = 1;

        } else {
            $seen_latlngs[$latlng_hash] = 1;
        }


        if($result['source'] == 1) {
            $result['html'] = flickr_format_pin($result);
        } else { // Twitter
            $result['html'] = twitter_format_pin($result);
        }

        $points[] = $result;
    }

    $points_js = json_encode($points);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
  <title>myTimeline</title>
  <style type="text/css">
    body {
        font-family: arial;
        font-size: 12px;
    }
  </style>
</head>
<body style="height:100%;">

  <div id="map" style="width:100%;height:90%;position:absolute;top:0;left:0;"></div>
  <div id="control" style="width:100%;height:10%;position:absolute;bottom:0;">
    <form id="date-selector" method="GET" action="<?=$_SERVER['REQUEST_URI']?>">
        <input type="hidden" name="user" value="<?=str_replace('"', '\"', $username)?>">
        <select name="month">
            <option <?= ($month == 1) ? 'selected' : '' ?> value="1">January</option>
            <option <?= ($month == 2) ? 'selected' : '' ?> value="2">February</option>
            <option <?= ($month == 3) ? 'selected' : '' ?> value="3">March</option>
            <option <?= ($month == 4) ? 'selected' : '' ?> value="4">April</option>
            <option <?= ($month == 5) ? 'selected' : '' ?> value="5">May</option>
            <option <?= ($month == 6) ? 'selected' : '' ?> value="6">June</option>
            <option <?= ($month == 7) ? 'selected' : '' ?> value="7">July</option>
            <option <?= ($month == 8) ? 'selected' : '' ?> value="8">August</option>
            <option <?= ($month == 9) ? 'selected' : '' ?> value="9">September</option>
            <option <?= ($month == 10) ? 'selected' : '' ?> value="10">October</option>
            <option <?= ($month == 11) ? 'selected' : '' ?> value="11">November</option>
            <option <?= ($month == 12) ? 'selected' : '' ?> value="12">December</option>
        </select>
        <input type="text" name="year" value="<?=$year?>" size=4 maxlength=4>
        <input type="submit">
    </form>
  </div>

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

        var points = <?=$points_js?>;

        var infowindow = new google.maps.InfoWindow();

        function marker_click(marker, point) {
            return function() {
                infowindow.close();
                infowindow.setContent('<br>' + point.html);
                infowindow.open(map, marker);     
            }
        }

        var bounds = new google.maps.LatLngBounds();

        var active_month = '<?="$year-$month"?>';

        // Create markers for everything
        var markers = [];
        
        for(var i in points) {
            point = points[i];
            latlng =  new google.maps.LatLng(point.lat, point.lon);
            point.latlng = latlng;
            marker = new google.maps.Marker({position: latlng});
            google.maps.event.addListener(marker, 'click', marker_click(marker, point));
            markers.push(marker);
        }

        function highlight_month(year, month) {
            var ym = year + "-" + month;
            var bounds = new google.maps.LatLngBounds();
            for(var i in points) {
                point = points[i];
                if(point.ym == ym) {
                    markers[i].setMap(map);
                    bounds.extend(points[i].latlng);
                } else {
                    markers[i].setMap(null);
                }
            }

            map.fitBounds(bounds);
            
            return false;
        }

        highlight_month(<?=$year?>, <?=$month?>);

        var form = document.getElementById('date-selector');
        form.onsubmit = function() {
            infowindow.close();
            month = form.month.value;
            year = form.year.value;
            highlight_month(year, month);
            if(history.pushState) {
                url = window.location.pathname + '?user=<?=$username?>&month=' + month + '&year=' + year;
                history.pushState({}, '', url);
            }
            return false;
        }

    }
    initialize();
  </script>

</body>
</html>
