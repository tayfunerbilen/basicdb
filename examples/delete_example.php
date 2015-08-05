<?php

// class file
require 'BasicDB.php';

// connection
$db = new BasicDB('localhost', 'testdb', 'testuser', 'password');

// delete
$query = $db->delete('users')
            ->where('user_id', 2)
            ->done();
   
if ( $query ){
  echo 'user deleted';
}

?>
