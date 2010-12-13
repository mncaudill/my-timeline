<?php

    require_once 'config.php';
    require_once 'db.php';

    function flickr_fetch_photos($nsid) {

        $failed = false;
        $photos = array();
        $curr_page = 1;
        $count = 1;

        $done = false;
        do {
            $rsp = flickr_api_call('flickr.people.getPublicPhotos', array('geo', 1, 'user_id' => $nsid, 'page' => $curr_page)); 
            if($rsp->stat === 'ok') {
                $curr_page++;
                $pages = $rsp->photos->pages;
                
                foreach ($rsp->photos->photo as $photo) {

                    // Get info
                    $photo_id = $photo->id;

                    print "$count. Fetching photo: $photo_id\n";
                    $count++;
                    if ($photo_info = flickr_get_photo_info($photo_id)) {
                        if($photo_info->location->latitude) {
                            $photo = array();
                            $photo['photo_id'] = $photo_id;
                            $photo['title'] = $photo_info->title->_content;
                            $photo['description'] = $photo_info->description->_content;
                            $photo['date'] = $photo_info->dates->taken;
                            $photo['latitude'] = $photo_info->location->latitude;
                            $photo['longitude'] = $photo_info->location->longitude;
                            $photo['image_url'] = flickr_get_image_url($photo_info);
                            $photo['url'] = $photo_info->urls->url[0]->_content;
                            $photo['posted'] = $photo_info->dates->posted;

                            if(!flickr_insert_photo($photo)) {
                                $done = true;        
                                break;
                            }
                        }

                        // Sleep for quarter second
                        usleep(250000);
                    }
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
        $query .= "  (1, 1, '{$escaped['photo_id']}', '{$escaped['latitude']}', '{$escaped['longitude']}', '{$escaped['date']}', '{$escaped['url']}', '{$escaped['image_url']}', ";
        $query .= "'{$escaped['title']}', '{$escaped['description']}', FROM_UNIXTIME('{$escaped['posted']}'))";

        return db_insert($query);
    }

