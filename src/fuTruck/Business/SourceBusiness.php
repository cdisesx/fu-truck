<?php
namespace fuTruck\Business;

use fuTruck\Package\MreClass;
use fuTruck\Package\SourceClass;
use fuPdo\mysql\Builder;

class SourceBusiness extends SingleBusiness
{

    protected $dbModelName = "";
    protected $pk = ["id"];
    protected $getCount = true;

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
        $sourceClass->IsGetCount($s->getCount);
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
                if(is_string($orderBy)){
                    $orderBy = explode(' ', $orderBy);
                }
                $query->orderBy($orderBy[0], $orderBy[1]);
            }
        }
    }

    public static function GetPage($params)
    {
        $sourceClass = self::GetSource();
        $sourceClass->RegisterFun(SourceClass::FunPageWhere, [get_called_class(), "PageWhere"]);
        $sourceClass->RegisterFun(SourceClass::FunPageMngRows, [get_called_class(), "PageMngRows"]);
        $sourceClass->RegisterFun(SourceClass::FunPageOrderBy, [get_called_class(), "PageOrderBy"]);
        return $sourceClass->GetPage($params);
    }

    public static function GetList($params)
    {
        $sourceClass = self::GetSource();
        $sourceClass->RegisterFun(SourceClass::FunPageWhere, [get_called_class(), "PageWhere"]);
        $sourceClass->RegisterFun(SourceClass::FunPageMngRows, [get_called_class(), "PageMngRows"]);
        $sourceClass->RegisterFun(SourceClass::FunPageOrderBy, [get_called_class(), "PageOrderBy"]);
        return $sourceClass->GetList($params);
    }

    public static function PageTotalRowQuery($params, Builder &$query, MreClass $mre){}

    public static function GetPageTotalRow($params)
    {
        $sourceClass = self::GetSource();
        $sourceClass->RegisterFun(SourceClass::FunPageWhere, [get_called_class(), "PageWhere"]);
        $sourceClass->RegisterFun(SourceClass::FunTotalRowQuery, [get_called_class(), "PageTotalRowQuery"]);
        return $sourceClass->GetPageTotalRow($params);
    }

    public static function GetItem($where)
    {
        $sourceClass = self::GetSource();
        return $sourceClass->GetRow($where);
    }
}