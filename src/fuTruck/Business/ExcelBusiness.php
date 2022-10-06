<?php
namespace fuTruck\Business;

use fuTruck\Package\ExcelClass;
use fuTruck\Package\MreClass;
use fuTruck\Package\PrepareClass;
use fuTruck\Package\SaverClass;
use Exception;
use fuPdo\mysql\Builder;

class ExcelBusiness extends SingleBusiness
{
    protected $dbModelName = "";
    protected $pk = ["id"];

    protected $excelSupportHeader = [];
    protected $excelHeaderStyle = [];
    protected $excelHeaderRowNum = 2;
    protected $excelSheetName = "";
    protected $excelDescription = "";

    protected $ImportFunSubFore = "fore_import_";
    protected $ImportFunSubValid = "import_format_";
    protected $ImportValidClassName = "";
    public static function ImportPrepare(PrepareClass &$prepareClass, $updateFields, &$error){}

    protected $OutputFunSubFore = "fore_output_";
    protected $OutputFunSubValid = "output_format_";
    protected $OutputValidClassName = "";
    public static function OutputPrepare(PrepareClass &$prepareClass, $updateFields, &$error){}
    public static function OutputWhere($params, Builder &$query, MreClass $mre){}

    public static function GetSourceRows($params, ExcelClass $excelClass)
    {
        if(empty($params)){
            return [];
        }

        $model = $excelClass->GetMre()->GetModel();
        $query = $model::query();
        static::OutputWhere($params, $query, $excelClass->GetMre());
        return $query->get()->toArray();
    }

    public static function GetExcelSupportHeader()
    {
        /**
         * @var $s self
         */
        $s = self::GetInstance();
        return $s->excelSupportHeader;
    }

    /**
     * @return ExcelClass
     */
    public static function GetCommonExcelClass()
    {
        /**
         * @var $s self
         */
        $s = self::GetInstance();

        $mreMark = MreClass::CreateInstance($s->dbModelName, $s->pk);
        $excelClass = new ExcelClass($mreMark);

        // 配置Excel导入导出
        $excelClass->SetExcelSupportHeader($s->excelSupportHeader);
        $excelClass->SetExcelHeaderRowNum($s->excelHeaderRowNum);
        $excelClass->SetExcelSheetName($s->excelSheetName);
        $excelClass->SetExcelDescription($s->excelDescription);
        $excelClass->SetExcelHeaderStyle($s->excelHeaderStyle);

        $excelClass->GetMre()->GetErrorObj()->SetFieldsCn($s->excelSupportHeader);
        return $excelClass;
    }

    /**
     * @return ExcelClass
     */
    public static function GetImportExcelClass()
    {
        /**
         * @var $s self
         */
        $s = self::GetInstance();

        $excelClass = self::GetCommonExcelClass();
        $excelClass->FunSubValid = $s->ImportFunSubValid;
        $excelClass->FunSubFore = $s->ImportFunSubFore;
        if($s->ImportValidClassName){
            $excelClass->ValidClassName = $s->ImportValidClassName;
        }else{
            $excelClass->ValidClassName = get_called_class();
        }
        $excelClass->PrepareFun = [get_called_class(), "ImportPrepare"];

        return $excelClass;
    }

    /**
     * @param $params
     * @param $fields
     * @return ExcelClass
     */
    public static function GetOutputExcelClass($params, $fields = true)
    {
        $excelClass = self::GetEmptyOutputExcelClass();

        $rows = [];
        if(!empty($params)){
            $rows = static::GetSourceRows($params, $excelClass);
        }
        if($fields === true){
            $fields = array_keys(self::GetExcelSupportHeader());
        }
        foreach ($rows as $row) {
            $excelClass->GetMre()->GetRowsObj()->AppendRow($row, $fields);
        }
        if(!empty($rows)){
            $excelClass->DoValidateRows();
        }

        return $excelClass;
    }

    public static function GetEmptyOutputExcelClass()
    {
        /**
         * @var $s self
         */
        $s = self::GetInstance();

        $excelClass = self::GetCommonExcelClass();
        $excelClass->SetExcelHeader(array_keys($s->excelSupportHeader));

        $excelClass->FunSubValid = $s->OutputFunSubValid;
        $excelClass->FunSubFore = $s->OutputFunSubFore;
        if($s->ImportValidClassName){
            $excelClass->ValidClassName = $s->OutputValidClassName;
        }else{
            $excelClass->ValidClassName = get_called_class();
        }
        $excelClass->PrepareFun = [get_called_class(), "OutputPrepare"];

        return $excelClass;
    }


    protected $CheckPkUnique = true;

    /**
     * @param SaverClass $saver
     * @param bool $DbTransaction
     * @return ExcelClass
     * @throws Exception
     */
    public static function ImportAndSave(SaverClass $saver, $DbTransaction = false)
    {
        /**
         * @var $s self
         */
        $s = self::GetInstance();

        $file = $_FILES['file'];
        $importExcelClass = self::GetImportExcelClass();

        $excelClassRows = [$importExcelClass];
        $ok = ExcelClass::ImportToExcelClass($file, $excelClassRows, $err);
        if(empty($importExcelClass->GetFirstError()) && !empty($err)){
            $importExcelClass->GetMre()->AddError(0, "","", $err);
        }
        if(!$ok){
            return $importExcelClass;
        }

        $ok = $importExcelClass->DoValidateRows();
        if(!$ok){
            return $importExcelClass;
        }

        if($s->CheckPkUnique){
            $pkVMap = [];
            $m = $importExcelClass->GetMre()->GetModelObj();
            foreach ($importExcelClass->GetMre()->GetRows() as $index=>$row) {
                $pk = $m->GetPkStr($row);
                $pkV = $m->GetPkValue($row);
                if(empty($pkV)){
                    continue;
                }
                if(isset($pkVMap[$pkV])){
                    $importExcelClass->GetMre()->AddError($index, $pk, $pkV, "{$pk}：{$pkV}, 唯一值不能重复");
                    $ok = false;
                    continue;
                }
                $pkVMap[$pkV] = $index;
            }
            if(!$ok){
                return $importExcelClass;
            }
        }

        $saver->SetMreMark($importExcelClass->GetMreMark());
        if(!$saver->DoValidateRows()){
            return $importExcelClass;
        }

        $saver->DoSave($DbTransaction);

        return $importExcelClass;
    }

}