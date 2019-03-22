<?php

/**
 * Class BasicDB
 *
 * @author Tayfun Erbilen
 * @web http://www.erbilen.net
 * @mail tayfunerbilen@gmail.com
 * @web http://www.mtkocak.com
 * @mail mtkocak@gmail.com
 * @date 13 April 2014
 * @update 20 March 2019
 * @author Midori Koçak
 * @update 2 July 2015
 */
class basicdb extends \PDO
{
    private $dbName;
    private $type;
    private $sql;
    private $unionSql;
    private $tableName;
    private $where;
    private $having;
    private $grouped;
    private $group_id;
    private $join;
    private $orderBy;
    private $groupBy;
    private $limit;
    private $page;
    private $totalRecord;
    public $pageCount;
    private $paginationLimit;
    private $html;
    public $debug = false;
    public $paginationItem = '<li class="[active]">
        <a href="[url]">[text]</a>
    </li>';
    public $reference = [
        'NOW()'
    ];

    public function __construct($host, $dbname, $username, $password, $charset = 'utf8')
    {
        try {
            parent::__construct('mysql:host=' . $host . ';dbname=' . $dbname, $username, $password);
            $this->dbName = $dbname;
            $this->query('SET CHARACTER SET ' . $charset);
            $this->query('SET NAMES ' . $charset);
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            $this->showError($e);
        }
    }

    public function from($tableName)
    {
        $this->sql = 'SELECT * FROM ' . $tableName;
        $this->tableName = $tableName;
        return $this;
    }

    public function select($columns)
    {
        $this->sql = str_replace(' * ', ' ' . $columns . ' ', $this->sql);
        return $this;
    }

    public function union()
    {
        $this->type = 'union';
        $this->unionSql = $this->sql;
        return $this;
    }

    public function group(Closure $fn)
    {
        static $group_id = 0;
        $this->grouped = true;
        call_user_func_array($fn, [$this]);
        $this->group_id = ++$group_id;
        $this->grouped = false;
        return $this;
    }

    public function where($column, $value = '', $mark = '=', $logical = '&&')
    {
        $this->where[] = [
            'column' => $column,
            'value' => $value,
            'mark' => $mark,
            'logical' => $logical,
            'grouped' => $this->grouped,
            'group_id' => $this->group_id
        ];
        return $this;
    }

    public function having($column, $value = '', $mark = '=', $logical = '&&')
    {
        $this->having[] = [
            'column' => $column,
            'value' => $value,
            'mark' => $mark,
            'logical' => $logical,
            'grouped' => $this->grouped,
            'group_id' => $this->group_id
        ];
        return $this;
    }

    public function or_where($column, $value, $mark = '=')
    {
        $this->where($column, $value, $mark, '||');
        return $this;
    }

    public function or_having($column, $value, $mark = '=')
    {
        $this->having($column, $value, $mark, '||');
        return $this;
    }

    public function join($targetTable, $joinSql, $joinType = 'inner')
    {
        $this->join[] = ' ' . strtoupper($joinType) . ' JOIN ' . $targetTable . ' ON ' . sprintf($joinSql, $targetTable, $this->tableName);
        return $this;
    }

    public function leftJoin($targetTable, $joinSql)
    {
        $this->join($targetTable, $joinSql, 'left');
        return $this;
    }

    public function rightJoin($targetTable, $joinSql)
    {
        $this->join($targetTable, $joinSql, 'right');
        return $this;
    }

    public function orderBy($columnName, $sort = 'ASC')
    {
        $this->orderBy = ' ORDER BY ' . $columnName . ' ' . $sort;
        return $this;
    }

    public function groupBy($columnName)
    {
        $this->groupBy = ' GROUP BY ' . $columnName;
        return $this;
    }

    public function limit($start, $limit)
    {
        $this->limit = ' LIMIT ' . $start . ',' . $limit;
        return $this;
    }

    public function all()
    {
        try {
            $query = $this->generateQuery();
            $result = $query->fetchAll(parent::FETCH_ASSOC);
            return $result;
        } catch (PDOException $e) {
            $this->showError($e);
        }
    }

