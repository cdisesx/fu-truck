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
    public function getModel()
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
    public function fixFillFields(&$updateFields)
    {
        if(is_bool($updateFields) && $updateFields){
            if(!is_array($this->getModel()::GetSaveFields())){
                throw new \Exception("Truck Model Need set SaveFields");
            }else{
                $updateFields = $this->getModel()::GetSaveFields();
            }
        }
    }

    public function begin()
    {
        $this->dbModel::Builder()->begin();
    }

    public function rollBack()
    {
        $this->dbModel::Builder()->rollBack();
    }

    public function commit()
    {
        $this->dbModel::Builder()->commit();
    }
}