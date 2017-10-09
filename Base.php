<?php
    /*
    *      
    *          ┌─┐       ┌─┐
    *       ┌──┘ ┴───────┘ ┴──┐
    *       │                 │
    *       │       ───       │
    *       │  ─┬┘       └┬─  │
    *       │                 │
    *       │       ─┴─       │
    *       │                 │
    *       └───┐         ┌───┘
    *           │         │
    *           │         │
    *           │         │
    *           │         └──────────────┐
    *           │                        │
    *           │                        ├─┐
    *           │                        ┌─┘    
    *           │                        │
    *           └─┐  ┐  ┌───────┬──┐  ┌──┘         
    *             │ ─┤ ─┤       │ ─┤ ─┤         
    *             └──┴──┘       └──┴──┘ 
    *                 神兽保佑 
    *                 代码无BUG! 
    *  author：Earya
    *  creat_time: 2017/06/09
    *  Ide: NetBeans
    *  email:402481444@qq.com
    // +----------------------------------------------------------------------
    // | 重庆点博物联网管理平台
    // | 父级控制器 检测登陆、权限
    // +----------------------------------------------------------------------
    */
namespace app\admin\controller;
use think\Controller;
class Base extends Controller
{
    public function _initialize(){
        $this->check_login();
        $this->check_auth();
        $this->check_left();
    }
    
    /*
     * 根据角色加载导航
     */
    public function check_left(){
        $AuthModule =$_SESSION['think']['authority'];
        $Company = $_SESSION['think']['company'];
        $this->assign(array(
            'AuthModule'=>$AuthModule,
            'Company'=>$Company
        ));
    }
    /*
     * 检查是否登陆
     */
    public function check_login(){
        if(!session('user_account')){
            $this->error('请先登录系统！','Login/index');
        }
    }
    
