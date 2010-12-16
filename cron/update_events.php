<?php

    require_once '../include/config.php';
    require_once '../include/db.php';
    require_once '../include/flickr.php';
    require_once '../include/twitter.php';

    $sql = "SELECT user_id, urlname FROM users";
    $results = db_query($sql);

    foreach($results as $user) {
        $user_id = $user['user_id'];
        print "Fetching for {$user['urlname']}...\n";

        print "Fetching flickr...\n";
        flickr_fetch_photos($user_id);

        print "\n\n";
        print "Fetching Twitter...\n";
        twitter_fetch_tweets($user_id);
    }

