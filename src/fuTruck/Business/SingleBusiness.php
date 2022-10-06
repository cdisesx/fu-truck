<?php
namespace fuTruck\Business;

class SingleBusiness
{
    protected static $instances = [];
    public static function GetInstance($optional = array())
    {
        $class = get_called_class();
        $optional_serial = md5(json_encode($optional));
        $class_serial = $class."_".$optional_serial;
        if (!isset(self::$instances[$class_serial]) || get_class(self::$instances[$class_serial]) !== $class) {
            self::$instances[$class_serial] = new $class($optional);
        }
        return self::$instances[$class_serial];
    }
}