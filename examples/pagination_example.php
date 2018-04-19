<?php

// class file
require '../src/BasicDB.php';

// connection
$db = new Erbilen\Database\BasicDB('localhost', 'testdb', 'testuser', 'password');

// pagination example
$totalRecord = $db->from('users')
                  ->select('count(user_id) as total')
                  ->total();

$pageLimit = 4;
$pageParam = 'page';
$pagination = $db->pagination($totalRecord, $pageLimit, $pageParam);

$query = $db->from('users')
            ->orderby('user_id', 'DESC')
            ->limit($pagination['start'], $pagination['limit'])
            ->all();

print_r($query);

echo $db->showPagination('http://localhost/test/?'.$pageParam.'=[page]');

?>
