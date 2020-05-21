# Swoole_FastRoute
使用Swoole監聽，轉到FastRoute來執行，把長連線從Apache獨立出來

route_apache.php 原版的apache做fastroute  

route.php 改過加上透過Swoole監聽

### 從apache到swoole，那些做法要注意:

1.內存安全 可閱讀https://segmentfault.com/a/1190000018533664

原本的
Middleware::$ReqData = json_decode($request->rawContent(),true);

要改成
$oMiddleware = new Middleware();
$oMiddleware->ReqData = json_decode($request->rawContent(),true);
不然其他的要求，有可能抓到錯誤的Middleware::$ReqData
ex: A修改了Middleware::$ReqData，B也改了，A在B改之後，去使用了，就造成資料錯誤。

這邊就可以看出之前一直覺得DI不重要，這邊就顯得無比重要了!

要避免內存問題，就要避免使用全域變數，或是全域變數的值不能修改，只能是固定值。
