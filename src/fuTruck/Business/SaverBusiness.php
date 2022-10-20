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

    protected $funSubFore = "fore_valid_";
    protected $funSubValid = "valid_";
    protected $validClassName = "";

    public static function SaveRowWithValid($saveRow, &$error)
    {
        $saver = self::GetSaver();
        $saver->appendRow($saveRow);
        $res = $saver->doSaveWithValidate(true);

        if(!$res || $saver->getErrors()){
            $error = $saver->getFirstError();
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
        $saverClass->funSubValid = $s->funSubValid;
        $saverClass->funSubFore = $s->funSubFore;
        $saverClass->getMre()->getErrorObj()->setFieldsCn($s->fieldsCn);

        if($s->validClassName){
            $saverClass->validClassName = $s->validClassName;
        }else{
            $saverClass->validClassName = get_called_class();
        }

        if(!$s->useDefaultSaveRowFun){
            $saverClass->registerFun(SaverClass::FunSaveRow, [get_called_class(), "SaverRow"]);
        }
        if(!$s->useDefaultIsUpdateFun){
            $saverClass->registerFun(SaverClass::FunIsUpdate, [get_called_class(), "IsUpdate"]);
        }
        $saverClass->registerFun(SaverClass::FunAfterSave, [get_called_class(), "AfterSaverRow"]);
        $saverClass->PrepareFun = [get_called_class(), "Prepare"];

        return $saverClass;
    }

    /**
     * @param $saver SaverClass
     * @param $rows array
     * @param $updateFields array|bool
     * @return mixed
     */
    public static function AppendRowsAndValidate(SaverClass &$saver,array $rows, $updateFields)
    {
        foreach ($rows as $row) {
            $saver->appendRow($row, $updateFields);
        }
        return $saver->doValidateRows();
    }


}