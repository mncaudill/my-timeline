<?php

    require_once 'include/config.php';
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
    map.setCenter(new CM.LatLng(37.7694,-122.4462), 13);
    map.addControl(new CM.LargeMapControl());

  </script>

</body>
</html>
