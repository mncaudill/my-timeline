<?php

    require_once 'config.php';
    require_once 'db.php';

    function twitter_fetch_tweets($screenname, $since_id=0) {

        $page = 1;
        while(true) {

            $url = "http://api.twitter.com/1/statuses/user_timeline.json?screen_name=$screenname&page=$page&count=150";
            $results = json_decode(file_get_contents($url), true);

            if(!$results) {
                break;
            }

            foreach($results as $tweet) {
                if($tweet['geo']) {
                    
                    $db_tweet = array(
                        'tweet_id' => $tweet['id_str'],
                        'date' => strtotime($tweet['created_at']),
                        'lat' => $tweet['geo']['coordinates'][0],
                        'lon' => $tweet['geo']['coordinates'][1],
                        'tweet_url' => "http://www.twitter.com/$screenname/status/{$tweet['id_str']}",
                        'tweet' => $tweet['text'],
                    );

                    print $tweet['text'] . "\n";
                    if(!twitter_insert_tweet($db_tweet)) {
                        print mysql_error() . "\n";
                        exit;
                    }
                }
            }

            $page++;
            sleep(1);
        }

    }

    function twitter_insert_tweet($tweet) {

        $escaped = array();
    
        foreach($tweet as $key => $value) {
            $escaped[$key] = addslashes($value); 
        }

        $query = "INSERT INTO geopoints (user_id, source, source_id, lat, lon, event_time, url, title) values ";
        $query .= "  (1, 2, '{$escaped['tweet_id']}', '{$escaped['lat']}', '{$escaped['lon']}', {$escaped['date']}, '{$escaped['tweet_url']}', '{$escaped['tweet']}')";

        return db_insert($query);
    }