    public function first()
    {
        try {
            $query = $this->generateQuery();
            return $query->fetch(parent::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->showError($e);
        }
    }

    public function generateQuery()
    {
        if ($this->join) {
            $this->sql .= implode(' ', $this->join);
            $this->join = null;
        }
        $this->get_where('where');
        if ($this->groupBy) {
            $this->sql .= $this->groupBy;
            $this->groupBy = null;
        }
        $this->get_where('having');
        if ($this->orderBy) {
            $this->sql .= $this->orderBy;
            $this->orderBy = null;
        }
        if ($this->limit) {
            $this->sql .= $this->limit;
            $this->limit = null;
        }
        if ($this->type == 'union') {
            $this->sql = $this->unionSql . ' UNION ALL ' . $this->sql;
        }
        if ($this->debug) {
            echo $this->getSqlString();
        }
        $this->type = '';
        $query = $this->query($this->sql);
        return $query;
    }

    private function get_where($conditionType = 'where')
    {
        if (
        (is_array($this->{$conditionType}) && count($this->{$conditionType}) > 0)
        ) {
            $whereClause = ' ' . ($conditionType == 'having' ? 'HAVING' : 'WHERE') . ' ';
            $arrs = $this->{$conditionType};
            if (is_array($arrs)) {
                foreach ($arrs as $key => $item) {
                    if (
                        $item['grouped'] === true &&
                        (
                            (
                                (isset($arrs[$key - 1]) && $arrs[$key - 1]['grouped'] !== true) ||
                                (isset($arrs[$key - 1]) && $arrs[$key - 1]['group_id'] != $item['group_id'])
                            ) ||
                            (
                                (isset($arrs[$key - 1]) && $arrs[$key - 1]['grouped'] !== true) ||
                                (!isset($arrs[$key - 1]))
                            )
                        )
                    ) {
                        $whereClause .= (isset($arrs[$key - 1]) && $arrs[$key - 1]['grouped'] == true ? ' ' . $item['logical'] : null) . ' (';
                    }
                    switch ($item['mark']) {
                        case 'LIKE':
                            $where = $item['column'] . ' LIKE "%' . $item['value'] . '%"';
                            break;
                        case 'NOT LIKE':
                            $where = $item['column'] . ' NOT LIKE "%' . $item['value'] . '%"';
                            break;
                        case 'BETWEEN':
                            $where = $item['column'] . ' BETWEEN "' . $item['value'][0] . '" AND "' . $item['value'][1] . '"';
                            break;
                        case 'NOT BETWEEN':
                            $where = $item['column'] . ' NOT BETWEEN "' . $item['value'][0] . '" AND "' . $item['value'][1] . '"';
                            break;
                        case 'FIND_IN_SET':
                            $where = 'FIND_IN_SET(' . $item['column'] . ', ' . $item['value'] . ')';
                            break;
                        case 'FIND_IN_SET_REVERSE':
                            $where = 'FIND_IN_SET(' . $item['value'] . ', ' . $item['column'] . ')';
                            break;
                        case 'IN':
                            $where = $item['column'] . ' IN("' . (is_array($item['value']) ? implode('", "', $item['value']) : $item['value']) . '")';
                            break;
                        case 'NOT IN':
                            $where = $item['column'] . ' NOT IN(' . (is_array($item['value']) ? implode(', ', $item['value']) : $item['value']) . ')';
                            break;
                        case 'SOUNDEX':
                            $where = 'SOUNDEX(' . $item['column'] . ') LIKE CONCAT(\'%\', TRIM(TRAILING \'0\' FROM SOUNDEX(\'' . $item['value'] . '\')), \'%\')';
                            break;
                        default:
                            $where = $item['column'] . ' ' . $item['mark'] . ' ' . (preg_grep('/' . trim($item['value']) . '/i', $this->reference) ? $item['value'] : '"' . $item['value'] . '"');
                            break;
                    }
                    if ($key == 0) {
                        if (
                            $item['grouped'] == false &&
                            isset($arrs[$key + 1]['grouped']) == true
                        ) {
                            $whereClause .= $where . ' ' . $item['logical'];
                        } else {
                            $whereClause .= $where;
                        }
                    } else {
                        $whereClause .= ' ' . $item['logical'] . ' ' . $where;
                    }
                    if (
                        $item['grouped'] === true &&
                        (
                            (
                                (isset($arrs[$key + 1]) && $arrs[$key + 1]['grouped'] !== true) ||
                                ($item['grouped'] === true && !isset($arrs[$key + 1]))
                            )
                            ||
                            (
                                (isset($arrs[$key + 1]) && $arrs[$key + 1]['group_id'] != $item['group_id']) ||
                                ($item['grouped'] === true && !isset($arrs[$key + 1]))
                            )
                        )
                    ) {
                        $whereClause .= ' )';
                    }
                }
            }
            $whereClause = rtrim($whereClause, '||');
            $whereClause = rtrim($whereClause, '&&');
            $whereClause = preg_replace('/\(\s+(\|\||&&)/', '(', $whereClause);
            $whereClause = preg_replace('/(\|\||&&)\s+\)/', ')', $whereClause);
            $this->sql .= $whereClause;
            $this->unionSql .= $whereClause;
            $this->{$conditionType} = null;
        }
    }

    public function insert($tableName)
    {
        $this->sql = 'INSERT INTO ' . $tableName;
        return $this;
    }

    public function set($data, $value = null)
    {
        try {
            if ($value) {
                if (strstr($value, '+')) {
                    $this->sql .= ' SET ' . $data . ' = ' . $data . ' ' . $value;
                    $executeValue = null;
                } elseif (strstr($value, '-')) {
                    $this->sql .= ' SET ' . $data . ' = ' . $data . ' ' . $value;
                    $executeValue = null;
                } else {
                    $this->sql .= ' SET ' . $data . ' = :' . $data . '';
                    $executeValue = [
                        $data => $value
                    ];
                }
            } else {

                $this->sql .= ' SET ' . implode(', ', array_map(function ($item) {
                        return $item . ' = :' . $item;
                    }, array_keys($data)));
                $executeValue = $data;
            }
            $this->get_where('where');
            $this->get_where('having');
            $query = $this->prepare($this->sql);
            $result = $query->execute($executeValue);
            return $result;
        } catch (PDOException $e) {
            $this->showError($e);
        }
    }

    public function lastId()
    {
        return $this->lastInsertId();
    }

    public function update($tableName)
    {
        $this->sql = 'UPDATE ' . $tableName;
        return $this;
    }

    public function delete($tableName)
    {
        $this->sql = 'DELETE FROM ' . $tableName;
        return $this;
    }

    public function done()
    {
        try {
            $this->get_where('where');
            $this->get_where('having');
            $query = $this->exec($this->sql);
            return $query;
        } catch (PDOException $e) {
            $this->showError($e);
        }
    }

    public function total()
    {
        if ($this->join) {
            $this->sql .= implode(' ', $this->join);
            $this->join = null;
        }
        $this->get_where('where');
        if ($this->groupBy) {
            $this->sql .= $this->groupBy;
            $this->groupBy = null;
        }
        $this->get_where('having');
        if ($this->orderBy) {
            $this->sql .= $this->orderBy;
            $this->orderBy = null;
        }
        if ($this->limit) {
            $this->sql .= $this->limit;
            $this->limit = null;
        }
        $query = $this->query($this->sql)->fetch(parent::FETCH_ASSOC);
        return $query['total'];
    }

    public function pagination($totalRecord, $paginationLimit, $pageParamName)
    {
        $this->paginationLimit = $paginationLimit;
        $this->page = isset($_GET[$pageParamName]) && is_numeric($_GET[$pageParamName]) ? $_GET[$pageParamName] : 1;
        $this->totalRecord = $totalRecord;
        $this->pageCount = ceil($this->totalRecord / $this->paginationLimit);
        $start = ($this->page * $this->paginationLimit) - $this->paginationLimit;
        return [
            'start' => $start,
            'limit' => $this->paginationLimit
        ];
    }

    public function showPagination($url, $class = 'active')
    {
        if ($this->totalRecord > $this->paginationLimit) {
            for ($i = $this->page - 5; $i < $this->page + 5 + 1; $i++) {
                if ($i > 0 && $i <= $this->pageCount) {
                    $this->html .= str_replace(
                        ['[active]', '[text]', '[url]'],
                        [($i == $this->page ? $class : null), $i, str_replace('[page]', $i, $url)],
                        $this->paginationItem
                    );
                }
            }
            return $this->html;
        }
    }

    public function nextPage()
    {
        return ($this->page + 1 < $this->pageCount ? $this->page + 1 : $this->pageCount);
    }

    public function prevPage()
    {
        return ($this->page - 1 > 0 ? $this->page - 1 : 1);
    }

    public function getSqlString()
    {
        $this->get_where('where');
        $this->get_where('having');
        return $this->errorTemplate($this->sql, __CLASS__ . ' SQL Sorgusu');
    }

    public function between($column, $values = [])
    {
        $this->where($column, $values, 'BETWEEN');
        return $this;
    }

    public function notBetween($column, $values = [])
    {
        $this->where($column, $values, 'NOT BETWEEN');
        return $this;
    }

    public function findInSet($column, $value)
    {
        $this->where($column, $value, 'FIND_IN_SET');
        return $this;
    }

    public function findInSetReverse($column, $value)
    {
        $this->where($column, $value, 'FIND_IN_SET_REVERSE');
        return $this;
    }

    public function in($column, $value)
    {
        $this->where($column, $value, 'IN');
        return $this;
    }

    public function notIn($column, $value)
    {
        $this->where($column, $value, 'NOT IN');
        return $this;
    }

    public function like($column, $value)
    {
        $this->where($column, $value, 'LIKE');
        return $this;
    }

    public function notLike($column, $value)
    {
        $this->where($column, $value, 'NOT LIKE');
        return $this;
    }

    public function soundex($column, $value)
    {
        $this->where($column, $value, 'SOUNDEX');
        return $this;
    }

    public function __call($name, $args)
    {
        die($name . '  metodu ' . __CLASS__ . ' sınıfı içinde bulunamadı.');
    }

    private function showError(PDOException $error)
    {
        $this->errorTemplate($error->getMessage());
    }

    private function errorTemplate($errorMsg, $title = null)
    {
        ?>
        <div class="db-error-msg-content">
            <div class="db-error-title">
                <?= $title ? $title : __CLASS__ . ' Hatası:' ?>
            </div>
            <div class="db-error-msg"><?= $errorMsg ?></div>
        </div>
        <style>
            .db-error-msg-content {
                padding: 15px;
                border-left: 5px solid #c00000;
                background: rgba(192, 0, 0, 0.06);
                background: #f8f8f8;
                margin-bottom: 10px;
            }

            .db-error-title {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                font-size: 16px;
                font-weight: 500;
            }

            .db-error-msg {
                margin-top: 15px;
                font-size: 14px;
                font-family: Consolas, Monaco, Menlo, Lucida Console, Liberation Mono, DejaVu Sans Mono, Bitstream Vera Sans Mono, Courier New, monospace, sans-serif;
                color: #c00000;
            }
        </style>
        <?php
    }

    /**
     * Belirtilen tabloyu temizler
     *
     * @param $tableName
     * @return bool|PDOStatement
     */
    public function truncate($tableName)
    {
        return $this->query('TRUNCATE TABLE ' . $this->dbName . '.' . $tableName);
    }

    /**
     * Tüm tabloları temizler
     *
     * @param array $dbs
     * @return mixed
     */
    public function truncateAll($dbs = [])
    {
        if (count($dbs) == 0) $dbs[] = $this->dbName;
        $query = $this->from('INFORMATION_SCHEMA.TABLES')
            ->select('CONCAT("TRUNCATE TABLE `", table_schema, "`.`", TABLE_NAME, "`;") as query, TABLE_NAME as tableName')
            ->in('table_schema', implode(',', $dbs))
            ->all();
        $this->query('SET FOREIGN_KEY_CHECKS=0;')->fetch();
        foreach ($query as $row) {
            $this->setAutoIncrement($row['tableName']);
            $this->query($row['query'])->fetch();
        }
        $this->query('SET FOREIGN_KEY_CHECKS=1;')->fetch();
    }

    /**
     * Belirtilen tablonun auto_increment değerini ayarlar
     *
     * @param $tableName
     * @return mixed
     */
    public function setAutoIncrement($tableName, $ai = 1)
    {
        return $this->query("ALTER TABLE `{$tableName}` AUTO_INCREMENT = {$ai}")->fetch();
    }

}
