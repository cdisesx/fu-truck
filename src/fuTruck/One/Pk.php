<?php
namespace fuTruck\One;

use fuPdo\mysql\Builder;

trait Pk
{
    protected $pk = ["id"];

    public function getPk()
    {
        return $this->pk;
    }

    public function getPkStr()
    {
        return join('-', $this->pk);
    }

    public function setPkWhere(Builder &$query, $rows, &$err)
    {
        foreach ($this->pk as $pk) {
            $_pkValues = array_filter(array_column($rows, $pk), 'trim');
            if(empty($_pkValues)){
                $err = "取不到PK字段：{$pk}";
                return false;
            }
            $query->whereIn($pk, $_pkValues);
        }
        return true;
    }

    public function getPkValueStr($row)
    {
        $pkValues = $this->getPkValue($row);
        return join('-', $pkValues);
    }

    public function getPkValue($row)
    {
        $pkValues = [];
        foreach ($this->pk as $pk) {
            $pkValues[] = @$row[$pk];
        }
        return $pkValues;
    }

    public function isUpdate($row)
    {
        if(!empty($row[$this->pk[0]])){
            return true;
        }
        return false;
    }
}