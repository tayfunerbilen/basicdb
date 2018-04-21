<?php

// class file
require '../src/BasicDB.php';

// connection
$db = new Erbilen\Database\BasicDB('localhost', 'testdb', 'testuser', 'password');

// select query
$db->from('table_name')
  ->run();
  
// single select query
$db->from('table_name')
  ->first();

// select
$db->from('table_name')
  ->select('column_name, column_name2')
  ->first();

// join
$db->from('table_name')
  ->join('other_table', '%s.other_table_id = %s.table_id')
  ->run();

// left join
$db->from('table_name')
  ->left_join('other_table', '%s.other_table_id = %s.table_id')
  ->run();

// right join
$db->from('table_name')
  ->right_join('other_table', '%s.other_table_id = %s.table_id')
  ->run();

// orderby
$db->from('table_name')
  ->orderby('column_name', 'ASC')
  ->run();

// groupby
$db->from('table_name')
  ->groupby('column_name')
  ->run();

//random
$db->from('table_name')
  ->rand()
  ->run();

// limit
$db->from('table_name')
  ->limit(0, 20)
  ->run();
  
// with where..
$db->from('table_name')
  ->where('column_name', 'basicdb')
  ->first();
  
$db->from('table_name')
  ->where('column_name', 'basicdb', '!=')
  ->first();
  
$db->from('table_name')
  ->where('column_name', 5, '>')
  ->first();
  
$db->from('table_name')
  ->where('column_name', 5, '<')
  ->first();
  
$db->from('table_name')
  ->like('column_name', 'basicdb')
  ->first();
  
$db->from('table_name')
  ->not_like('column_name', 'basicdb')
  ->first();
  
$db->from('table_name')
  ->in('column_name', [1,2,3,4])
  ->first();
  
$db->from('table_name')
  ->not_in('column_name', [1,2,3,4])
  ->first();
  
$db->from('table_name')
  ->find_in_set('column_name', 'basicdb')
  ->first();
  
$db->from('table_name')
  ->between('column_name', [2000, 2050])
  ->first();
  
$db->select('table_name')
  ->not_between('column_name', [2000, 2050])
  ->first();
  
// delete
$db->delete('table_name')
  ->where('column_name', 'value')
  ->done();
  
// update
$db->update('table_name')
  ->where('column_name', 'value')
  ->set([
    'column_name2' => 'new value'
  ]);
  
// insert
$db->insert('table_name')
  ->set([
    'column_name' => 'value1',
    'column_name2' => 'value2'
  ]);
  
// pagination example
$totalRecord = $db->from('users')
                  ->select('count(user_id) as total')
                  ->total();

$pageLimit = 4;
$pageParam = 'sayfa';
$pagination = $db->pagination($totalRecord, $pageLimit, $pageParam);

$query = $db->from('users')
            ->orderby('user_id', 'DESC')
            ->limit($pagination['start'], $pagination['limit'])
            ->run();

print_r($query);

echo $db->showPagination('http://localhost/test/?'.$pageParam.'=[page]');
