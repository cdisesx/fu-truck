<?php
namespace fuTruck\Business;

use fuPdo\mysql\Error;
use fuPdo\mysql\Searcher;
use fuPdo\mysql\Where;
use fuTruck\Package\MreClass;
use fuTruck\Package\SourceClass;
use fuPdo\mysql\Builder;

class SourceBusiness extends SingleBusiness
{

    protected $dbModelName = "";
    protected $pk = ["id"];
    protected $getCount = true;
    public static $whereRule = [];

    /**
     * @return SourceClass
     */
    public static function GetSource()
    {
        /**
         * @var $s self
         */
        $s = self::GetInstance();

        $mreMark = MreClass::CreateInstance($s->dbModelName, $s->pk);
        $sourceClass = new SourceClass($mreMark);
        $sourceClass->isGetCount($s->getCount);
        return $sourceClass;
    }

    public static function PageWhere($params, Builder &$query, MreClass $mre){}

    public static function PageMngRows($params, &$row){}

    public static function PageOrderBy($params, Builder &$query)
    {
        if(!empty(@$params['order_by'])){
            $orderBys = @$params['order_by'];
            if(is_string($orderBys)){
                $orderBys = explode(',', $params['order_by']);
            }
            foreach ($orderBys as $orderBy) {
                if(empty($orderBy)){
                    continue;
                }
                if(is_array($orderBy)){
                    $orderBy = join(' ', $orderBy);
                }
                if(is_string($orderBy)){
                    $query->orderBy($orderBy);
                }
            }
        }
    }

    public static function GetPage($params)
    {
        $sourceClass = self::GetSource();
        $sourceClass->registerFun(SourceClass::FunPageWhere, [get_called_class(), "PageWhere"]);
        $sourceClass->registerFun(SourceClass::FunPageMngRows, [get_called_class(), "PageMngRows"]);
        $sourceClass->registerFun(SourceClass::FunPageOrderBy, [get_called_class(), "PageOrderBy"]);
        return $sourceClass->getPage($params);
    }

    public static function GetList($params)
    {
        $sourceClass = self::GetSource();
        $sourceClass->registerFun(SourceClass::FunPageWhere, [get_called_class(), "PageWhere"]);
        $sourceClass->registerFun(SourceClass::FunPageMngRows, [get_called_class(), "PageMngRows"]);
        $sourceClass->registerFun(SourceClass::FunPageOrderBy, [get_called_class(), "PageOrderBy"]);
        return $sourceClass->getList($params);
    }

    public static function PageTotalRowQuery($params, Builder &$query, MreClass $mre){}

    public static function GetPageTotalRow($params)
    {
        $sourceClass = self::GetSource();
        $sourceClass->registerFun(SourceClass::FunPageWhere, [get_called_class(), "PageWhere"]);
        $sourceClass->registerFun(SourceClass::FunTotalRowQuery, [get_called_class(), "PageTotalRowQuery"]);
        return $sourceClass->getPageTotalRow($params);
    }

    public static function GetByKey($row)
    {
        $sourceClass = self::GetSource();
        return $sourceClass->getOneByPk($row);
    }

    public static function GetRow($params)
    {
        $sourceClass = self::GetSource();

        /**
         * @var Error $error
         */
        $error = null;
        $where = Searcher::GetWhereByRule($params, self::$whereRule, $error);
        if($error != null){
            $sourceClass->getMre()->appendStrError($error->getErrorMessage());
            return [];
        }
        return $sourceClass->getRow($where);
    }
}