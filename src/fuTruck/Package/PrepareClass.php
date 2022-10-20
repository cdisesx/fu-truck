<?php
namespace fuTruck\Package;

use fuTruck\One\FunRegister;
use fuTruck\One\MreMark;

class PrepareClass
{
    use FunRegister;
    use MreMark;

    public function __construct($mreMark)
    {
        $this->mreMark = $mreMark;
    }

    const FunPrepare = "prepareFun";
    public static $FunList = [
        self::FunPrepare,
    ];

    public $prepareFun = ["fuTruck\Package\ValidatePrepareClass", "DefaultPrepare"];

    /**
     * 保存枚举数据
     * @var array [fieldName][]{"key":"name"}
     */
    protected $Enums = [];

    /**
     * @param PrepareClass $prepareClass
     * @param bool|array $updateFields
     * @return bool
     */
    public static function DefaultPrepareEnums(PrepareClass &$prepareClass, $updateFields)
    {
        return true;
    }

    /**
     * @param PrepareClass $prepareClass
     * @param bool|array $updateFields
     * @return bool
     */
    public static function DefaultPrepare(PrepareClass &$prepareClass, $updateFields)
    {
        return true;
    }

    /**
     * 保存枚举数据
     * @var array [pk]{"key":"name"}
     */
    protected $ExistRowMap = [];

    /**
     * @param bool $clearCache
     * @return array
     */
    public function getExistRowMap($clearCache = false){
        if(empty($this->ExistRowMap) || $clearCache){
            $mre = $this->getMre();
            $query = $mre->getModel()::Builder();
            $err = "";
            $mre->getModelObj()->setPkWhere($query, $mre->getRows(), $err);
            if(!empty($err)){
                return [];
            }
            $rows = $query->select();
            $map = [];
            foreach ($rows as $row) {
                $map[$mre->getModelObj()->getPkValueStr($row)] = $row;
            }
            $this->ExistRowMap = $map;
        }
        return $this->ExistRowMap;
    }

    /**
     * @param array $row
     * @param bool $clearCache
     * @return array
     */
    public function getExistRow(array $row, $clearCache = false){
        $existMsp = $this->getExistRowMap($clearCache);
        $mre = $this->getMre();
        $pk = $mre->getModelObj()->getPkValueStr($row);
        if(isset($existMsp[$pk])){
            return $existMsp[$pk];
        }
        return [];
    }

    /**
     * @return array
     */
    public function getEnums(){
        return $this->Enums;
    }

    /**
     * @param $name
     * @return array
     */
    public function getEnum($name){
        return $this->Enums[$name] ?? [];
    }

    public function setEnum($name, $map){
        $this->Enums[$name] = $map;
    }

    public function doPrepare()
    {
        $mre = $this->getMre();
        $updateFields = [];
        $error = "";
        foreach ($mre->getRowsObj()->getUpdateFieldsList() as $index=>$fields) {
            if($fields === true){
                $updateFields = true;
                try {
                    $mre->getModelObj()->fixFillFields($updateFields);
                } catch (\Exception $e) {
                    $mre->appendStrError($e->getMessage());
                    return false;
                }
                $mre->getRowsObj()->setUpdateFields($index, $updateFields);
                continue;
            }
            if(is_array($fields)){
                $updateFields = array_unique(array_merge($updateFields, $fields));
                $mre->getRowsObj()->setUpdateFields($index, $updateFields);
            }
        }
        $ok = forward_static_call_array($this->prepareFun, [&$this, $updateFields, &$error]);
        if(!$ok){
            $mre->appendStrError($error);
        }
        return $ok;
    }

}
