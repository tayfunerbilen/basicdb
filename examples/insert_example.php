<?php

// class file
require 'BasicDB.php';

// connection
$db = new BasicDB('localhost', 'testdb', 'testuser', 'password');

// insert
$query = $db->insert('users')
            ->set(array(
                 username => 'test user',
                 password => 123456,
                 email => 'test@mail.com'
            ));
   
if ( $query ){
  echo 'Last Insert Id: '.$db->lastId();
}

?>
