<?php

// class file
require 'BasicDB.php';

// connection
$db = new BasicDB('localhost', 'testdb', 'testuser', 'password');

// pagination example
$totalRecord = $db->select('users')
                  ->from('count(user_id) as total')
                  ->total();

$pageLimit = 4;
$pageParam = 'page';
$pagination = $db->pagination($totalRecord, $pageLimit, $pageParam);

$query = $db->select('users')
            ->orderby('user_id', 'DESC')
            ->limit($pagination['start'], $pagination['limit'])
            ->run();

print_r($query);

echo $db->showPagination('http://localhost/test/?'.$pageParam.'=[page]');

?>
