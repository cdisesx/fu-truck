<?php
namespace fuTruck\Package;

use App\Library\Common\MYExcel;
use fuTruck\One\FunRegister;
use fuTruck\One\MreMark;
use fuTruck\One\PrepareValidate;
use Exception;
use PHPExcel_Exception;
use PHPExcel_Worksheet;
use PHPExcel_Writer_Exception;

class ExcelClass
{
    use FunRegister;
    use MreMark;
    use PrepareValidate;

    public function __construct($mreMark)
    {
        $this->mreMark = $mreMark;
    }

    /**
     * 支持的Header
     * @var array map[en_name]cn_name
     */
    protected $excelSupportHeader = [];

    public function SetExcelSupportHeader($excelSupportHeader)
    {
        $this->excelSupportHeader = $excelSupportHeader;
    }

    public function GetExcelSupportHeader()
    {
        return $this->excelSupportHeader;
    }

    /**
     * HeaderStyle
     * @var array ["headers"=>[ $headerEn, ...], "css"=>[ExcelCssObj]]
     */
    protected $excelHeaderStyle = [];

    public function SetExcelHeaderStyle($style)
    {
        $this->excelHeaderStyle = $style;
    }

    public function GetExcelHeaderStyle()
    {
        return $this->excelHeaderStyle;
    }

    public function GetExcelHeaderStyleMap()
    {
        $mngMap = [];
        foreach ($this->excelHeaderStyle as $row) {
            foreach ($row['headers'] as $header) {
                $mngMap[$header] = $row['css'];
            }
        }
        return $mngMap;
    }

    /**
     * DataStyle
     * @var array [  "$key"=>["$headerEn" => [ExcelCssObj]],  ]
     */
    protected $excelHeaderDataStyle = [];

    public function SetExcelDataStyle($style)
    {
        $this->excelHeaderDataStyle = $style;
    }

    public function GetExcelDataStyle()
    {
        return $this->excelHeaderDataStyle;
    }

    /**
     * 本次要导入或导出的Header
     * 注意：这里数组建值为Excel中的列数
     * @var array [index]en_name
     */
    protected $excelHeader = [];

    public function SetExcelHeader($excelHeader)
    {
        $this->excelHeader = $excelHeader;
    }

    public function GetExcelHeader()
    {
        return $this->excelHeader;
    }

    /**
     * Header所在行，默认第2行，第1行为Description
     * @var int
     */
    protected $excelHeaderRowNum = 2;

    public function SetExcelHeaderRowNum($excelHeaderRowNum)
    {
        $this->excelHeaderRowNum = $excelHeaderRowNum;
    }

    /**
     * 所在SheetName
     * @var string
     */
    protected $excelSheetName = "";

    public function SetExcelSheetName($excelSheetName)
    {
        $this->excelSheetName = $excelSheetName;
    }

    /**
     * 描述
     * @var string
     */
    protected $excelDescription = "";

    public function SetExcelDescription($excelDescription)
    {
        $this->excelDescription = $excelDescription;
    }

    /**
     * 直接导出
     * @param $user
     * @param $fileName
     * @throws Exception
     */
    public function Output($user, $fileName)
    {
        ini_set('memory_limit','2048M');
        set_time_limit(300);
        self::outPutMultipleSheetFile($fileName, $user, [$this->GetOutputOption()]);
    }

