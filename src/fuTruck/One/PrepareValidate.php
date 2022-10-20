<?php
namespace fuTruck\One;

use fuTruck\Package\PrepareClass;
use fuTruck\Package\ValidateClass;

trait PrepareValidate
{
    public $PrepareFun = "";

    /**
     * @var PrepareClass
     */
    protected $Prepare;

    public function getPrepare()
    {
        if($this->Prepare instanceof PrepareClass){
            return $this->Prepare;
        }
        $prepare = new PrepareClass($this->getMreMark());

        if(!empty($this->PrepareFun)){
            $prepare->registerFun(PrepareClass::FunPrepare, $this->PrepareFun);
        }
        $this->Prepare = $prepare;

        return $prepare;
    }


    public $validClassName = "";
    public $funSubFore = "fore_valid_";
    public $funSubValid = "valid_";

    public function getValidate()
    {
        $prepare = $this->getPrepare();
        $validate = new ValidateClass($prepare);
        $validate->setFunSubFore($this->funSubFore);
        $validate->setFunSubValid($this->funSubValid);

        if($this->validClassName){
            $validate->setValidateClassName($this->validClassName);
            $methods = get_class_methods($this->validClassName);
            foreach ($methods as $method) {
                $RegisterFun = false;

                if(substr($method, 0, strlen($this->funSubFore)) === $this->funSubFore){
                    $RegisterFun = true;
                }
                if(substr($method, 0, strlen($this->funSubValid)) === $this->funSubValid){
                    $RegisterFun = true;
                }

                if($RegisterFun){
                    $validate->registerValidateFun($method);
                }
            }
        }

        return $validate;
    }

    public function doValidateRows()
    {
        $validate = $this->getValidate();
        return $validate->validateRows();
    }

    public function doPrepare()
    {
        $validate = $this->getValidate();
        return $validate->getPrepare()->doPrepare();
    }
}