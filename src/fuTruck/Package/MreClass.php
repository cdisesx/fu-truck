<?php
namespace fuTruck\Package;

use fuPdo\mysql\Model as fuPdoModel;
use fuTruck\Piece\Error;
use fuTruck\Piece\Model;
use fuTruck\Piece\Rows;

class MreClass
{

    /**
     * DM模型
     * @var Model
     */
    protected $model;

    /**
     * @var Rows
     */
    protected $rows;

    /**
     * @var Error
     */
    protected $error;


    protected static $instances = [];

    /**
     * @param $MreMark
     * @return MreClass
     */
    public static function GetInstance($MreMark)
    {
        if(empty($MreMark)){
            return null;
        }

        return @self::$instances[$MreMark];
    }

    public static function CreateInstance($dbModelName = "", $pk = ["id"], $uidPrefix = "")
    {
        $model = new Model($dbModelName, $pk);;
        $rows = new Rows();
        $error = new Error();
        $instance = new self($model, $rows, $error);

        $uid = uniqid($uidPrefix);
        $mreMark = md5(json_encode([$dbModelName, $pk, $uid]));

        self::$instances[$mreMark] = &$instance;
        return $mreMark;
    }

    protected function __construct(Model &$m, Rows &$r, Error &$e = null)
    {
        if($m instanceof Model){
            $this->model = $m;
        }
        if($r instanceof  Rows){
            $this->rows = $r;
        }
        if($e === null){
            $this->error = new Error();
        } else if($e instanceof  Error){
            $this->error = $e;
        }
    }

    public function GetRowsObj()
    {
        return $this->rows;
    }

    public function GetModelObj()
    {
        return $this->model;
    }

    public function GetErrorObj()
    {
        return $this->error;
    }

    public function GetRows()
    {
        return $this->rows->GetRows();
    }

    public function GetRowsInFields($fields = true)
    {
        $rowsInFields = [];
        $rows = $this->GetRows();
        foreach ($this->GetRowsObj()->GetUpdateFieldsList() as $index=>$updateFields) {
            if($fields === true){
                $this->GetModelObj()->FixFillFields($updateFields);
            }else{
                $updateFields = $fields;
            }

            foreach ($updateFields as $updateField) {
                if(isset($rows[$index][$updateField])){
                    $rowsInFields[$index][$updateField] = $rows[$index][$updateField];
                }
            }
        }
        return $rowsInFields;
    }

    /**
     * @return fuPdoModel
     */
    public function GetModel()
    {
        return $this->model->GetModel();
    }

    public function GetErrors()
    {
        return $this->error->GetErrors();
    }

    public function AddError($index, $field, $value, $error)
    {
        $this->error->AddError($index, $field, $value, $error);
    }

}