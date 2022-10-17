<?php
namespace fuTruck\Business;

use fuTruck\Package\MreClass;
use fuTruck\Package\PrepareClass;
use fuTruck\Package\SaverClass;

class SaverBusiness extends SingleBusiness
{

    protected $dbModelName = "";
    protected $pk = ["id"];
    protected $fieldsCn = [];

    protected $useDefaultSaveRowFun = true;
    protected $useDefaultIsUpdateFun = true;

    public static function SaverRow(PrepareClass $prepareClass, MreClass $mre, $index, $row, $isUpdateFun = null){}
    public static function AfterSaverRow(PrepareClass $prepareClass, $row, $updateFields, $pk, &$err){return true;}
    public static function IsUpdate(PrepareClass $prepareClass, $row){return false;}

    public static function Prepare(PrepareClass &$prepareClass, $updateFields, &$error){}

    protected $FunSubFore = "fore_valid_";
    protected $FunSubValid = "valid_";
    protected $ValidClassName = "";

    public static function SaveRowWithValid($saveRow, &$error)
    {
        $saver = self::GetSaver();
        $saver->AppendRow($saveRow);
        $res = $saver->DoSaveWithValidate(true);

        if(!$res || $saver->GetErrors()){
            $error = $saver->GetFirstError();
        }

        return $res[0];
    }


    public static function GetSaver()
    {
        /**
         * @var $s self
         */
        $s = self::GetInstance();

        $mreMark = MreClass::CreateInstance($s->dbModelName, $s->pk);
        $saverClass = new SaverClass($mreMark);
        $saverClass->FunSubValid = $s->FunSubValid;
        $saverClass->FunSubFore = $s->FunSubFore;
        $saverClass->GetMre()->GetErrorObj()->SetFieldsCn($s->fieldsCn);

        if($s->ValidClassName){
            $saverClass->ValidClassName = $s->ValidClassName;
        }else{
            $saverClass->ValidClassName = get_called_class();
        }

        if(!$s->useDefaultSaveRowFun){
            $saverClass->RegisterFun(SaverClass::FunSaveRow, [get_called_class(), "SaverRow"]);
        }
        if(!$s->useDefaultIsUpdateFun){
            $saverClass->RegisterFun(SaverClass::FunIsUpdate, [get_called_class(), "IsUpdate"]);
        }
        $saverClass->RegisterFun(SaverClass::FunAfterSave, [get_called_class(), "AfterSaverRow"]);
        $saverClass->PrepareFun = [get_called_class(), "Prepare"];

        return $saverClass;
    }

    /**
     * @param $saver SaverClass
     * @param $rows array
     * @param $updateFields array|bool
     * @return mixed
     */
    public static function AppendRowsAndValidate(SaverClass &$saver, $rows, $updateFields)
    {
        foreach ($rows as $row) {
            $saver->AppendRow($row, $updateFields);
        }
        return $saver->DoValidateRows();
    }


}