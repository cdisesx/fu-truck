<?php
namespace fuTruck\Package;

use fuTruck\One\FunRegister;
use fuTruck\One\MreMark;
use fuPdo\mysql\Builder;

class SourceClass
{
    use FunRegister;
    use MreMark;

    protected $getCount = true;
    public function IsGetCount($getCount)
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
            $query->OrderBy($params['order_by']);
            return ;
        }
    }

    public function GetPage($params)
    {
        $page = $params['page'];
        $limit = $params['limit'];
        $result = [];

        $mre = $this->GetMre();
        $query = $mre->GetModel()::Builder();

        forward_static_call_array($this->pageWhereFun, [$params, &$query, $mre]);

        if($this->getCount){
            $result["count"] = $query->Count();
        }

        forward_static_call_array($this->pageOrderByFun, [$params, &$query]);

        $rows = $query->Page($page, $limit)->Select();

        forward_static_call_array($this->pageMngRowsFun, [$params, &$rows]);

        $result["list"] = $rows;
        return $result;
    }

    public function GetList($params)
    {
        $mre = $this->GetMre();
        $query = $mre->GetModel()::Builder();
        forward_static_call_array($this->pageWhereFun, [$params, &$query, $mre]);
        forward_static_call_array($this->pageOrderByFun, [$params, &$query]);
        $rows = $query->Select();
        forward_static_call_array($this->pageMngRowsFun, [$params, &$rows]);
        return $rows;
    }

    /**
     * 常用于报表列表，计算合计的那一行
     * @param $params
     * @param Builder $query
     * @param MreClass $mre
     */
    public function DefaultTotalRowQuery($params, Builder &$query,MreClass $mre){}

    /**
     * 常用于报表列表，计算合计的那一行
     * @param $params
     * @return array
     */
    public function GetPageTotalRow($params)
    {
        $mre = $this->GetMre();
        $query = $mre->GetModel()::Builder();
        call_user_func_array($this->pageWhereFun, [$params, &$query, $mre]);
        call_user_func_array($this->totalRowQueryFun, [$params, &$query, $mre]);
        return $query->Select();
    }

    public function GetRow($where)
    {
        $row = [];
        if(!empty($where)){
            $mre = $this->GetMre();
            $query = $mre->GetModel()::Builder();
            $err = "";
            $ok =  $mre->GetModelObj()->SetPkWhere($query, [$where], $err);
            if(!$ok){
                $mre->AddError(1, "", "", $err);
            }

            $row = $query->One();
            if(!$row){
                return [];
            }
        }
        return $row;
    }
}