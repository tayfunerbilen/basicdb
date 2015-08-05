<?php
namespace Erbilen\Database;

abstract class CrudableAdapter implements CrudableInterface
{
    private $basicDB;
    
    public function __construct(BasicDB $connection){
        $this->basicDB = $connection;
    }

    public function create($data,$tableName){
        
        $query = $this->basicDB->insert($tableName)->set($data);
        
        return $this->basicDB->lastId();
        
    }

    public function read($id, $tableName){
        $query = $this->basicDB->select($tableName)
        ->where('id', $id)
        ->run();
        
        if ($query) {
            foreach ($query as $row) {
                return ($row);
            }
        }
        
    }

    public function update($id, $tableName ,$data){
        // update
        $query = $this->basicDB->update($tableName)
        ->where('id', $id)
        ->set($data);
        
        if ($query) {
            return true;
        }
    }

    public function delete($id,$tableName){
        // delete
        $query = $this->basicDB->delete($tableName)
        ->where('id', $id)
        ->done();
        
        if ($query) {
            return true;
        }
    }
}