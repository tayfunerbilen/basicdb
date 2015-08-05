<?php

// class file
require 'BasicDB.php';

// connection
$db = new BasicDB('localhost', 'testdb', 'testuser', 'password');

// select
$query = $db->select('post')
            ->join('users', '%s.user_id = %s.post_userid', 'left')
            ->where('post_approval', 1)
            ->or_where('post_approval', 2)
            ->orderby('post_id', 'desc')
            ->groupby('post_user')
            ->limit(0, 10)
            ->run();
   
if ( $query ){
  foreach ( $query as $row ){
    print_r($row);
  }
}

?>
