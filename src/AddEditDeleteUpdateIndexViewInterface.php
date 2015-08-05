<?php
namespace Erbilen\Database;

interface AddEditDeleteUpdateIndexViewInterface
{

    public function add($data);

    public function edit($id, $data);

    public function index();

    public function view($id);

    public function delete($id);
}