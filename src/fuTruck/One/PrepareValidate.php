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

    public function GetPrepare()
    {
        if($this->Prepare instanceof PrepareClass){
            return $this->Prepare;
        }
        $prepare = new PrepareClass($this->GetMreMark());

        if(!empty($this->PrepareFun)){
            $prepare->RegisterFun(PrepareClass::FunPrepare, $this->PrepareFun);
        }
        $this->Prepare = $prepare;

        return $prepare;
    }


    public $ValidClassName = "";
    public $FunSubFore = "fore_valid_";
    public $FunSubValid = "valid_";

    public function GetValidate()
    {
        $prepare = $this->GetPrepare();
        $validate = new ValidateClass($prepare);
        $validate->SetFunSubFore($this->FunSubFore);
        $validate->SetFunSubValid($this->FunSubValid);

        if($this->ValidClassName){
            $validate->SetValidateClassName($this->ValidClassName);
            $methods = get_class_methods($this->ValidClassName);
            foreach ($methods as $method) {
                $RegisterFun = false;

                if(substr($method, 0, strlen($this->FunSubFore)) === $this->FunSubFore){
                    $RegisterFun = true;
                }
                if(substr($method, 0, strlen($this->FunSubValid)) === $this->FunSubValid){
                    $RegisterFun = true;
                }

                if($RegisterFun){
                    $validate->RegisterValidateFun($method);
                }
            }
        }

        return $validate;
    }

    public function DoValidateRows()
    {
        $validate = $this->GetValidate();
        return $validate->ValidateRows();
    }

    public function DoPrepare()
    {
        $validate = $this->GetValidate();
        return $validate->GetPrepare()->DoPrepare();
    }
}