<?php
namespace fuTruck\One;

use fuTruck\Package\MreClass;

trait MreMark
{
    protected $mreMark = "";

    public function GetMre()
    {
        return MreClass::GetInstance($this->mreMark);
    }

    public function GetMreMark()
    {
        return $this->mreMark;
    }

    public function SetMreMark($mreMark)
    {
        $this->mreMark = $mreMark;
    }

    public function GetFirstError()
    {
        $fieldsCn = $this->GetMre()->GetErrorObj()->GetFieldsCn();
        foreach ($this->GetMre()->GetErrors() as $e) {
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

    public function GetErrorsString($glue = ',')
    {
        $fieldsCn = $this->GetMre()->GetErrorObj()->GetFieldsCn();
        $res = [];
        foreach ($this->GetMre()->GetErrors() as $e) {
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

    public function GetFirstRow()
    {
        foreach ($this->GetMre()->GetRows() as $row) {
            if (!empty($row)) {
                return $row;
            }
        }
        return [];
    }

}