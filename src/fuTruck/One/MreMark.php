<?php
namespace fuTruck\One;

use fuTruck\Package\MreClass;

trait MreMark
{
    protected $mreMark = "";

    public function getMre()
    {
        return MreClass::GetInstance($this->mreMark);
    }

    public function getMreMark()
    {
        return $this->mreMark;
    }

    public function setMreMark($mreMark)
    {
        $this->mreMark = $mreMark;
    }

    public function getFirstError()
    {
        $fieldsCn = $this->getMre()->getErrorObj()->getFieldsCn();
        foreach ($this->getMre()->getErrors() as $e) {
            if (!empty($e['field']) || !empty($e['error'])) {
                $err = [];
                if(!empty($e['field'])){
                    $err[] = ($fieldsCn[$e['field']] ?? $e['field'])."错误";
                }
                if(!empty($e['error'])){
                    $err[] = $e['error'];
                }
                return join(':', $err);
            }
        }
        return "";
    }

    public function getErrorsString($glue = ',')
    {
        $fieldsCn = $this->getMre()->getErrorObj()->getFieldsCn();
        $res = [];
        foreach ($this->getMre()->getErrors() as $e) {
            if (!empty($e['field']) || !empty($e['error'])) {
                $err = [];
                if(!empty($e['field'])){
                    $err[] = ($fieldsCn[$e['field']] ?? $e['field'])."错误";
                }
                if(!empty($e['error'])){
                    $err[] = $e['error'];
                }
                $res[] = join(':', $err);
            }
        }
        return join($glue, $res);
    }

    public function getFirstRow()
    {
        foreach ($this->getMre()->getRows() as $row) {
            if (!empty($row)) {
                return $row;
            }
        }
        return [];
    }

}