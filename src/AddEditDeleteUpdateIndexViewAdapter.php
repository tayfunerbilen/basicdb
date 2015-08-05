<?php
namespace Erbilen\Database;

abstract class AddEditDeleteUpdateIndexView extends CrudableAdapter implements AddEditDeleteUpdateIndexViewInterface
{

    public $tableName;
    
    public function __construct($connection, $tableName){
        parent::__construct($connection);
        $this->tableName = $tableName;
    }

    public function edit($id, $data)
    {
        parent::update($id, $this->tableName, $data);
    }

    public function index(){
        $query = $this->basicDB->select($this->tableName)
        ->run();
        
        if ($query) {
            foreach ($query as $row) {
                yield $row;
            }
        }
    }

    public function view($id)
    {
        parent::read($id, $this->tableName);
    }

    public function add($data)
    {
        parent::create($data, $this->tableName);
    }

    public function delete($id){
        parent::delete($id, $this->tableName);
    }
}