<?php
namespace fuTruck\Package;

use fuPdo\mysql\Builder;
use fuTruck\One\FunRegister;
use fuTruck\One\MreMark;
use fuTruck\One\PrepareValidate;

class SaverClass
{
    use FunRegister;
    use MreMark;
    use PrepareValidate;

    public function __construct($mreMark)
    {
        $this->mreMark = $mreMark;
    }

    const FunSaveRow = "saveRowFun";
    const FunAfterSave = "afterSaveFun";
    const FunIsUpdate = "isUpdateFun";
    public static $FunList = [
        self::FunSaveRow,
        self::FunAfterSave,
        self::FunIsUpdate,
    ];

    protected $saveRowFun = ["fuTruck\Package\SaverClass", "DefaultSaveRowFun"];
    protected $afterSaveFun = [];
    protected $isUpdateFun = [];
    protected $updateField = "update_at";
    protected $createField = "create_at";

    public static function DefaultSaveRowFun(PrepareClass $prepareClass, MreClass &$mre, $index, $row, $isUpdateFun = null)
    {
        $updateFields = $mre->getRowsObj()->getUpdateFields($index);
        $updateFields = array_intersect($updateFields, $mre->getModel()::GetSaveFields());

        $saveData = self::GetSaveData($row, $updateFields);
        $dbModel = $mre->getModel();
        $m = $mre->getModelObj();

        if($isUpdateFun){
            $isUpdate = forward_static_call_array($isUpdateFun, [$prepareClass, $row]);
        }else{
            $isUpdate = $m->IsUpdate($row);
        }

        foreach ($saveData as $k=>$v) {
            if(is_array($v)){
                $mre->getErrorObj()->addError($index, $k, $v, "数据{$k}为数组，Sql无法保存");
            }
        }
        if($mre->getErrors()){
            return false;
        }

        $builder = $dbModel::Builder();
        if($isUpdate && count($m->getPk()) > 0){
            $error = "";
            if(!$m->setPkWhere($query, [$row], $error)){
                return false;
            }
            $ok = $builder->update($saveData);
        }else{
            $ok = $builder->insert($saveData);
        }

        $pkFields = $m->getPk();
        if(count($pkFields) > 1){
            $pk = [];
            foreach ($pkFields as $pkField) {
                $pk[$pkField] = $saveData[$pkField];
            }
        }else{
            if(!$ok){
                return false;
            }else{
                $pk = $ok;
            }
        }

        return $pk;
    }

    /**
     * 根据UpdateFields获取要保存的数据
     * @param $row
     * @param $updateFields
     * @return array
     */
    public static function GetSaveData($row, $updateFields)
    {
        if($updateFields === true){
            return $row;
        }

        $saveData = [];
        foreach ($updateFields as $updateField) {
            if(isset($row[$updateField])){
                $saveData[$updateField] = $row[$updateField];
            }
        }
        return $saveData;
    }

    public function doSave($DbTransaction = false)
    {
        $mre = $this->getMre();
        if(empty($mre->getRows())){
            return true;
        }

        $DbTransaction && $mre->getModelObj()->Begin();

        $resPkList = [];
        foreach ($mre->getRows() as $index=>&$row) {
            $updateFields =  $mre->getRowsObj()->getUpdateFields($index);
            if($updateFields === true){
                try {
                    $mre->getModelObj()->fixFillFields($updateFields);
                } catch (\Exception $e) {
                    $mre->getErrorObj()->addError($index, "", $row, $e->getMessage());
                    $DbTransaction && $mre->getModelObj()->rollback();
                    return false;
                }
                $mre->getRowsObj()->setUpdateFields($index, $updateFields);
            }

            $pk = call_user_func_array($this->saveRowFun, [$this->getPrepare(), &$mre, $index, $row, $this->isUpdateFun]);
            if($pk){
                $err = "";
                if($this->afterSaveFun){
                    $afterSaveRes = call_user_func_array($this->afterSaveFun, [$this->getPrepare(), $row, $updateFields, $pk, &$err]);
                    if(!empty($err)){
                        $mre->getErrorObj()->addError($index, "", $row, $err);
                    }
                    if(!$afterSaveRes){
                        if(empty($err)){
                            $mre->getErrorObj()->addError($index, "", $row, "AfterSave执行失败");
                        }
                        $DbTransaction && $mre->getModelObj()->rollback();
                        return false;
                    }
                }
            }

            if($pk){
                $resPkList[$index] = $pk;
            }else{
                $mre->getErrorObj()->addError($index, "", $row, "保存失败");
                $DbTransaction && $mre->getModelObj()->rollback();
                return false;
            }
        }

        $DbTransaction && $mre->getModelObj()->commit();
        return $resPkList;
    }

    public function doSaveWithValidate($DbTransaction = false)
    {
        $validateRes = $this->doValidateRows();

        if($validateRes){
            return $this->doSave($DbTransaction);
        }

        return false;
    }

    public function doSaveWithPrepare($DbTransaction = false)
    {
        $prepare = $this->getPrepare();
        $prepare->doPrepare();
        return $this->doSave($DbTransaction);
    }

    public function appendRow($row, $updateFields = true, $index = false)
    {
        $this->getMre()->getRowsObj()->appendRow($row, $updateFields, $index);
    }

    public function getErrors()
    {
        return $this->getMre()->getErrors();
    }

    public function removeExistRows()
    {
        $mre = $this->getMre();
        $prepare = $this->getPrepare();
        $prepare->getExistRowMap();
        foreach ($mre->getRows() as $index=>$row) {
            if ($prepare->getExistRow($row)){
                $mre->getRowsObj()->removeRow($index);
            }
        }
    }

}