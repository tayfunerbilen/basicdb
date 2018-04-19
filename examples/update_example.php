<?php

// class file
require '../src/BasicDB.php';

// connection
$db = new Erbilen\Database\BasicDB('localhost', 'testdb', 'testuser', 'password');

// update
$query = $db->update('users')
            ->where('user_id', 2)
            ->set([
                 'username' => 'another user'
            ]);
   
if ( $query ){
  echo 'update success';
}

?>
