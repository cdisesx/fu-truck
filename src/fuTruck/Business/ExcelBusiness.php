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

        $model = $excelClass->getMre()->getModel();
        $query = $model::query();
        static::OutputWhere($params, $query, $excelClass->getMre());
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
        $excelClass->setExcelSupportHeader($s->excelSupportHeader);
        $excelClass->setExcelHeaderRowNum($s->excelHeaderRowNum);
        $excelClass->setExcelSheetName($s->excelSheetName);
        $excelClass->setExcelDescription($s->excelDescription);
        $excelClass->setExcelHeaderStyle($s->excelHeaderStyle);

        $excelClass->getMre()->getErrorObj()->setFieldsCn($s->excelSupportHeader);
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
        $excelClass->funSubValid = $s->ImportFunSubValid;
        $excelClass->funSubFore = $s->ImportFunSubFore;
        if($s->ImportValidClassName){
            $excelClass->validClassName = $s->ImportValidClassName;
        }else{
            $excelClass->validClassName = get_called_class();
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
            $excelClass->getMre()->getRowsObj()->appendRow($row, $fields);
        }
        if(!empty($rows)){
            $excelClass->doValidateRows();
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
        $excelClass->setExcelHeader(array_keys($s->excelSupportHeader));

        $excelClass->funSubValid = $s->OutputFunSubValid;
        $excelClass->funSubFore = $s->OutputFunSubFore;
        if($s->ImportValidClassName){
            $excelClass->validClassName = $s->OutputValidClassName;
        }else{
            $excelClass->validClassName = get_called_class();
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
        if(empty($importExcelClass->getFirstError()) && !empty($err)){
            $importExcelClass->getMre()->addError(0, "","", $err);
        }
        if(!$ok){
            return $importExcelClass;
        }

        $ok = $importExcelClass->doValidateRows();
        if(!$ok){
            return $importExcelClass;
        }

        if($s->CheckPkUnique){
            $pkVMap = [];
            $m = $importExcelClass->getMre()->getModelObj();
            foreach ($importExcelClass->getMre()->getRows() as $index=>$row) {
                $pk = $m->getPkStr($row);
                $pkV = $m->getPkValueStr($row);
                if(empty($pkV)){
                    continue;
                }
                if(isset($pkVMap[$pkV])){
                    $importExcelClass->getMre()->addError($index, $pk, $pkV, "{$pk}：{$pkV}, 唯一值不能重复");
                    $ok = false;
                    continue;
                }
                $pkVMap[$pkV] = $index;
            }
            if(!$ok){
                return $importExcelClass;
            }
        }

        $saver->setMreMark($importExcelClass->getMreMark());
        if(!$saver->doValidateRows()){
            return $importExcelClass;
        }

        $saver->doSave($DbTransaction);

        return $importExcelClass;
    }

}