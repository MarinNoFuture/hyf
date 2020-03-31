<?php
/**
 * http server
 *
 * @author
 */
namespace hyf\server\serv;

use hyf\server\lib\core;

class http
{

    public static function run()
    {
        core::run();
    }

    public static function handle($request, $response)
    {
        // 将request和response设置为全局对象
        \Hyf::$request = $request;
        \Hyf::$response = $response;
        
        // 处理非业务请求
        if (request()->server['path_info'] == '/favicon.ico' || request()->server['request_uri'] == '/favicon.ico') {
            return response()->end();
        }
        
        // http handle
        \hyf\frame\http::Handle();
    }
}
