<?php
namespace fuTruck\Piece;

class Error
{
    /**
     * @var array []{"index":"", "field":"", "value":"", "error":""}
     */
    protected $errors = [];

    /**
     * @return array
     */
    public function GetErrors()
    {
        return $this->errors;
    }

    public function SetErrors($errors)
    {
        $this->errors = $errors;
    }

    /**
     * @var array
     */
    protected $fieldsCn = [];

    /**
     * @param $fieldsCn array ["name"=>"名称"]
     */
    public function SetFieldsCn(array $fieldsCn)
    {
        $this->fieldsCn = $fieldsCn;
    }

    /**
     * @return array
     */
    public function GetFieldsCn()
    {
        return $this->fieldsCn;
    }

    /**
     * @param $index int
     * @param $field string
     * @param $value mixed
     * @param $error string
     */
    public function AddError(int $index, string $field, $value, string $error)
    {
        $this->errors[] = [
            "index"=>$index,
            "field"=>$field,
            "value"=>$value,
            "error"=>$error,
        ];
    }

    /**
     * @param $errorRows []error
     */
    public function AddErrors($errorRows)
    {
        foreach ($errorRows as $errorRow) {
            $this->AddError($errorRow['index'], $errorRow['field'], $errorRow['value'], $errorRow['error']);
        }
    }
}