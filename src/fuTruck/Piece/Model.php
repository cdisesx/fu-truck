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
     */
    public function FixFillFields(&$updateFields)
    {
        if(is_bool($updateFields) && $updateFields){
            $updateFields = $this->dbModel->getFillable();
        }
    }

    public function Begin()
    {
        $this->dbModel->getConnection()->beginTransaction();
    }

    public function Rollback()
    {
        $this->dbModel->getConnection()->rollBack();
    }

    public function Commit()
    {
        $this->dbModel->getConnection()->commit();
    }
}