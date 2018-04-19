<?php

// class file
require '../src/BasicDB.php';

// connection
$db = new Erbilen\Database\BasicDB('localhost', 'testdb', 'testuser', 'password');

// select
$query = $db->from('post')
            ->orderby('post_id', 'desc')
            ->limit(0, 10)
            ->all();
   
if ( $query ){
  foreach ( $query as $row ){
    print_r($row);
  }
}

?>
