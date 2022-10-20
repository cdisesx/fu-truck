<?php
namespace fuTruck\Package;

use fuPdo\mysql\Where;
use fuTruck\One\FunRegister;
use fuTruck\One\MreMark;
use fuPdo\mysql\Builder;

class SourceClass
{
    use FunRegister;
    use MreMark;

    protected $getCount = true;
    public function isGetCount($getCount)
    {
        $this->getCount = $getCount;
    }

    const FunPageWhere = "pageWhereFun";
    const FunPageMngRows = "pageMngRowsFun";
    const FunPageOrderBy = "pageOrderByFun";
    const FunTotalRowQuery = "totalRowQueryFun";
    public static $FunList = [
        self::FunPageWhere,
        self::FunPageMngRows,
        self::FunPageOrderBy,
        self::FunTotalRowQuery,
    ];

    protected $pageWhereFun = ["fuTruck\Package\SourceClass", "DefaultPageWhere"];
    protected $pageMngRowsFun = ["fuTruck\Package\SourceClass", "DefaultPageMngRows"];
    protected $pageOrderByFun = ["fuTruck\Package\SourceClass", "DefaultPagePageOrderBy"];

    protected $totalRowQueryFun = ["fuTruck\Package\SourceClass", "DefaultTotalRowQuery"];

    public function __construct($mreMark)
    {
        $this->mreMark = $mreMark;
    }

    public static function DefaultPageWhere($params, Builder &$query, MreClass $mre){}
    public static function DefaultPageMngRows($params, &$row){}
    public static function DefaultPagePageOrderBy($params, Builder &$query)
    {
        if(isset($params['order_by'])){
            $query->orderBy($params['order_by']);
            return ;
        }
    }

    public function getPage($params)
    {
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 20;
        $result = [];

        $mre = $this->getMre();
        $query = $mre->getModel()::Builder();

        forward_static_call_array($this->pageWhereFun, [$params, &$query, $mre]);

        if($this->getCount){
            $result["count"] = $query->count();
        }

        forward_static_call_array($this->pageOrderByFun, [$params, &$query]);

        $rows = $query->page($page, $limit)->select();

        forward_static_call_array($this->pageMngRowsFun, [$params, &$rows]);

        $result["list"] = $rows;
        return $result;
    }

    public function getList($params)
    {
        $mre = $this->getMre();
        $query = $mre->getModel()::Builder();
        forward_static_call_array($this->pageWhereFun, [$params, &$query, $mre]);
        forward_static_call_array($this->pageOrderByFun, [$params, &$query]);
        $rows = $query->select();
        forward_static_call_array($this->pageMngRowsFun, [$params, &$rows]);
        return $rows;
    }

    /**
     * 常用于报表列表，计算合计的那一行
     * @param $params
     * @param Builder $query
     * @param MreClass $mre
     */
    public function defaultTotalRowQuery($params, Builder &$query,MreClass $mre){}

    /**
     * 常用于报表列表，计算合计的那一行
     * @param $params
     * @return array
     */
    public function getPageTotalRow($params)
    {
        $mre = $this->getMre();
        $query = $mre->getModel()::Builder();
        call_user_func_array($this->pageWhereFun, [$params, &$query, $mre]);
        call_user_func_array($this->totalRowQueryFun, [$params, &$query, $mre]);
        return $query->select();
    }

    public function getRow(Where $where)
    {
        $row = [];
        if(!$where->emptyReturn && !$where->isEmptyWhere()){
            $mre = $this->getMre();
            $query = $mre->getModel()::Builder();
            $query->whereMerge($where);
            $row = $query->find();
            if(!$row){
                return [];
            }
        }
        return $row;
    }

    public function getOneByPk($row)
    {
        $row = [];
        if(!empty($row)){
            $mre = $this->getMre();
            $query = $mre->getModel()::Builder();
            $err = "";
            $ok =  $mre->getModelObj()->setPkWhere($query, [$row], $err);
            if(!$ok){
                $mre->addError(1, "", "", $err);
            }

            $row = $query->find();
            if(!$row){
                return [];
            }
        }
        return $row;
    }
}