    /**
     * @param $file_name
     * @param $user
     * @param $sheets = [ { sheet_name, header, data, before_header, header_style } ]
     * @param string $file_path
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Writer_Exception
     */
    public static function OutPutMultipleSheetFile($file_name, $user, $sheets,$file_path = "php://output")
    {
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()->setCreator($user);
        $objPHPExcel->removeSheetByIndex($objPHPExcel->getActiveSheetIndex());

        foreach ($sheets as $k=>$sheet) {
            if(empty($sheet['sheet_name'])){
                $sheet['sheet_name'] = "sheet".$k;
            }
            if(!isset($sheet['header'])){
                $sheet['header'] = [];
            }
            if(!isset($sheet['data'])){
                $sheet['data'] = [];
            }
            if(!isset($sheet['before_header'])){
                $sheet['before_header'] = [];
            }
            if(!isset($sheet['header_style'])){
                $sheet['header_style'] = [];
            }
            if(!isset($sheet['style'])){
                $sheet['style'] = [];
            }
            $workSheet = new PHPExcel_Worksheet($objPHPExcel, $sheet['sheet_name']);
            $objPHPExcel->addSheet($workSheet);
            self::initSheet($workSheet, $sheet['header'], $sheet['data'], @$sheet['before_header'], @$sheet['header_style'],@$sheet["style"]);
        }

        $objPHPExcel->setActiveSheetIndex(0);

        if ($file_path == "php://output") {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $file_name . '_' . date('YmdHis') . '.xls"');
            header('Cache-Control: max-age=0');
        }
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
        $objWriter->save($file_path);
        if ($file_path == "php://output") {
            exit;
        }
    }

    /**
     * @param PHPExcel_Worksheet $cell
     * @param $header
     * @param $data
     * @param array $before_header
     * @param array $header_style
     * @param array $style
     * @throws PHPExcel_Exception
     */
    public static function initSheet(PHPExcel_Worksheet &$cell, $header, $data, $before_header = [], $header_style = [], $style = [])
    {
        $i = 1;

        if(!empty($before_header)){
            foreach (array_values($before_header) as $rows) {
                foreach (array_values($rows) as $k=>$row) {
                    $title_sign = self::cell_index($k).($i);
                    $cell->setCellValue($title_sign, $row);
                }
                $i++;
            }
        }

        foreach (array_values($header) as $k=>$item) {
            $line_sign = self::cell_index($k);
            $title_sign = $line_sign.($i);
            $cell->getColumnDimension($line_sign)->setWidth(15);

            $baseCellStyle = [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER]
            ];
            if(!empty($header_style[$item]) && is_array($header_style[$item])){
                $baseCellStyle = array_merge($baseCellStyle, $header_style[$item]);
            }
            $cell->getStyle($title_sign)->applyFromArray($baseCellStyle);
            $cell->setCellValue($title_sign, $item);
        }
        $i++;

        if (!empty($data)){
            foreach ($data as $key => $row) {
                foreach (array_keys($header) as $k=>$_name) {
                    $title_sign = self::cell_index($k).($i);
                    if (isset($style[$key]) &&
                        is_array($style[$key]) &&
                        isset($style[$key][$_name]) &&
                        is_array($style[$key][$_name]) &&
                        !empty($style[$key][$_name])) {
                        $cell->getStyle($title_sign)->applyFromArray($style[$key][$_name]);
                    }
                    $cell->setCellValue($title_sign, @$row[$_name]);
                }
                $i++;
            }
        }
    }

    protected static function cell_index($index)
    {
        $mod = $index % 26;
        $count = floor($index / 26);
        $str = chr($mod + 65);
        if ($count > 0) {
            $str = self::cell_index($count - 1) . $str;
        }
        return $str;
    }


    /**
     * 生成要导出的配置和数据
     * @return array
     */
    public function GetOutputOption()
    {
        $allHeaderStyleMap = $this->GetExcelHeaderStyleMap();

        $headerMap = $headerStyle = [];
        foreach ($this->excelHeader as $k=>$headerEn) {
            $headerMap[$headerEn] = "";
            if(isset($this->excelSupportHeader[$headerEn])){
                $headerMap[$headerEn] = $this->excelSupportHeader[$headerEn];

                if(isset($allHeaderStyleMap[$headerEn])){
                    $headerStyle[$this->excelSupportHeader[$headerEn]] = $allHeaderStyleMap[$headerEn];
                }
            }
        }

        $mre = $this->GetMre();
        $beforeHeader = [];
        if(!empty($this->excelDescription)){
            if(is_array($this->excelDescription)){
                $beforeHeader = $this->excelDescription;
            }
            if(is_string($this->excelDescription)){
                $beforeHeader = [[$this->excelDescription]];
            }
        }


        return [
            "sheet_name"=>$this->excelSheetName,
            "header"=>$headerMap,
            "data"=>$mre->GetRowsInFields($this->excelHeader),
            "before_header"=>$beforeHeader,
            "header_style"=>$headerStyle,
            "style"=>$this->excelHeaderDataStyle,
        ];
    }


    /**
     * 直接导入
     * @param $error
     * @return bool | array
     * @throws Exception
     */
    public function Import(&$error)
    {
        $file = self::GetFile($error);
        if(!empty($error)){
            return false;
        }

        $truckExcels = [&$this];
        if(!self::InitImportSheets($file['tmp_name'], $truckExcels, $error)){
            return false;
        }

        if(!$this->ReadSheet($error)){
            return false;
        }

        return $this->ExcelRows;
    }

    public function GetExcelErrors()
    {
        $errObj = $this->GetMre()->GetErrorObj();
        $errors = [];
        foreach ($errObj->GetErrors() as $error) {
            if(@$error['index'] > 0){
                $errors[] = "第{$error['index']}行错误：{$error['error']}";
            }else{
                $errors[] = $error['error'];
            }
        }
        return join("<br>", $errors);
    }

    /**
     * 获取File
     * @param $error
     * @return bool|mixed
     */
    public static function GetFile(&$error)
    {
        if (empty($_FILES) || !isset($_FILES["file"])) {
            $error = "请选择上传的文件";
            return false;
        }
        $file = $_FILES["file"];
        if ($file["error"] != UPLOAD_ERR_OK) {
            $error = "文件上传失败";
            return false;
        }

        $file_extension = strtolower(pathinfo($file["name"],PATHINFO_EXTENSION));
        if (!in_array($file_extension,["xls"])) {
            $error = "请上传所需的excel(.xls)文件";
        }
        return $file;
    }

    /**
     * 静态方法，往TruckExcels载入Sheet
     * 注意：需要传入的truckExcels是一个TruckExcel实例List
     * @param $file array
     * @param $excelClassList
     * @param $error
     * @return array|bool
     * @throws Exception
     */
    public static function ImportToExcelClass(array $file, &$excelClassList, &$error)
    {
        if(empty($file)){
            $error = "获取不到导入文件";
            return false;
        }

        $filePath = @$file["tmp_name"];
        if(!is_file($filePath)){
            $error = "获取不到导入文件";
            return false;
        }

        $objPHPExcel = \PHPExcel_IOFactory::load($filePath);

        /**
         * @var $excelClass ExcelClass
         */
        foreach ($excelClassList as &$excelClass) {
            $mre = $excelClass->GetMre();

            $sheetName = @$excelClass->excelSheetName;
            if(empty($sheetName)){
                $error = "请配置SheetName";
                $mre->AddError(0, "", "", $error);
                return false;
            }

            $sheet = null;
            $sheet = $objPHPExcel->getSheetByName($sheetName);
            if(empty($sheet)){
                $error = "获取不到表格SheetName:{$sheetName}";
                $mre->AddError(0, "", "", $error);
                return false;
            }

            foreach ($sheet->getRowIterator() as $h=>$row) {

                // 判断空行
                $c = $row->getCellIterator();
                $c->setIterateOnlyExistingCells(false);
                $empty_row = true;
                foreach ($c as $k=>$unit) {
                    if($unit->getValue()){
                        $empty_row = false;
                        break;
                    }
                }

                // 过滤小于头部的数据
                if($h < $excelClass->excelHeaderRowNum){
                    continue;
                }

                if($empty_row){
                    if($h == $excelClass->excelHeaderRowNum){
                        $error = "取不到你的头部信息";
                        $mre->AddError(0, "", "", $error);
                        return false;
                    }
                    continue;
                }

                // key、value翻转
                $supportHeaderFlip = array_flip($excelClass->excelSupportHeader);

                $appendRow = [];
                // 从头部行，往下取值
                foreach ($c as $k=>$unit) {
                    $v = trim($unit->getValue());
                    if($h == $excelClass->excelHeaderRowNum){
                        if(empty($v)){
                            continue;
                        }
                        if(array_key_exists($v, $supportHeaderFlip)){
                            $excelClass->excelHeader[$k] = $supportHeaderFlip[$v];
                        }else{
                            $error = "{$excelClass->excelSheetName}表格不支持导入Header：{$v}";
                            $mre->AddError(0, "", "", $error);
                            return false;
                        }
                    }else{
                        if(isset($excelClass->excelHeader[$k])){
                            $appendRow[$excelClass->excelHeader[$k]] = $v;
                        }
                    }
                }
                if($h > $excelClass->excelHeaderRowNum){
                    $mre->GetRowsObj()->AppendRow($appendRow, $excelClass->excelHeader, $h);
                }
            }
        }

        return true;
    }

    public function DoValidateRows($isOutput=false)
    {
        $validate = $this->GetValidate();
        $ok = $validate->ValidateRows();
        if(!$ok){
            return false;
        }

        $rows = $this->GetMre()->GetRows();
        foreach ($rows as $i=>$row) {
            foreach ($this->GetExcelHeader() as $h) {
                if(isset($row[$h]) && is_array($row[$h]) && $isOutput){
                    $this->GetMre()->GetErrorObj()->AddError($i, $h, $row[$h], "{$h}字段导出时为数组，请处理成字符串");
                    return false;
                }
            }
        }

        return true;
    }
}