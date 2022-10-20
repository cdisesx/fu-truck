<?php
namespace fuTruck\Piece;

class Error extends \fuPdo\mysql\Error
{
    /**
     * @var array []{"index":"", "field":"", "value":"", "error":""}
     */
    protected $errors = [];

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    public function setErrors($errors)
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
    public function setFieldsCn(array $fieldsCn)
    {
        $this->fieldsCn = $fieldsCn;
    }

    /**
     * @return array
     */
    public function getFieldsCn()
    {
        return $this->fieldsCn;
    }

    /**
     * @param $index int
     * @param $field string
     * @param $value mixed
     * @param $error string
     */
    public function addError(int $index, string $field, $value, string $error)
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
    public function addErrors($errorRows)
    {
        foreach ($errorRows as $errorRow) {
            $this->addError($errorRow['index'], $errorRow['field'], $errorRow['value'], $errorRow['error']);
        }
    }
}