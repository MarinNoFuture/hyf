<?php
namespace hyf;
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
    public static $version = "2.0";
    
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
    public static function Run($server_config)
    {
        \hyf\server\start::run($server_config);
    }
}