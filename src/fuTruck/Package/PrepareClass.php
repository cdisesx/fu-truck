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
    public function GetExistRowMap($clearCache = false){
        if(empty($this->ExistRowMap) || $clearCache){
            $mre = $this->GetMre();
            $model = $mre->GetModel();
            $query = $model->newQuery();
            $err = "";
            $mre->GetModelObj()->SetPkWhere($query, $mre->GetRows(), $err);
            if(!empty($err)){
                return [];
            }
            $rows = $query->get()->toArray();
            $map = [];
            foreach ($rows as $row) {
                $map[$mre->GetModelObj()->GetPkValue($row)] = $row;
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
    public function GetExistRow(array $row, $clearCache = false){
        $existMsp = $this->GetExistRowMap($clearCache);
        $mre = $this->GetMre();
        $pk = $mre->GetModelObj()->GetPkValue($row);
        if(isset($existMsp[$pk])){
            return $existMsp[$pk];
        }
        return [];
    }

    /**
     * @return array
     */
    public function GetEnums(){
        return $this->Enums;
    }

    /**
     * @param $name
     * @return array
     */
    public function GetEnum($name){
        return $this->Enums[$name] ?? [];
    }

    public function SetEnum($name, $map){
        $this->Enums[$name] = $map;
    }

    public function DoPrepare()
    {
        $mre = $this->GetMre();
        $updateFields = [];
        $error = "";
        foreach ($mre->GetRowsObj()->GetUpdateFieldsList() as $index=>$fields) {
            if($fields === true){
                $updateFields = true;
                $mre->GetModelObj()->FixFillFields($updateFields);
                $mre->GetRowsObj()->SetUpdateFields($index, $updateFields);
                continue;
            }
            if(is_array($fields)){
                $updateFields = array_unique(array_merge($updateFields, $fields));
                $mre->GetRowsObj()->SetUpdateFields($index, $updateFields);
            }
        }
        $ok = forward_static_call_array($this->prepareFun, [&$this, $updateFields, &$error]);
        if(!$ok){
            $mre->AddError(0, "", "", $error);
        }
        return $ok;
    }

}
