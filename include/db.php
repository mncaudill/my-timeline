<?php


    // Assumes properly escaped strings
    function db_connect() {
        $db = mysql_connect('localhost', 'root', '');

        if(!$db) {
            die("Couldn't connect to DB: " . mysql_error());
        }

        mysql_select_db('mysanfrancisco');
        mysql_set_charset('utf8', $db);
        return $db;
    }

    function db_query($query) {
        $db = db_connect();    

        $results = array();
        if ($result = mysql_query($query, $db)) {
            while($row = mysql_fetch_assoc($result)) {
                $results[] = $row;
            }
            return $results;
        } else {
            return false;
        }
    }

    function db_insert($query) {

        $db = db_connect();

        if($res = mysql_query($query, $db)) {
            return true;
        } else {
            print mysql_error() . "\n";
        }
    }
