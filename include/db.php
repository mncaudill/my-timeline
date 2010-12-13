<?php


    // Assumes properly escaped strings
    function db_connect() {
        global $db;

        $dbh = mysql_connect($db['host'], $db['username'], $db['password']);

        if(!$dbh) {
            die("Couldn't connect to DB: " . mysql_error());
        }

        mysql_select_db($db['name']);
        mysql_set_charset('utf8', $dbh);
        return $dbh;
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
        return mysql_query($query, $db);
    }
