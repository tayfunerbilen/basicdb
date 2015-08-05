<?php
use Erbilen\Database\BasicDB;

class BasicDBTest extends PHPUnit_Extensions_Database_TestCase
{

    protected $basicDB;

    /**
     * only instantiate pdo once for test clean-up/fixture load
     *
     * @var PDO
     */
    private static $pdo = null;

    /**
     * only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
     *
     * @var type
     */
    private $conn = null;

    public function setup()
    {
        $this->basicDB = new BasicDB('localhost', 'test', 'root', 'midori');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {}

    protected function getDataSet()
    {
        return $this->createMySQLXMLDataSet(__DIR__ . '/fixtures/basicdb.xml');
    }

    protected function getConnection()
    {
        if ($this->conn === null) {
            if (self::$pdo == null) {
                self::$pdo = new PDO('mysql:host=localhost;dbname=test', 'root', 'midori');
            }
            $this->conn = $this->createDefaultDBConnection(self::$pdo, 'testing');
        }
        return $this->conn;
    }

    public function testDataBaseConnection()
    {
        $this->getConnection()->createDataSet(array(
            'users'
        ));
        $user = $this->getDataSet();
        $queryTable = $this->getConnection()->createQueryTable('users', 'SELECT username,password,email FROM users');
        $expectedTable = $this->getDataSet()->getTable('users');
        // Here we check that the table in the database matches the data in the XML file
        $this->assertTablesEqual($expectedTable, $queryTable);
    }

    public function insert()
    {
        $query = $this->basicDB->insert('users')->set(array(
            'username' => 'test user',
            'password' => 123456,
            'email' => 'test@mail.com'
        ));
        $this->assertTrue($query);
        /*
         * if ($query) {
         * echo 'Last Insert Id: ' . $this->basicDB->lastId();
         * }
         */
        
        $lastId = $this->basicDB->lastId();
        $this->assertNotEmpty($lastId);
        return $lastId;
    }

    public function testSelect()
    {
        $query = $this->basicDB->select('users')->run();
        
        $this->assertNotEmpty($query);
    }

    public function testPagination()
    {
        // pagination example
        $totalRecord = $this->basicDB->select('users')
            ->from('count(id) as total')
            ->total();
        
        $pageLimit = 4;
        $pageParam = 'page';
        $pagination = $this->basicDB->pagination($totalRecord, $pageLimit, $pageParam);
        
        $query = $this->basicDB->select('users')
            ->orderby('id', 'DESC')
            ->limit($pagination['start'], $pagination['limit'])
            ->run();
        
        $this->assertNotEmpty($query);
    }

    public function testUpdate()
    {
        // update
        $query = $this->basicDB->update('users')
            ->where('id', 3)
            ->set(array(
            'username' => 'another user'
        ));
        $this->assertTrue($query);
    }

    public function testDelete()
    {
        $lastId = $this->insert();
        // delete
        $query = $this->basicDB->delete('users')
            ->where('id', $lastId)
            ->done();
        
        $this->assertNotEmpty($query);
    }
}
