<?php
namespace fuTruck\One;

use fuPdo\mysql\Builder;

trait Pk
{
    protected $pk = ["id"];

    public function GetPk()
    {
        return $this->pk;
    }

    public function GetPkStr()
    {
        return join('-', $this->pk);
    }

    public function SetPkWhere(Builder &$query, $rows, &$err)
    {
        foreach ($this->pk as $pk) {
            $_pkValues = array_filter(array_column($rows, $pk), 'trim');
            if(empty($_pkValues)){
                $err = "取不到PK字段：{$pk}";
                return false;
            }
            $query->WhereIn($pk, $_pkValues);
        }
        return true;
    }

    public function GetPkValue($row)
    {
        $pkValues = [];
        foreach ($this->pk as $pk) {
            $pkValues[] = @$row[$pk];
        }
        return join('-', $pkValues);
    }

    public function IsUpdate($row)
    {
        if(!empty($row[$this->pk[0]])){
            return true;
        }
        return false;
    }
}