    /*
     * @封装日志插件
     * @ $FielName 文件名
     * @ $Str 写入内容
     */
    public function write_log($FielName,$Str){
        \loger\MLoger::write($FielName,$Str);
    }
    
    
    /*
     *  获取IP
     */
     public function getClientIP() {
        if (getenv("HTTP_CLIENT_IP"))
            $ip = getenv("HTTP_CLIENT_IP");
        else if (getenv("HTTP_X_FORWARDED_FOR"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        else if (getenv("REMOTE_ADDR"))
            $ip = getenv("REMOTE_ADDR");
        else
            $ip = "Unknow";
        return $ip;
    }
    
    /*
     * 检查是否有权限
     */
    public function check_auth(){
        if($_SESSION['think']['authority'] != "1"){
        $auth_Msg=db('auth_user')->join('auth_route','auth_route.id = auth_user.route_id')->join('auth_module','auth_module.id = auth_route.module_id')->where('auth_user.auth_id',$_SESSION['think']['authority'])->select();
        foreach ($auth_Msg as $key =>$value){
        $auth_msg[$key]['route']=$value['route'];
        $auth_msg[$key]['route_name']=$value['route_name'];
        $auth_msg[$key]['module_id']=$value['module_id'];
        $auth_msg[$key]['module_name']=$value['module_name'];
        $auth_msg[$key]['auth_id']=$value['auth_id'];
        }
        
        foreach ($auth_msg as $key => $value) {
            $auth_data[$key]=strtolower( $value['route']);  
        } 
        $Request_Url=strtolower($_SERVER['PATH_INFO']);
        if(!in_array($Request_Url, $auth_data)){
            $this->error('对不起，该模块你没有权限！');
        }    
        }
    }
    
      /**
     * 查询数据并返回datatables格式数据
     * @param unknown $params 查询条件、字段
     */
    public function basis($params) {
        // 获取Datatables发送的参数 必要
        $draw = $_GET ['draw']; // 这个值作者会直接返回给前台
        // 排序
        $order_column = $_GET ['order'] ['0'] ['column']; // 那一列排序，从0开始
        $order_dir = $_GET ['order'] ['0'] ['dir']; // ase desc 升序或者降序
        $orderSql = "";
        if (isset($order_column)) {
            $i = intval($order_column);
            $orderSql = " order by " . $params ['Columns'] [$i] . " " . $order_dir;
        }
        // 搜索
        $search = $_GET ['search'] ['value']; // 获取前台传过来的过滤条件
        // 分页
        $start = $_GET ['start']; // 从多少开始
        $length = $_GET ['length']; // 数据长度
        $limitSql = '';
        $limitFlag = isset($_GET ['start']) && $length != - 1;
        if ($limitFlag) {
            $limitSql = " LIMIT " . intval($start) . ", " . intval($length);
        }
        // 定义查询数据总记录数sql
        $sumSql = "SELECT count(id) as sum FROM " . $params ['Table'];
        // 条件过滤后记录数 必要
        $recordsFiltered = 0;
        // 表的总记录数 必要
        $recordsTotal = 0;
        $recordsTotalResult = db("{$params ['Table']}")->query($sumSql);
        $recordsTotal = $recordsTotalResult[0] ['sum'];
        // 定义过滤条件查询过滤后的记录数sql
        $sumSqlWhere = " where sim_num LIKE '%" . $search . "%'";
        if (preg_match("/^\d*$/", $search)) {
            
        } else {//不符合搜索规则 不允许条件查询
            $search = "";
        }
        $conditions = ""; // 查询字段
        $condcount = sizeof($params ['Columns']);
        for ($a = 0; $a < $condcount; $a ++) {
            if ($a == ($condcount - 1)) {
                $conditions .= $params ['Columns'] [$a];
            } else {
                $conditions .= $params ['Columns'] [$a] . ",";
            }
        }
        $associated = ""; // 管理表查询
        if (!empty($params ['joins'])) {
            for ($a = 0; $a < sizeof($params ['joins']); $a ++) {
                $associated .= " JOIN " . $params ['joins'] [$a] ['table'] . " on " . $params ['joins'] [$a] ['conditions'] [0];
            }
        }
        // query data
        $totalResultSql = "SELECT " . $conditions . " FROM " . $params ['Table'] . $associated;
        $infos = array();
        if (strlen($search) > 0) {
            // 如果有搜索条件，按条件过滤找出记录
            echo $totalResultSql . $sumSqlWhere . $orderSql . $limitSql;
            echo "2222";
            $dataResult = db("{$params ['Table']}")->query($totalResultSql . $sumSqlWhere . $orderSql . $limitSql);
        } else {
            // 直接查询所有记录
            if (!empty($params ['conditions'])) {
                $sumSqlWhere = " where " . $params ['conditions'];
                    echo $totalResultSql . $sumSqlWhere . $orderSql . $limitSql;
                    echo "111111111";
                $dataResult = db("{$params ['Table']}")->query($totalResultSql . $sumSqlWhere . $orderSql . $limitSql);
                    echo "SELECT COUNT(*) as SUM FROM " . $params ['Table'] . $associated . $sumSqlWhere . $orderSql;
                $data = db("{$params ['Table']}")->query("SELECT COUNT(*) as SUM FROM " . $params ['Table'] . $associated . $sumSqlWhere . $orderSql);
            } else {
                $dataResult = db("{$params ['Table']}")->query($totalResultSql . $orderSql . $limitSql);
                $data = db("{$params ['Table']}")->query("SELECT COUNT(*) as SUM FROM " . $params ['Table'] . $associated . $orderSql);
            }
        }
        echo $totalResultSql . $sumSqlWhere . $orderSql . $limitSql;
        var_dump($dataResult);
        exit;
        // 计算查询数据条数 进行分页
        if (strlen($search) > 0) {
            $recordsFilteredResult = db("{$params ['Table']}")->query($sumSql . $sumSqlWhere);
            $recordsFiltered = $recordsFilteredResult [0] [0] ['sum'];
        } else {
            $recordsFiltered = $data[0][0]['SUM'];
        }
        return [
            'rows' => $dataResult,
            'output' => [
                "draw" => intval($draw),
                "recordsTotal" => intval($recordsTotal),
                "recordsFiltered" => intval($recordsFiltered),
                "data" => []
            ]
        ];
    }
}
