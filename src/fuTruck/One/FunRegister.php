<?php
namespace fuTruck\One;

trait FunRegister
{
    public function registerFun($name, $fun)
    {
        if(in_array($name, self::$FunList)){
            $this->$name = $fun;
            return true;
        }
        return false;
    }

    public function registerClass($name, $fun)
    {
        if(in_array($name, self::$ClassList)){
            $this->$name = $fun;
            return true;
        }
        return false;
    }
}