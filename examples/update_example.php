<?php

// class file
require 'BasicDB.php';

// connection
$db = new BasicDB('localhost', 'testdb', 'testuser', 'password');

// update
$query = $db->update('users')
            ->where('user_id', 2)
            ->set(array(
                 username => 'another user'
            ));
   
if ( $query ){
  echo 'update success';
}

?>
