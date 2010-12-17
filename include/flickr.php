<?php

    require_once 'config.php';
    require_once 'db.php';

    function flickr_fetch_photos($user_id) {

        $sql = "SELECT flickr_nsid FROM users WHERE user_id=$user_id";
        $result = db_query($sql);

        if(!$result) {
            return false;
        }

        $nsid = $result[0]['flickr_nsid'];

        // Get ID of first photo from the last run
        $sql = "SELECT flickr_last_run_id FROM users WHERE user_id=$user_id";
        $result = db_query($sql);

        $last_seen_id = null;
        if($result) {
            $last_seen_id = $result[0]['flickr_last_run_id'];
        }

        $failed = false;
        $photos = array();
        $curr_page = 1;
        $count = 1;

        $done = false;
        $extras = "description,date_taken,owner_name,geo,path_alias,url_s";
        do {
            $rsp = flickr_api_call('flickr.people.getPublicPhotos', array('user_id' => $nsid, 'page' => $curr_page, 'extras' => $extras)); 
            if($rsp->stat === 'ok') {
                $curr_page++;
                $pages = $rsp->photos->pages;
                
                foreach ($rsp->photos->photo as $photo_info) {

                    // Get info
                    $photo_id = $photo_info->id;

                    if($last_seen_id && $photo_id == $last_seen_id) {
                        print "Matches last run id. Breaking...\n";
                        $done = true;
                        break;
                    }

                    if($count == 1) {
                        $sql = "UPDATE users SET flickr_last_run_id='" . addslashes($photo_id) . "' WHERE user_id=$user_id";
                        db_insert($sql);
                    }

                    print "$count. Fetching photo: $photo_id\n";

                    $count++;
                    if(!empty($photo_info->latitude)) {
                        $photo = array();
                        $photo['user_id'] = $user_id;
                        $photo['photo_id'] = $photo_id;
                        $photo['title'] = $photo_info->title;
                        $photo['description'] = $photo_info->description->_content;
                        $photo['date'] = $photo_info->datetaken;
                        $photo['latitude'] = $photo_info->latitude;
                        $photo['longitude'] = $photo_info->longitude;
                        $photo['image_url'] = $photo_info->url_s;
                        $photo['url'] = $photo_info->pathalias 
                            ? "http://www.flickr.com/{$photo_info->pathalias}/$photo_id" 
                            : "http://www.flickr.com/$nsid/$photo_id";
                        $photo['posted'] = $photo_info->datetaken;

                        if(!flickr_insert_photo($photo)) {
                            $done = true;        
                            break;
                        }
                    }

                    // Sleep for quarter second
                    usleep(250000);
                }

            } 
        } while ($curr_page <= $pages && !$done);

        return $photos;
    }

    function flickr_get_photo_info($photo_id) {
        
        $rsp = flickr_api_call('flickr.photos.getInfo', array('photo_id' => $photo_id));

        if ($rsp->stat == 'ok') {
            return $rsp->photo;    
        }

        return false;
    }

    function flickr_api_call($method, $args=array()) {
        global $services_auth;

        $flickr_key = $services_auth['flickr']['key'];

        $url = 'http://api.flickr.com/services/rest?';
        $url .= "&format=json&nojsoncallback=1&method=$method&api_key=$flickr_key";

        if($args) {
            $url .= '&' . http_build_query($args);    
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response);
    }

    function flickr_get_image_url($photo_info) {
        
        return "http://farm{$photo_info->farm}.static.flickr.com/{$photo_info->server}/{$photo_info->id}_{$photo_info->secret}_s.jpg";
    }

    function flickr_insert_photo($photo) {

        $escaped = array();
    
        foreach($photo as $key => $value) {
            $escaped[$key] = addslashes($value); 
        }

        $query = "INSERT INTO geopoints (user_id, source, source_id, lat, lon, event_time, url, image_url, title, description, posted) values ";
        $query .= "  ({$escaped['user_id']}, 1, '{$escaped['photo_id']}', '{$escaped['latitude']}', '{$escaped['longitude']}', '{$escaped['date']}', '{$escaped['url']}', '{$escaped['image_url']}', ";
        $query .= "'{$escaped['title']}', '{$escaped['description']}', FROM_UNIXTIME('{$escaped['posted']}'))";

        return db_insert($query);
    }

    function flickr_format_pin($result) {
        $image_url = str_replace('_s.jpg', '_m.jpg', $result['image_url']);
        $html = '';
        if($result['title']) {
            $html .= "{$result['title']}";
            $html .= "<br>" . date('F j, Y \a\t h:i:s a', strtotime($result['event_time'])) . '<br>';
        }
        $html .= "<a href=\"{$result['url']}\"><img src=\"{$image_url}\"/></a><br>";
        return $html; 
    }

