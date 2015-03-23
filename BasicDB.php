<?php

/**
 * Class BasicDB
 * @author Tayfun Erbilen
 * @web http://www.erbilen.net
 * @mail tayfunerbilen@gmail.com
 * @date 13 Nisan 2014
 * @update 17 Kasım 2014
 */
class basicdb extends PDO
{

    /**
     * sql sorgusunun tutulduğu değişken
     *
     * @var
     */
    private $sql;

    /**
     * tab adının tutulduğu değişken
     *
     * @var
     */
    private $tableName;

    /**
     * koşulların tutulduğu değişken
     *
     * @var
     */
    private $where;

    /**
     * join kurallarının tutulduğu değişken
     *
     * @var
     */
    private $join;

    /**
     * orderby değerinin tutulduğu değişken
     *
     * @var
     */
    private $orderBy;

    /**
     * groupby değerinin tutulduğu değişken
     *
     * @var
     */
    private $groupBy;

    /**
     * limit değerinin tutulduğu değişken
     *
     * @var
     */
    private $limit;

    /**
     * sayfa $_GET[] parametresinin tutulduğu değişken
     *
     * @var
     */
    private $page;

    /**
     * Toplam sütun sayısının tutulduğu değişken
     *
     * @var
     */
    private $totalRecord;

    /**
     * Toplam sayfa sayısının tutulduğu değişken
     *
     * @var
     */
    private $pageCount;

    /**
     * Sayfa limitinin tutulduğu değişken
     *
     * @var
     */
    private $paginationLimit;

    /**
     * Sayfalama kodlarının tutulduğu değişken
     *
     * @var
     */
    private $html;



    /**
     * BasicDB kurucu metodu
     *
     * @param $host
     * @param $dbname
     * @param $username
     * @param $password
     * @param string $charset
     */
    public function __construct($host, $dbname, $username, $password, $charset = 'utf8')
    {
        parent::__construct('mysql:host=' . $host . ';dbname=' . $dbname, $username, $password);
        $this->query('SET CHARACTER SET ' . $charset);
        $this->query('SET NAMES ' . $charset);
    }

