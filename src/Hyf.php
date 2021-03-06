<?php
/**
 * Class Hyf
 * 
 * @author
 */
class Hyf
{

    /**
     * 版本号
     *
     * @var string
     */
    public static $version = "3.0.0";
    
    /**
     * 系统主路径
     *
     * @var string
     */
    public static $dir;
    
    /**
     * 全局配置文件
     *
     * @var array
     */
    public static $config;

    /**
     * 应用名称
     *
     * @var string
     */
    public static $app_name;

    /**
     * server配置文件
     *
     * @var array
     */
    public static $server_config;
    
    /**
     * server对象
     *
     * @var object
     */
    public static $server;

    /**
     * 请求request对象
     *
     * @var object
     */
    public static $request;

    /**
     * 请求response对象
     *
     * @var object
     */
    public static $response;

    /**
     * tcp data
     *
     * @var object
     */
    public static $tcp_data;
    
    /**
     * websocket frame
     *
     * @var object
     */
    public static $websocket_frame;
    
    /**
     * 请求group名
     *
     * @var string
     */
    public static $group;

    /**
     * 请求controller名
     *
     * @var string
     */
    public static $controller;
    
    /**
     * 请求action名
     *
     * @var string
     */
    public static $action;
    
    /**
     * 应用配置文件
     *
     * @var array
     */
    public static $app_config;

    /**
     * 启动脚本
     */
    public static function Run($type = 'http')
    {
        \hyf\server\start::run($type);
    }
}
