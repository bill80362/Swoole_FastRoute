<?php
//載入Composer套件
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

$http = new Swoole\Http\Server("bill.nctu.me", 9555);
printf("HTTP server 啟動 %s:%s\n", $http->host, $http->port);
$http->on('request', function ($request, $response) use($dispatcher) {
    //CORS
    $response->header("Access-Control-Allow-Origin","*");
    $response->header("Access-Control-Allow-Headers","Origin, X-Requested-With, Content-Type, Accept, Authorization");
    $response->header('Access-Control-Allow-Methods',' PUT, PATCH, POST, GET, DELETE, OPTIONS');
    //HTTP Cache
    $response->header('Cache-Control','max-age=5');
    $response->header("Content-Cache-Control", "no-store");
    //HTTP JSON
    $response->header("Content-Type", "application/json; charset=utf-8");
    //chrome會有的要求
    if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
        $response->end();
        return;
    }
    //跨域會有的要求-讓OPTIONS通過
    if($request->server["request_method"]=="OPTIONS"){
        $_JSON['code']=200;
        $_JSON['msg']="OK";
        $response->end(json_encode($_JSON));
        return;
    }
    //路由套件設定區 START
    $httpMethod = $request->server["request_method"];
    $uri = $request->server["request_uri"];
    // 去除query string(?foo=bar) and decode URI
    if (false !== $pos = strpos($uri, '?')) {
        $uri = substr($uri, 0, $pos);
    }
    $uri = rawurldecode($uri);
    $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
    //Json參數
    $oMiddleware = new Middleware();
    $oMiddleware->ReqData = json_decode($request->rawContent(),true);
    //分配路由狀態
    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::NOT_FOUND:
            //當uri路徑找不到
            $_JSON['code']=404;
            $_JSON['msg']="404 Not Found";
            $response->write( json_encode($_JSON) );
            $response->status(404);
            break;
        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            $allowedMethods = $routeInfo[1];
            // 當uri路徑找到，方法不對(GET POST PUT.....)
            $_JSON['code']=405;
            $_JSON['msg']="405 Method Not Allowed";
            $response->write( json_encode($_JSON) );
            $response->status(405);
            break;
        case FastRoute\Dispatcher::FOUND:
            //路徑、方法都對了，執行Controller
            $handler = $routeInfo[1];
            $vars = $routeInfo[2];
            //自定義$handler 第一個參數是 string class@method 第二個之後是$vars
            list($class, $method) = explode('@',$handler,2);
            $obj = new $class($oMiddleware);//類別進行物件化
            $response->write( $obj->{$method}($vars) );//傳入參數
            break;
    }

    $response->end();
    //路由套件設定區 END
});

$http->start();








