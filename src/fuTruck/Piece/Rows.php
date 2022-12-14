<?php
namespace fuTruck\Piece;

class Rows
{
    /**
     * 保存的数据列表
     * @var array [index]SaveObj
     */
    protected $rows = [];

    /**
     * 要更新的字段名称
     * @var array [index][]string
     */
    protected $updateFieldsList = [];

    /**
     * @param $row
     * @param array|bool $updateFields
     * @param int|bool $index
     */
    public function appendRow($row, $updateFields, $index = false)
    {
        if($index === false){
            $index = count($this->rows);
        }else{
            $index = intval($index);
        }
        $this->rows[$index] = $row;

        if($updateFields){
            $this->updateFieldsList[$index] = $updateFields;
        }
    }

    /**
     * @param int|bool $index
     * @param $row
     */
    public function setRow($index, $row)
    {
        $this->rows[$index] = $row;
    }

    public function removeRow($index)
    {
        unset($this->rows[$index]);
        unset($this->updateFieldsList[$index]);
    }

    public function setRows($rows, $updateFields)
    {
        $this->rows = $rows;
        $c = count($this->rows);
        $this->updateFieldsList = [];
        for ($i = 0; $i<$c; $i++){
            $this->updateFieldsList[$i] = $updateFields;
        }

    }

    /**
     * @return array
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * @return array
     */
    public function getUpdateFieldsList()
    {
        return $this->updateFieldsList;
    }

    /**
     * @param $index int
     * @return array
     */
    public function getUpdateFields(int $index)
    {
        return $this->updateFieldsList[$index] ?? [];
    }

    public function setUpdateFields($index, $updateFields)
    {
        $this->updateFieldsList[$index] = $updateFields;
    }

    /**
     * 添加要更新的字段名称
     * @param $index
     * @param array $fieldNames
     */
    public function appendUpdateField($index, Array $fieldNames)
    {
        $this->updateFieldsList[$index] = array_merge($this->updateFieldsList[$index], $fieldNames);
        $this->updateFieldsList[$index] = array_unique($this->updateFieldsList[$index]);
    }

    /**
     * 根据UpdateFields获取要保存的数据
     * @param $row
     * @param $updateFields
     * @return array
     */
    public function getSaveData($row, $updateFields)
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
}