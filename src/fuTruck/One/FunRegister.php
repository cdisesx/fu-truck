<?php
namespace fuTruck\One;

trait FunRegister
{
    public function RegisterFun($name, $fun)
    {
        if(in_array($name, self::$FunList)){
            $this->$name = $fun;
            return true;
        }
        return false;
    }

    public function RegisterClass($name, $fun)
    {
        if(in_array($name, self::$ClassList)){
            $this->$name = $fun;
            return true;
        }
        return false;
    }
}