<?php
namespace fuTruck\Package;

use fuTruck\One\FunRegister;

class ValidateClass
{
    use FunRegister;

    /**
     * @var PrepareClass
     */
    protected  $prepare;

    public function GetPrepare()
    {
        return $this->prepare;
    }

    public function __construct(PrepareClass &$prepare)
    {
        if($prepare instanceof PrepareClass){
            $this->prepare = $prepare;
        }
    }

    protected $validateClassName = "";
    protected $validateFunList = [];

    public function GetValidateClassName()
    {
        return $this->validateClassName;
    }
    public function SetValidateClassName($className)
    {
        $this->validateClassName = $className;
    }
    public function RegisterValidateFun($funName)
    {
        if(!in_array($funName, $this->validateFunList)){
            $this->validateFunList[] = $funName;
        }
    }
    public function GetValidateFunList()
    {
        return $this->validateFunList;
    }

    /**
     * 对整体要保存的Rows进行校验
     * @return bool
     */
    public function ValidateRows()
    {
        $ok = $this->prepare->DoPrepare();
        if(!$ok){
            return false;
        }

        $mre = $this->prepare->GetMre();

        $validHasFalse = false;
        foreach ($mre->GetRows() as $index => &$row) {
            $ok = $this->ValidateAfterPrepare($index, $row);
            if(!$ok){
                $validHasFalse = true;
            }
        }
        return !$validHasFalse;
    }

    public function ValidateRowsIgnorePrepare()
    {
        $mre = $this->prepare->GetMre();

        $validHasFalse = false;
        foreach ($mre->GetRows() as $index => &$row) {
            $ok = $this->ValidateAfterPrepare($index, $row);
            if(!$ok){
                $validHasFalse = true;
            }
        }
        return !$validHasFalse;
    }

    /**
     * 对某条要保存的Row进行校验
     * @param $row
     * @param $updateFields
     * @return bool
     */
    public function ValidateRow(&$row, $updateFields)
    {
        forward_static_call_array($this->prepare->PrepareFun, [$this->prepare, $updateFields]);
        return $this->ValidateAfterPrepare(0, $row, $updateFields);
    }

    protected $FunSubFore = "fore_valid_";
    protected $FunSubValid = "valid_";

    public function SetFunSubFore($sub)
    {
        $this->FunSubFore = $sub;
    }
    public function SetFunSubValid($sub)
    {
        $this->FunSubValid = $sub;
    }

    public static $ValidateTypeFore = 1;
    public static $ValidateTypeEnum = 2;
    public static $ValidateTypeValidFun = 4;

    /**
     * 确保prepare后校验单行
     * @param $index
     * @param $row
     * @param bool|int $type
     * @return bool
     */
    public function ValidateAfterPrepare($index, &$row, $type = true)
    {
        $validRes = true;
        $mre = $this->prepare->GetMre();
        try{
            if($type === true){
                $type = self::$ValidateTypeFore + self::$ValidateTypeEnum + self::$ValidateTypeValidFun;
            }

            // 优先校验：优先遍历方法中的fore_valid_xxx方法
            if($type&self::$ValidateTypeFore){
                $className = $this->GetValidateClassName();
                $methods = $this->GetValidateFunList();
                foreach ($methods as $method) {
                    if(substr($method, 0, strlen($this->FunSubFore)) === $this->FunSubFore){
                        $validError = "";
                        $ok = forward_static_call_array([$className, $method], [&$this->prepare, $index, &$row, &$validError]);
                        if (!$ok) {
                            $mre->AddError($index, $method, "", $validError);
                            $validRes = false;
                            continue;
                        }
                    }
                }
            }

            $updateFields = $mre->GetRowsObj()->GetUpdateFields($index);
            foreach ($updateFields as $field) {
                if($field == "created_at" || $field == "updated_at"){
                    continue;
                }
                if(!isset($row[$field])){
                    $row[$field] = "";
                }

                $val = $row[$field];

                // 枚举值校验
                if($type&self::$ValidateTypeEnum){
                    $menu = $this->Enums[$field] ?? [];
                    if(empty($menu)){
                        goto ValidateFunc;
                    }
                    if(!array_key_exists($val, $menu)){
                        $mre->AddError($index, $field, $val, "值不在范围内");
                        $validRes = false;
                        goto ValidateFunc;
                    }
                }

                ValidateFunc:

                // 方法校验
                if($type&self::$ValidateTypeValidFun){
                    $funName = $this->FunSubValid.$field;
                    $className = $this->GetValidateClassName();
                    if (in_array($funName, $this->GetValidateFunList())) {
                        $validError = "";
                        $ok = forward_static_call_array([$className, $funName], [&$this->prepare, $index, &$row, &$validError]);
                        if (!$ok) {
                            $mre->AddError($index, $field, $val, $validError);
                            $validRes = false;
                            continue;
                        }
                    }
                }

            }

            $mre->GetRowsObj()->SetRow($index, $row);

        }catch (\Exception $exception){
            echo $exception->getMessage();exit;
        }

        return $validRes;
    }
}