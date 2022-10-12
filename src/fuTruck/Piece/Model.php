<?php
namespace fuTruck\Piece;

use fuTruck\One\Pk;
use fuPdo\mysql\Model as fuPdoModel;

class Model
{
    use Pk;

    /**
     * DM模型
     * @var fuPdoModel
     */
    protected $dbModel;

    /**
     * DB模型名称
     * @var string
     */
    protected $dbModelName = "";

    public function __construct($dbModelName, $pk = ["id"])
    {
        $this->dbModelName = $dbModelName;
        $this->initModel();
        $this->pk = $pk;
    }

    /**
     * 创建DB模型
     */
    public function initModel()
    {
        $this->dbModel = new $this->dbModelName();
    }

    /**
     * @return fuPdoModel
     */
    public function GetModel()
    {
        if($this->dbModel == null){
            $this->initModel();
        }
        return $this->dbModel;
    }

    /**
     * @param array|bool $updateFields
     * @throws \Exception
     */
    public function FixFillFields(&$updateFields)
    {
        if(is_bool($updateFields) && $updateFields){
            if(!is_array($this->dbModel->SaveFields)){
                throw new \Exception("Truck Model Need set SaveFields");
            }else{
                $updateFields = $this->dbModel->SaveFields;
            }
        }
    }

    public function Begin()
    {
        $this->dbModel::Builder()->Begin();
    }

    public function Rollback()
    {
        $this->dbModel::Builder()->RollBack();
    }

    public function Commit()
    {
        $this->dbModel::Builder()->Commit();
    }
}