    /**
     * Sql sorguda tablo seçme işlemi belirlenir.
     *
     * @param $tableName
     * @return $this
     */
    public function select($tableName)
    {
        $this->sql = 'SELECT * FROM `' . $tableName . '`';
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * Sql sorguda kolon seçme işlemi belirlenir.
     *
     * @param $from
     * @return $this
     */
    public function from($from)
    {
        $this->sql = str_replace('*', $from, $this->sql);
        return $this;
    }

    /**
     * Sql sorguda -where- işlemini belirler.
     *
     * @param $column
     * @param $value
     * @param string $mark
     * @param bool $filter
     * @return $this
     */
    public function where($column, $value = '', $mark = '=', $logical = '&&')
    {
        $this->where[] = array($column, $value, $mark, $logical);
        return $this;
    }

    /**
     * Sql sorguda -or where- işlemini belirler.
     *
     * @param $column
     * @param $value
     * @param $mark
     * @return $this
     */
    public function or_where($column, $value, $mark = '='){
        $this->where($column, $value, $mark, '||');
        return $this;
    }

    /**
     * Sql sorguda -join- işlemini belirler.
     *
     * @param $targetTable
     * @param $joinSql
     * @param string $joinType
     * @return $this
     */
    public function join($targetTable, $joinSql, $joinType = 'inner')
    {
        $this->join[] = ' ' . strtoupper($joinType) . ' JOIN ' . $targetTable . ' ON ' . sprintf($joinSql, $targetTable, $this->tableName);
        return $this;
    }

    /**
     * Sql sorguda -orderby- işlemini belirler.
     *
     * @param $columnName
     * @param string $sort
     */
    public function orderby($columnName, $sort = 'ASC')
    {
        $this->orderBy = ' ORDER BY ' . $columnName . ' ' . strtoupper($sort);
        return $this;
    }

    /**
     * Sql sorguda -groupby- işlemini belirler.
     *
     * @param $columnName
     * @return $this
     */
    public function groupby($columnName)
    {
        $this->groupBy = ' GROUP BY ' . $columnName;
        return $this;
    }

    /**
     * Sql sorguda -limit- işlemini belirler.
     *
     * @param $start
     * @param $limit
     * @return $this
     */
    public function limit($start, $limit)
    {
        $this->limit = ' LIMIT ' . $start . ',' . $limit;
        return $this;
    }

    /**
     * Insert/Update/Select işlemlerini çalıştırmak için kullanılır.
     *
     * @param bool $single
     * @return array|mixed
     */
    public function run($single = false)
    {
        if ($this->join) {
            $this->sql .= implode(' ', $this->join);
            $this->join = null;
        }
        $this->get_where();
        if ($this->groupBy) {
            $this->sql .= $this->groupBy;
            $this->groupBy = null;
        }
        if ($this->orderBy) {
            $this->sql .= $this->orderBy;
            $this->orderBy = null;
        }
        if ($this->limit) {
            $this->sql .= $this->limit;
            $this->limit = null;
        }

        $query = $this->query($this->sql);

        if ($single)
            return $query->fetch(PDO::FETCH_ASSOC);
        else
            return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Sorgu çalıştırma metodlarında where işlemini yerine getirir.
     */
    private function get_where()
    {
        if (is_array($this->where) && count($this->where) > 0) {
            $this->sql .= ' WHERE ';
            $where = array();
            foreach ($this->where as $key => $arg) {
                if ( strstr($arg[1], 'FIND_IN_SET') ){
                    $where[] = ( $key > 0 ? $arg[3] : null ) . $arg[1];
                } else {
                    $where[] = ( $key > 0 ? $arg[3] : null ) . ' `' . $arg[0] . '` ' . strtoupper($arg[2]) . ' ' . (strstr($arg[2], 'IN') ? '(' : '"') . $arg[1] . (strstr($arg[2], 'IN') ? ')' : '"');
                }
            }
            $this->sql .= implode(' ', $where);
            $this->where = null;
        }
    }

    /**
     * Insert işlemi için kullanılır.
     *
     * @param $tableName
     * @return $this
     */
    public function insert($tableName)
    {
        $this->sql = 'INSERT INTO ' . $tableName;
        return $this;
    }

    /**
     * Insert işlemi için veri yüklemede kullanılır.
     *
     * @param $columns
     * @return bool
     */
    public function set($columns)
    {
        $val = array();
        $col = array();
        foreach ($columns as $column => $value) {
            $val[] = $value;
            $col[] = $column . ' = ? ';
        }
        $this->sql .= ' SET ' . implode(', ', $col);
        $this->get_where();
        $query = $this->prepare($this->sql);
        $result = $query->execute($val);
        return $result;
    }

    /**
     * Son eklenen id yi geriye döndürür.
     *
     * @return string
     */
    public function lastId()
    {
        return $this->lastInsertId();
    }

    /**
     * Güncelleme işlemi için kullanılır.
     *
     * @param $columnName
     * @return $this
     */
    public function update($columnName)
    {
        $this->sql = 'UPDATE ' . $columnName;
        return $this;
    }

    /**
     * Silme işlemi için kullanılır.
     *
     * @param $columnName
     * @return $this
     */
    public function delete($columnName)
    {
        $this->sql = 'DELETE FROM ' . $columnName;
        return $this;
    }

    /**
     * Silme işlemini tamamlamak için kullanılır.
     *
     * @return int
     */
    public function done()
    {
        $this->get_where();
        $query = $this->exec($this->sql);
        return $query;
    }

    /**
     * Toplam sonucu -total- sütun adıyla geriye döndürür.
     *
     * @return mixed
     */
    public function total()
    {
        if ($this->join) {
            $this->sql .= implode(' ', $this->join);
            $this->join = null;
        }
        $this->get_where();
        if ($this->orderBy) {
            $this->sql .= $this->orderBy;
            $this->orderBy = null;
        }
        if ($this->groupBy) {
            $this->sql .= $this->groupBy;
            $this->groupBy = null;
        }
        if ($this->limit) {
            $this->sql .= $this->limit;
            $this->limit = null;
        }
        $query = $this->query($this->sql)->fetch(PDO::FETCH_ASSOC);
        return $query['total'];
    }

    /**
     * Sayfalama işlemine ait start ve limit değerlerini geriye döndürür.
     *
     * @param $totalRecord
     * @param $paginationLimit
     * @param $pageParamName
     * @return array
     */
    public function pagination($totalRecord, $paginationLimit, $pageParamName)
    {
        $this->paginationLimit = $paginationLimit;
        $this->page = isset($_GET[$pageParamName]) && is_numeric($_GET[$pageParamName]) ? $_GET[$pageParamName] : 1;
        $this->totalRecord = $totalRecord;
        $this->pageCount = ceil($this->totalRecord / $this->paginationLimit);
        $start = ($this->page * $this->paginationLimit) - $this->paginationLimit;
        return array(
            'start' => $start,
            'limit' => $this->paginationLimit
        );
    }

    /**
     * Sayfalama işlemini geriye döndürür.
     *
     * @param $url
     * @return mixed
     */
    public function showPagination($url, $class = 'active')
    {
        if ($this->totalRecord > $this->paginationLimit) {
            for ($i = $this->page - 5; $i < $this->page + 5 + 1; $i++) {
                if ($i > 0 && $i <= $this->pageCount) {
                    $this->html .= '<li class="';
                    $this->html .= ($i == $this->page ? $class : null);
                    $this->html .= '"><a href="' . str_replace('[page]', $i, $url) . '">' . $i . '</a>';
                }
            }
            return $this->html;
        }
    }

    /**
     * Sayfalama işleminde bir sonraki sayfayı geriye döndürür.
     *
     * @return bool
     */
    public function nextPage()
    {
        return ($this->page + 1 < $this->pageCount ? $this->page + 1 : $this->pageCount);
    }

    /**
     * Sayfalama işleminde bir önceki sayfayı geriye döndürür.
     *
     * @return bool
     */
    public function prevPage()
    {
        return ($this->page - 1 > 0 ? $this->page - 1 : 1);
    }

    /**
     * SQL sorgusunu string olarak geriye döndürür.
     *
     * @return mixed
     */
    public function getSqlString()
    {
        return $this->sql;
    }

}
