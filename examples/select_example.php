<?php

// class file
require 'BasicDB.php';

// connection
$db = new BasicDB('localhost', 'testdb', 'testuser', 'password');

// select
$query = $db->from('post')
            ->orderby('post_id', 'desc')
            ->limit(0, 10)
            ->run();
   
if ( $query ){
  foreach ( $query as $row ){
    print_r($row);
  }
}

?>
