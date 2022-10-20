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

    public function getPrepare()
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

    public function getValidateClassName()
    {
        return $this->validateClassName;
    }
    public function setValidateClassName($className)
    {
        $this->validateClassName = $className;
    }
    public function registerValidateFun($funName)
    {
        if(!in_array($funName, $this->validateFunList)){
            $this->validateFunList[] = $funName;
        }
    }
    public function getValidateFunList()
    {
        return $this->validateFunList;
    }

    /**
     * 对整体要保存的Rows进行校验
     * @return bool
     */
    public function validateRows()
    {
        $ok = $this->prepare->doPrepare();
        if(!$ok){
            return false;
        }

        $mre = $this->prepare->getMre();

        $validHasFalse = false;
        foreach ($mre->getRows() as $index => &$row) {
            $ok = $this->validateAfterPrepare($index, $row);
            if(!$ok){
                $validHasFalse = true;
            }
        }
        return !$validHasFalse;
    }

    public function validateRowsIgnorePrepare()
    {
        $mre = $this->prepare->getMre();

        $validHasFalse = false;
        foreach ($mre->getRows() as $index => &$row) {
            $ok = $this->validateAfterPrepare($index, $row);
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
    public function validateRow(&$row, $updateFields)
    {
        forward_static_call_array($this->prepare->prepareFun, [$this->prepare, $updateFields]);
        return $this->validateAfterPrepare(0, $row, $updateFields);
    }

    protected $funSubFore = "fore_valid_";
    protected $funSubValid = "valid_";

    public function setFunSubFore($sub)
    {
        $this->funSubFore = $sub;
    }
    public function setFunSubValid($sub)
    {
        $this->funSubValid = $sub;
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
    public function validateAfterPrepare($index, &$row, $type = true)
    {
        $validRes = true;
        $mre = $this->prepare->getMre();
        try{
            if($type === true){
                $type = self::$ValidateTypeFore + self::$ValidateTypeEnum + self::$ValidateTypeValidFun;
            }

            // 优先校验：优先遍历方法中的fore_valid_xxx方法
            if($type&self::$ValidateTypeFore){
                $className = $this->getValidateClassName();
                $methods = $this->getValidateFunList();
                foreach ($methods as $method) {
                    if(substr($method, 0, strlen($this->funSubFore)) === $this->funSubFore){
                        $validError = "";
                        $ok = forward_static_call_array([$className, $method], [&$this->prepare, $index, &$row, &$validError]);
                        if (!$ok) {
                            $mre->addError($index, $method, "", $validError);
                            $validRes = false;
                            continue;
                        }
                    }
                }
            }

            $updateFields = $mre->getRowsObj()->getUpdateFields($index);
            foreach ($updateFields as $field) {
                if($field == "create_at" || $field == "update_at"){
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
                        $mre->addError($index, $field, $val, "值不在范围内");
                        $validRes = false;
                        goto ValidateFunc;
                    }
                }

                ValidateFunc:

                // 方法校验
                if($type&self::$ValidateTypeValidFun){
                    $funName = $this->funSubValid.$field;
                    $className = $this->getValidateClassName();
                    if (in_array($funName, $this->getValidateFunList())) {
                        $validError = "";
                        $ok = forward_static_call_array([$className, $funName], [&$this->prepare, $index, &$row, &$validError]);
                        if (!$ok) {
                            $mre->addError($index, $field, $val, $validError);
                            $validRes = false;
                            continue;
                        }
                    }
                }

            }

            $mre->getRowsObj()->setRow($index, $row);

        }catch (\Exception $exception){
            echo $exception->getMessage();exit;
        }

        return $validRes;
    }
}