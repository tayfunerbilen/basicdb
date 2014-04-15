<?php

/**
 * Class BasicDB
 * @author Tayfun Erbilen
 * @web http://www.erbilen.net
 * @mail tayfunerbilen@gmail.com
 * @date 13 Nisan 2014
 */
class BasicDB extends PDO
{

    private $sql;
    private $tableName;
    private $where;
    private $join;
    private $orderBy;
    private $limit;

    private $page;
    private $totalRecord;
    private $pageCount;
    private $paginationLimit;
    private $html;

    public function __construct($host, $dbname, $username, $password, $charset = 'utf8')
    {
        parent::__construct('mysql:host=' . $host . ';dbname=' . $dbname, $username, $password);
        $this->query('SET CHARACTER SET ' . $charset);
        $this->query('SET NAMES ' . $charset);
    }

    /**
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
     * @param $from
     * @return $this
     */
    public function from($from)
    {
        $this->sql = str_replace('*', $from, $this->sql);
        return $this;
    }

    /**
     * @param $column
     * @param $value
     * @param string $mark
     * @param bool $filter
     * @return $this
     */
    public function where($column, $value, $mark = '=')
    {
        $this->where[] = array($column, $value, $mark);
        return $this;
    }

    /**
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
     * @param $columnName
     * @param string $sort
     */
    public function orderby($columnName, $sort = 'ASC')
    {
        $this->orderBy = ' ORDER BY ' . $columnName . ' ' . strtoupper($sort);
        return $this;
    }

    /**
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
     * @param bool $single
     * @return array|mixed
     */
    public function run($single = false)
    {
        if ($this->join) {
            $this->sql .= implode(' ', $this->join);
            $this->join = null;
        }
        if (is_array($this->where) && count($this->where) > 0) {
            $this->sql .= ' WHERE ';
            $where = array();
            foreach ($this->where as $arg) {
                if (!is_numeric($arg[1])) {
                    $arg[1] = '"' . $arg[1] . '"';
                }
                $where[] = '`' . $arg[0] . '` ' . $arg[2] . ' ' . $arg[1];
            }
            $this->sql .= implode(' && ', $where);
            $this->where = null;
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
     * @param $tableName
     * @return $this
     */
    public function insert($tableName)
    {
        $this->sql = 'INSERT INTO ' . $tableName;
        return $this;
    }

    /**
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
        if (is_array($this->where) && count($this->where) > 0) {
            $this->sql .= ' WHERE ';
            $where = array();
            foreach ($this->where as $arg) {
                if (!is_numeric($arg[1])) {
                    $arg[1] = '"' . $arg[1] . '"';
                }
                $where[] = '`' . $arg[0] . '` ' . $arg[2] . ' ' . $arg[1];
            }
            $this->sql .= implode(' && ', $where);
            $this->where = null;
        }
        $query = $this->prepare($this->sql);
        $result = $query->execute($val);
        return $result;
    }

    /**
     * @return string
     */
    public function lastId()
    {
        return $this->lastInsertId();
    }

    /**
     * @param $columnName
     * @return $this
     */
    public function update($columnName)
    {
        $this->sql = 'UPDATE ' . $columnName;
        return $this;
    }

    /**
     * @param $columnName
     * @return $this
     */
    public function delete($columnName)
    {
        $this->sql = 'DELETE FROM ' . $columnName;
        return $this;
    }

    /**
     * @return int
     */
    public function done()
    {
        if (is_array($this->where) && count($this->where) > 0) {
            $this->sql .= ' WHERE ';
            $where = array();
            foreach ($this->where as $arg) {
                if (!is_numeric($arg[1])) {
                    $arg[1] = '"' . $arg[1] . '"';
                }
                $where[] = '`' . $arg[0] . '` ' . $arg[2] . ' ' . $arg[1];
            }
            $this->sql .= implode(' && ', $where);
            $this->where = null;
        }
        $query = $this->exec($this->sql);
        return $query;
    }

    public function total()
    {
        if (is_array($this->where) && count($this->where) > 0) {
            $this->sql .= ' WHERE ';
            $where = array();
            foreach ($this->where as $arg) {
                if (!is_numeric($arg[1])) {
                    $arg[1] = '"' . $arg[1] . '"';
                }
                $where[] = '`' . $arg[0] . '` ' . $arg[2] . ' ' . $arg[1];
            }
            $this->sql .= implode(' && ', $where);
            $this->where = null;
        }
        $query = $this->query($this->sql)->fetch(PDO::FETCH_ASSOC);
        return $query['total'];
    }

    /**
     * @param $totalRecord
     * @param $paginationLimit
     * @param $pageParamName
     * @return array
     */
    public function pagination($totalRecord, $paginationLimit, $pageParamName)
    {
        $this->paginationLimit = $paginationLimit;
        $this->page = isset($_GET[$pageParamName]) ? $_GET[$pageParamName] : 1;
        $this->totalRecord = $totalRecord;
        $this->pageCount = ceil($this->totalRecord / $this->paginationLimit);
        $start = ($this->page * $this->paginationLimit) - $this->paginationLimit;
        return array(
            start => $start,
            limit => $this->paginationLimit
        );
    }

    /**
     * @param $url
     * @return mixed
     */
    public function showPagination($url)
    {
        if ($this->totalRecord > $this->paginationLimit) {
            for ($i = $this->page - 5; $i < $this->page + 5 + 1; $i++) {
                if ($i > 0 && $i <= $this->pageCount) {
                    $this->html .= '<a class="';
                    $this->html .= ($i == $this->page ? 'active' : null);
                    $this->html .= '" href="' . str_replace('[page]', $i, $url) . '">' . $i . '</a>';
                }
            }
            return $this->html;
        }
    }

    /**
     * @return bool
     */
    public function nextPage()
    {
        return ($this->page + 1 < $this->pageCount ? $this->page + 1 : $this->pageCount);
    }

    /**
     * @return bool
     */
    public function prevPage()
    {
        return ($this->page - 1 > 0 ? $this->page - 1 : 1);
    }

}
