<?php
//載入Composer套件
//include "/home/bill/www/config/config_inc.php";
include __DIR__."/../config/config_inc.php";

//路由套件:https://github.com/nikic/FastRoute
$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {

    //管理員
    $r->addGroup('/admin', function (FastRoute\RouteCollector $r) {
        //不需驗證Token
        $r->addRoute('POST', '/login', 'ControllerAdminLogin@login');//登入
        //需要Token-通用欄位
        $r->addRoute('POST', '/column', 'ControllerAdmin@readColumn');
        $r->addRoute('PATCH', '/column', 'ControllerAdmin@updateColumn');
    });
    //使用者
    $r->addGroup('/user', function (FastRoute\RouteCollector $r) {
        //不需驗證Token
        $r->addRoute('POST', '/login', 'ControllerUserLogin@login');//登入
    });
    //前端不需要權限
    $r->addGroup('/api', function (FastRoute\RouteCollector $r) {

    });

});

//路由套件設定區 START
if(true){
    // 從$_SERVER取路徑(URI)和方法
    $httpMethod = $_SERVER['REQUEST_METHOD'];
    $uri = $_SERVER['REQUEST_URI'];
    // 去除query string(?foo=bar) and decode URI
    if (false !== $pos = strpos($uri, '?')) {
        $uri = substr($uri, 0, $pos);
    }
    $uri = rawurldecode($uri);
    $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
    //讓OPTIONS通過
    if($httpMethod=="OPTIONS"){
        header('Content-Type: application/json; charset=utf-8');
        header("Access-Control-Allow-Origin:*");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
        header('Access-Control-Allow-Methods: PUT, PATCH, POST, GET, DELETE, OPTIONS');
        $_JSON['code']=200;
        $_JSON['msg']="OK";
        echo json_encode($_JSON);
        exit();
    }
    //CORS
    header("Access-Control-Allow-Origin:*");
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
    header('Access-Control-Allow-Methods: PUT, PATCH, POST, GET, DELETE, OPTIONS');
    //HTTP Cache
    header('Cache-Control: max-age=5');
    //HTTP JSON
    header('Content-Type: application/json; charset=utf-8');
    //分配路由狀態
    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::NOT_FOUND:
            //當uri路徑找不到
            header('Content-Type: application/json; charset=utf-8');
            header('HTTP/1.1 404 Not Found');
            $_JSON['code']=404;
            $_JSON['msg']="404 Not Found";
            echo json_encode($_JSON);
            break;
        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            $allowedMethods = $routeInfo[1];
            // 當uri路徑找到，方法不對(GET POST PUT.....)
            header('Content-Type: application/json; charset=utf-8');
            header('HTTP/1.0 405 Method Not Allowed');
            $_JSON['code']=405;
            $_JSON['msg']="405 Method Not Allowed";
            echo json_encode($_JSON);
            break;
        case FastRoute\Dispatcher::FOUND:
            //路徑、方法都對了，執行Controller
            $handler = $routeInfo[1];
            $vars = $routeInfo[2];
            //自定義$handler 第一個參數是 string class@method 第二個之後是$vars
            list($class, $method) = explode('@',$handler,2);
            $obj = new $class();//類別進行物件化
            echo $obj->{$method}($vars);//傳入參數
            break;
    }
}
//路由套件設定區 END





