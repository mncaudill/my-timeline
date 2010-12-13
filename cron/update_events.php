<?php

    require_once '../include/config.php';
    require_once '../include/flickr.php';
    
    # TODO: This will loop through the user db when I get that far
    $nsid = '52777706@N00';
    flickr_fetch_photos($nsid);
