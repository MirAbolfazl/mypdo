<?php

class mysqlfun
{
    public $obj = null;
    public $function = '';
    public $value = '';
    function __construct(
        $fun,
        $value
    ) {
        $this->function = $fun;
        $this->value = implode(", ", $value);
    }
    function get_string($mypdo)
    {
        return $this->function . '(' . $this->value . ')';
    }
}
class defwhere
{
    public $obj = null;
    function __construct()
    {
        $this->obj = [];
    }
    function give() {}
}
class defmypdo
{
    public $mypdo = null;
    public $limit = null;
    function __construct($mypdo)
    {
        $this->mypdo = $mypdo;
    }
    function __call($method, $args)
    {
        if (isset($this->$method)) return call_user_func_array($this->$method, $args);
        if (method_exists($this->mypdo, $method)) return call_user_func_array([$this->mypdo, $method], $args);
    }
    function __get($name)
    {
        if (isset($this->$name)) return $this->$name;
        if (isset($this->mypdo->$name)) return $this->mypdo->$name;
    }
}
class wherepdo
{
    public $obj = null;
    public $mypdo = null;
    public $id = null;
    public $give = null;
    public $c = null;

    function __construct($mypdo, $vl1, $vl2, $c = '=', $id = 0, $give = null, $cc = 0)
    {
        $this->obj = [];
        $this->mypdo = $mypdo;
        $this->id = $id;
        $this->give = $give;
        $this->c = $cc;
        $this->set((object)[$vl1, $c, $vl2]);
    }
    function __call($method, $args)
    {
        if (method_exists($this->mypdo, $method)) return call_user_func_array([$this->mypdo, $method], $args);
    }
    function and($vl1, $vl2, $c = '=')
    {
        $this->set(['AND' => (object)[$vl1, $c, $vl2]]);
        return $this;
    }
    function or($vl1, $vl2, $c = '=')
    {
        $this->set(['OR' => (object)[$vl1, $c, $vl2]]);
        return $this;
    }
    function andwhere($vl1, $vl2, $c = '=')
    {
        $this->obj[] = [];
        $where = new wherepdo($this, $vl1, $vl2, $c, count($this->obj) - 1, $this, 'AND');
        $this->give();
        return $where;
    }
    function orwhere($vl1, $vl2, $c = '=')
    {
        $this->obj[] = [];
        $where = new wherepdo($this, $vl1, $vl2, $c, count($this->obj) - 1, $this, 'OR');
        $this->give();
        return $where;
    }
    function give()
    {
        $this->give->obj[$this->id][$this->c] = $this->obj;
        $this->give->give();
    }
    function set($ar)
    {
        $this->obj[] = $ar;
        $this->give();
    }
} //end where class
class mypdo
{
    public $pdo = null;
    public $lastInsertId = null;
    public $where = null;
    public $as = null;
    public $join = null;
    public $having = null;
    public $limit = null;
    public $orderby = null;
    public $groupby = null;
    public $orderbylimit = null;
    public $groupbylimit = null;
    public $bind = null;
    public $bindValue = null;
    public $bindid = null;
    public $debug = null;
    function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->lastInsertId = '';
        $this->mynull();
    }
    function __call($method, $args)
    {
        $mysqlfun = new mysqlfun($method, $args);
        return $mysqlfun;
    }
    function mynull()
    {
        $this->where = new defwhere();
        $this->as = '';
        $this->join = '';
        $this->having = '';
        $this->limit = '';
        $this->orderby = '';
        $this->groupby = '';
        $this->orderbylimit = '';
        $this->groupbylimit = '';
        $this->bind = true;
        $this->bindValue = [];
        $this->bindid = 0;
        $this->debug = false;
    }
    function debug($v = true)
    {
        $this->debug = $v;
        return $this;
    }
    function query($query)
    {
        $this->mynull();

        $q = $this->pdo->query($query);


        $this->lastInsertId = $this->pdo->lastInsertId();
        return $q;
    }
    function wherecreate($array)
    {
        $value = $array[0];
        $where = $this->column($value->{0}) . ' ' . $value->{1} . ' ' . $this->bind($value->{2});
        unset($array[0]);
        foreach ($array as $ar) {
            foreach ($ar as $key => $value) {
                if (is_object($value)) {
                    $where .= ' ' . $key . ' ' . $this->column($value->{0}) . ' ' . $value->{1} . ' ' . $this->bind($value->{2});
                } else {
                    $where .= ' ' . $key . ' (' . $this->wherecreate($value) . ')';
                }
            }
        }
        return $where;
    }
    function wherebyarray($ars)
    {
        $where = '';
        foreach ($ars as $key => $ar) {
            if ($key === 'WHERE' || $key === 'AND' || $key === 'OR') {
                if (count($ar) != 1) {
                    $where .= ' ' . $key . ' (' . $this->wherecreate($ar) . ')';
                } else {
                    $where .= ' ' . $key . ' ' . $this->wherecreate($ar);
                }
            } else {
                $where .= $this->wherebyarray($ar);
            }
        }
        return $where;
    }

    function lastInsertId()
    {
        return $this->lastInsertId;
    }
    function myexecute($query)
    {
        $query .= $this->join;
        $query .= $this->wherebyarray($this->where->obj);
        $query .= $this->groupby;
        $query .= $this->groupbylimit;
        $query .= $this->orderby;
        $query .= $this->orderbylimit;
        $query .= $this->having;
        $query .= $this->limit;
        if (!$this->debug) return $this->query($query);
        return $query;
    }
    function where($vl1, $vl2, $c = '=')
    {
        return new wherepdo($this, $vl1, $vl2, $c, count($this->where->obj), $this->where, 'WHERE');
    }
    function andwhere($vl1, $vl2, $c = '=')
    {
        return new wherepdo($this, $vl1, $vl2, $c, count($this->where->obj), $this->where, 'AND');
    }
    function orwhere($vl1, $vl2, $c = '=')
    {
        return new wherepdo($this, $vl1, $vl2, $c, count($this->where->obj), $this->where, 'OR');
    }
    function bind($value, &$type = null)
    {
        $type = '=';
        if (!is_array($value) && !is_object($value)) {
            if (is_string($value)) {
                $type = 'LIKE';
                return $this->pdo->quote($value);
            } elseif (is_int($value) || is_float($value)) {
                return $value;
            } elseif (is_bool($value)) {
                return (int)$value;
            } elseif (is_null($value)) {
                $type = 'IS';
                return 'NULL';
            }
            $i = ':bind' . $this->bindid;
            $this->bindValue[$i] = $value;
            $this->bindid = $this->bindid + 1;
            return $i;
        }
        if (is_object($value) && get_class($value) == 'mysqlfun') {
            $mypdo = $this;
            return $value->get_string($mypdo);
        }
        return $value['value'] ?? '';
    }
    function nobind($value)
    {
        return ['value' => $value];
    }
    function update($table, $ar = [])
    {
        $update = '';
        foreach ($ar as $key => $vle) {
            $update .= ', ' . $this->column($key) . '=' . $this->bind($vle);
        }
        $sth = $this->myexecute('UPDATE ' . $this->column($table) . ' SET ' . mb_substr($update, 2));
        return $sth;
    }
    function replace($table, $ar = [])
    {
        $update = '';
        foreach ($ar as $key => $vle) {
            $update .= ', ' . $this->column($key) . '=' . $this->bind($vle);
        }
        $sth = $this->myexecute('REPLACE ' . $this->column($table) . ' SET ' . mb_substr($update, 2));
        return $sth;
    }
    function delete($table)
    {
        $sth = $this->myexecute('DELETE FROM ' . $this->column($table));
        return $sth;
    }
    function limit($c, $cc = 0)
    {
        if ($cc) $this->limit = " LIMIT $c , $cc";
        else $this->limit = " LIMIT $c";
        return $this;
    }

    function select($table = '', $ar = [])
    {
        $select = '';
        foreach ($ar as $vle) {
            $select .= ', ' . $this->column($vle);
        }
        $select = $select == '' ? '*' . $this->as : mb_substr($select, 2) . $this->as;
        $sth = $this->myexecute("SELECT $select FROM " . $this->column($table));

        return $sth;
    }
    function insert($table, $ar = [])
    {
        $LisKy = '';
        $LisVl = '';
        foreach ($ar as $key => $vle) {
            $LisKy .= ', ' . $this->column($key);
            $LisVl .= ', ' . $this->bind($vle);
        }
        $sth = $this->myexecute('INSERT INTO ' . $this->column($table) . '(' . mb_substr($LisKy, 2) . ') VALUES (' . mb_substr($LisVl, 2) . ')');
        return $sth;
    }
    function multiinsert($table, $data = [])
    {
        $columns = implode(', ', array_map([$this, 'column'], array_keys($data[0])));
        $values = [];

        foreach ($data as $row) {
            $rowValues = implode(', ', array_map([$this, 'bind'], array_values($row)));
            $values[] = "({$rowValues})";
        }

        $valuesString = implode(', ', $values);

        $sth = $this->myexecute("INSERT INTO {$this->column($table)} ({$columns}) VALUES {$valuesString}");

        return $sth;
    }
    function getvalues($array)
    {
        $string = '';
        foreach ($array as $v) $string .= ', ' . $this->bind($v);
        return mb_substr($string, 2);
    }
    function getcolumns($array)
    {
        $string = '';
        foreach ($array as $k) $string .= ', ' . $this->column($k);
        return mb_substr($string, 2);
    }
    function getcolumnskeys($array)
    {
        $string = '';
        foreach ($array as $k => $v) $string .= ', ' . $this->column($k) . '=' . $this->bind($v);
        return mb_substr($string, 2);
    }

    function createdb($db)
    {
        return $this->pdo->query('create database ' . $this->column($db));
    }
    function showdbs()
    {
        return $this->pdo->query('show databases');
    }
    function showtbs()
    {
        return $this->pdo->query('show tables');
    }
    function dropdb($db)
    {
        return $this->pdo->query('drop database ' . $this->column($db));
    }
    function droptb($db)
    {
        return $this->pdo->query('drop table ' . $this->column($db));
    }
    function show($tb)
    {
        return $this->pdo->query('show columns from ' . $this->column($tb));
    }
    function as($old, $new)
    {
        $this->as .= ', ' . $this->column($old) . ' AS ' . $new;
        return $this;
    }
    function groupby($c, $type = '')
    {
        if ($type) $type = ' ' . $type;
        if (!$this->groupby) $this->groupby = ' group By ';
        else $this->groupby .= ', ';
        $this->groupby .= $this->column($c) . $type;
        $my = new defmypdo($this);
        $my->limit = function ($c, $cc = 0) use ($my) {
            if ($cc) $my->mypdo->groupbylimit = " LIMIT $c , $cc";
            else $my->mypdo->groupbylimit = " LIMIT $c";
            return $my;
        };
        return $my;
    }
    function orderby($c, $type = '')
    {
        if ($type) $type = ' ' . $type;
        if (!$this->orderby) $this->orderby = ' order By ';
        else $this->orderby .= ', ';
        $this->orderby .= $this->column($c) . $type;
        $my = new defmypdo($this);
        $my->limit = function ($c, $cc = 0) use ($my) {
            if ($cc) $my->mypdo->orderbylimit = " LIMIT $c , $cc";
            else $my->mypdo->orderbylimit = " LIMIT $c";
            return $my;
        };
        return $my;
    }
    function column($column)
    {
        if (!is_object($column)) {
            if ((strpos($column, '.') === false) && (strpos($column, '(') === false) && $column) return "`$column`";
            return $column;
        }
        if (get_class($column) == 'mysqlfun') {
            $mypdo = $this;
            return $column->get_string($mypdo);
        }
    }
    function join($table, ...$data)
    {
        $this->join .= ' JOIN ' . $this->column($table);
        $ons1 = [];
        $ons2 = [];
        foreach ($data as $k => $v) {
            if ($k % 2 == 0) $ons1[] = $v;
            else $ons2[] = $v;
        }
        $on = '';
        foreach ($ons2 as $c => $on2) {
            $on1 = $ons1[$c];
            $on .= "$on1=$on2 AND ";
        }
        if ($on) $this->join .= ' ON ' . mb_substr($on, 0, -5);
        return $this;
    }
    function having($c)
    {
        $this->having = ' ' . $c;
        return $this;
    }
    function count($c)
    {
        return 'count(' . $this->column($c) . ')';
    }
    function rand($c = '')
    {
        return 'rand(' . $c . ')';
    }
}//end class 