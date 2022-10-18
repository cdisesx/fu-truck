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
        $updateFields = $mre->GetRowsObj()->GetUpdateFields($index);
        $updateFields = array_intersect($updateFields, $mre->GetModel()::GetSaveFields());

        $saveData = self::GetSaveData($row, $updateFields);
        $dbModel = $mre->GetModel();
        $m = $mre->GetModelObj();

        if($isUpdateFun){
            $isUpdate = forward_static_call_array($isUpdateFun, [$prepareClass, $row]);
        }else{
            $isUpdate = $m->IsUpdate($row);
        }

        foreach ($saveData as $k=>$v) {
            if(is_array($v)){
                $mre->GetErrorObj()->AddError($index, $k, $v, "数据{$k}为数组，Sql无法保存");
            }
        }
        if($mre->GetErrors()){
            return false;
        }

        $builder = $dbModel::Builder();
        if($isUpdate && count($m->GetPk()) > 0){
            $error = "";
            if(!$m->SetPkWhere($query, [$row], $error)){
                return false;
            }
            $ok = $builder->Update($saveData);
        }else{
            $ok = $builder->Insert($saveData);
        }

        $pkFields = $m->GetPk();
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

    public function DoSave($DbTransaction = false)
    {
        $mre = $this->GetMre();
        if(empty($mre->GetRows())){
            return true;
        }

        $DbTransaction && $mre->GetModelObj()->Begin();

        $resPkList = [];
        foreach ($mre->GetRows() as $index=>&$row) {
            $updateFields =  $mre->GetRowsObj()->GetUpdateFields($index);
            if($updateFields === true){
                try {
                    $mre->GetModelObj()->FixFillFields($updateFields);
                } catch (\Exception $e) {
                    $mre->GetErrorObj()->AddError($index, "", $row, $e->getMessage());
                    $DbTransaction && $mre->GetModelObj()->Rollback();
                    return false;
                }
                $mre->GetRowsObj()->SetUpdateFields($index, $updateFields);
            }

            $pk = call_user_func_array($this->saveRowFun, [$this->GetPrepare(), &$mre, $index, $row, $this->isUpdateFun]);
            if($pk){
                $err = "";
                if($this->afterSaveFun){
                    $afterSaveRes = call_user_func_array($this->afterSaveFun, [$this->GetPrepare(), $row, $updateFields, $pk, &$err]);
                    if(!empty($err)){
                        $mre->GetErrorObj()->AddError($index, "", $row, $err);
                    }
                    if(!$afterSaveRes){
                        if(empty($err)){
                            $mre->GetErrorObj()->AddError($index, "", $row, "AfterSave执行失败");
                        }
                        $DbTransaction && $mre->GetModelObj()->Rollback();
                        return false;
                    }
                }
            }

            if($pk){
                $resPkList[$index] = $pk;
            }else{
                $mre->GetErrorObj()->AddError($index, "", $row, "保存失败");
                $DbTransaction && $mre->GetModelObj()->Rollback();
                return false;
            }
        }

        $DbTransaction && $mre->GetModelObj()->Commit();
        return $resPkList;
    }

    public function DoSaveWithValidate($DbTransaction = false)
    {
        $validateRes = $this->DoValidateRows();

        if($validateRes){
            return $this->DoSave($DbTransaction);
        }

        return false;
    }

    public function DoSaveWithPrepare($DbTransaction = false)
    {
        $prepare = $this->GetPrepare();
        $prepare->DoPrepare();
        return $this->DoSave($DbTransaction);
    }

    public function AppendRow($row, $updateFields = true, $index = false)
    {
        $this->GetMre()->GetRowsObj()->AppendRow($row, $updateFields, $index);
    }

    public function GetErrors()
    {
        return $this->GetMre()->GetErrors();
    }

    public function RemoveExistRows()
    {
        $mre = $this->GetMre();
        $prepare = $this->GetPrepare();
        $prepare->GetExistRowMap();
        foreach ($mre->GetRows() as $index=>$row) {
            if ($prepare->GetExistRow($row)){
                $mre->GetRowsObj()->RemoveRow($index);
            }
        }
    }

}