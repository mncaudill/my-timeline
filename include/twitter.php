<?php

    require_once 'config.php';
    require_once 'db.php';

    function twitter_format_pin($tweet) {
        $html = wordwrap($tweet['title'], 50) . '<br>';
        $html .= "<a href=\"{$tweet['url']}\">" . date('F j, Y \a\t g:i:s a', strtotime($tweet['event_time'])) . "</a>";
        return $html;
    }

    function twitter_fetch_tweets($user_id) {

        $sql = "SELECT twitter_screenname FROM users WHERE user_id=$user_id";
        $result = db_query($sql);

        if(!$result) {
            return false;
        }

        $screenname = $result[0]['twitter_screenname'];

        // Get largest twitter ID
        $sql = "SELECT source_id FROM geopoints WHERE source=2 AND user_id=$user_id ORDER BY event_time DESC LIMIT 1";
        $result = db_query($sql);

        if(!$result) {
            $since_id = 0;    
        } else {
            $since_id = $result[0]['source_id'];
        }

        $page = 1;
        while(true) {

            $url = "http://api.twitter.com/1/statuses/user_timeline.json?screen_name=$screenname&page=$page&count=150";
            if($since_id) {
                $url .= "&since_id=$since_id";
            }
            $results = json_decode(file_get_contents($url), true);

            if(!$results) {
                break;
            }

            foreach($results as $tweet) {
                if($tweet['geo']) {
                    
                    $db_tweet = array(
                        'user_id' => $user_id,
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
        $query .= "  ({$escaped['user_id']}, 2, '{$escaped['tweet_id']}', '{$escaped['lat']}', '{$escaped['lon']}', FROM_UNIXTIME({$escaped['date']}), '{$escaped['tweet_url']}', '{$escaped['tweet']}')";

        return db_insert($query);
    }

