<?php
/**
 * 
 */
namespace hyf\server\lib;

use hyf\server\serv\http;
use hyf\server\serv\timer;
use hyf\server\serv\tcp;
use hyf\server\serv\websocket;

class core
{

    /**
     * 设置应用配置文件
     */
    private static function appConfig()
    {
        if (file_exists(app_dir() . 'conf/app.php')) {
            \Hyf::$app_config = include (app_dir() . 'conf/app.php') ?: [];
        } else {
            \Hyf::$app_config = [];
        }
    }

    /**
     * 绑定系统内置容器方法
     *
     * @param string $server_type            
     */
    private static function bindDefaultContainer($server_type = 'http')
    {
        \hyf\container\binds::Run($server_type);
    }

    /**
     * 初始化相关Job
     *
     * @param string $server_type            
     */
    private static function initJobService($server_type = 'http')
    {
        \hyf\init\init::Run($server_type);
    }

    /**
     * master process start
     * @param unknown $server
     */
    public static function onStart($server)
    {
        swoole_set_process_name(\Hyf::$server_config['process_name']['master']);
    }

    /**
     * manager process start
     * @param unknown $server
     */
    public static function onManagerStart($server)
    {
        swoole_set_process_name(\Hyf::$server_config['process_name']['manager']);
    }

    /**
     * worker process start
     * @param unknown $server
     * @param unknown $worker_id
     */
    public static function onWorkerStart($server, $worker_id)
    {
        if ($worker_id >= $server->setting['worker_num']) {
            $process_name = str_replace('{id}', $worker_id, \Hyf::$server_config['process_name']['task']);
            swoole_set_process_name($process_name);
        } else {
            $process_name = str_replace('{id}', $worker_id, \Hyf::$server_config['process_name']['worker']);
            swoole_set_process_name($process_name);
        }
    }

    /**
     * async task handle
     * @param unknown $server
     * @param unknown $task_id
     * @param unknown $from_id
     * @param unknown $data
     * @return string|mixed
     */
    public static function onTask($server, $task_id, $from_id, $data)
    {
        $ret = call_user_func_array([
            new $data["class"](), 
            $data["method"]
        ], [
            $data["data"]
        ]);
        if($data["finish"]) {
            return is_null($ret) ? '' : $ret;
        }
    }
    
    private static function sockFile()
    {
        $sock_file = "/tmp/" . \Hyf::$server_config['service_type'] . "-" . \Hyf::$server_config['app_name'] . ".sock";
        shell_exec("touch {$sock_file}");
        \Hyf::$server_config['host'] = $sock_file;
        \Hyf::$server_config['port'] = 0;
    }

    public static function run()
    {
        // create server
        switch (\Hyf::$server_config['service_type']){
            case 'http':
                $server = new \Swoole\Http\Server(\Hyf::$server_config['host'], \Hyf::$server_config['port'], SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
                break;
            case 'timer':
                self::sockFile();
                $server = new \Swoole\Server(\Hyf::$server_config['host'], \Hyf::$server_config['port'], SWOOLE_PROCESS, SWOOLE_UNIX_STREAM);
                break;
            case 'tcp':
                $server = new \Swoole\Server(\Hyf::$server_config['host'], \Hyf::$server_config['port'], SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
                break;
            case 'websocket':
                $server = new \Swoole\WebSocket\Server(\Hyf::$server_config['host'], \Hyf::$server_config['port'], SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
                break;
        }
        
        $server->set(\Hyf::$server_config['server_set']);
        
        // 应用配置文件
        self::appConfig();
        
        // 设置server对象
        \Hyf::$server = $server;
        
        // default bind container
        self::bindDefaultContainer(\Hyf::$server_config['service_type']);
        
        // init jobs
        self::initJobService(\Hyf::$server_config['service_type']);
        
        $server->on('start', [self::class, 'onStart']);
        $server->on('managerStart', [self::class, 'onManagerStart']);
        $server->on('workerStart', [self::class, 'onWorkerStart']);
        switch (\Hyf::$server_config['service_type']){
            case 'http':
                // 注册handle模式路由
                if(!empty(app_config()['route']['mode']) && app_config()['route']['mode'] == 'handle') {
                    call_user_func_array([
                            "\\application\\" . app_name() . "\\route\\router",
                            "Run"
                        ],[
                            \hyf\component\route\routerHandle::class
                        ]
                    );
                }
                $server->on('request', [http::class, 'handle']);
                break;
            case 'timer':
                $server->on('receive', [timer::class, 'handle']);
                break;
            case 'tcp':
                $server->on('receive', [tcp::class, 'handle']);
                break;
            case 'websocket':
                $server->on('message', [websocket::class, 'handle']);
        }
        $server->on('task', [self::class, 'onTask']);
        
        $server->start();
    }
    
}