<?php
/**
 * 启动脚本
 *
 * @author
 */
namespace hyf\server;

class start
{

    private static $daemonize = false;

    private static $master_pid = '';
    
    private static function parseCli()
    {
        global $argv;
        
        if (!isset($argv[1]) || !isset($argv[2])) {
            throw new \Exception("使用方法 php {http|timer|tcp|websocket} app_name {start|stop|killall|reload|reload_task} (-d)\n");
        }
        
        //
        $program = explode("/", $argv[0]);
        $argv[0] = array_pop($program);
        unset($program);
        
        if (!in_array($argv[0], [
            'http', 
            'timer', 
            'tcp',
            'websocket'
        ])) {
            throw new \Exception("参数[" . $argv[0] . "]使用不正确\n");
        }
        
        if (!is_dir(\Hyf::$dir . 'application' . '/' . $argv[1])) {
            throw new \Exception("应用[" . $argv[1] . "]不存在\n");
        }
        
        // 服务配置文件
        if (file_exists(\Hyf::$dir . 'application' . '/' . $argv[1] . '/conf/server.php')) {
            \Hyf::$server_config = include (\Hyf::$dir . 'application' . '/' . $argv[1] . '/conf/server.php');
            \hyf::$server_config['app_name'] = $argv[1];
        } else {
            throw new \Exception("服务配置文件[" . \Hyf::$dir . 'application' . '/' . $argv[1] . "/conf/server.php]不存在\n");
        }
        
        if (!in_array($argv[2], [
            'start', 
            'stop', 
            'killall',
            'reload',
            'reload_task'
        ])) {
            throw new \Exception("参数[" . $argv[2] . "]使用不正确\n");
        }
        
        if (isset($argv[3]) && $argv[3] == '-d') {
            self::$daemonize = true;
        }
        
        return $argv[2];
    }

    private static function get_master_pid()
    {
        self::$master_pid = trim(shell_exec("pidof " . \Hyf::$server_config['process_name']['master']));
    }

    public static function run($server_type)
    {
        try {
            // action
            $action = self::parseCli();
            
            // 服务类型
            \Hyf::$server_config['service_type'] = $server_type;
            
            // 配置进程名称
            \Hyf::$server_config['process_name'] = [
                'base' => 'hy_' . \Hyf::$server_config['app_name'], 
                'master' => 'hy_' . \Hyf::$server_config['app_name'] . '_master', 
                'manager' => 'hy_' . \Hyf::$server_config['app_name'] . '_manager', 
                'worker' => 'hy_' . \Hyf::$server_config['app_name'] . '_worker[{id}]', 
                'task' => 'hy_' . \Hyf::$server_config['app_name'] . '_task[{id}]'
            ];
            
            self::get_master_pid();
            
            switch ($action) {
                case 'start':
                    self::startService();
                    break;
                case 'stop':
                    self::stopService();
                    break;
                case 'killall':
                    self::killall();
                    break;
                case 'reload':
                    self::reload();
                    break;
                case 'reload_task':
                    self::reload_task();
                    break;
            }
            
        } catch (\Exception $e) {
            exit("Error: \n File: {$e->getFile()} ,Line: {$e->getLine()}, Message: {$e->getMessage()}\n");
        } catch (\Error $e) {
            exit("Error: \n File: {$e->getFile()} ,Line: {$e->getLine()}, Message: {$e->getMessage()}\n");
        }
    }

    private static function startService()
    {
        if (empty(self::$master_pid)) {
            
            // 全局配置文件
            if (file_exists(\Hyf::$dir . 'conf/base.php')) {
                \Hyf::$config = include (\Hyf::$dir . 'conf/base.php');
            } else {
                throw new \Exception("全局配置文件[" . \Hyf::$dir . "conf/base.php]不存在\n");
            }
            
            // 抢占模式，主进程会根据Worker的忙闲状态选择投递，只会投递给处于闲置状态的Worker
            \Hyf::$server_config['server_set']['dispatch_mode'] = 3;
            
            // 设置task async，可以使用协程等
            \Hyf::$server_config['server_set']['task_async'] = true;
            
            // set log file
            \Hyf::$server_config['server_set']['log_file'] = log_path() . \Hyf::$server_config['app_name'] . '_server.log';
            
            // 设置服务进程状态
            \Hyf::$server_config['server_set']['daemonize'] = self::$daemonize;
            
            // 全局应用名称
            \Hyf::$app_name = \Hyf::$server_config['app_name'];
            
            // 一键php原生语句协程化
            if (isset(\Hyf::$server_config['enableCoroutine']) && \Hyf::$server_config['enableCoroutine'] == 1) {
                \Swoole\Runtime::enableCoroutine(true);
            }
            
            // 屏幕欢迎信息
            echo "\n";
            echo "\033[0;42;37m***************************************************************\033[0m\n";
            echo "\033[0;42;37m*                                                             *\033[0m\n";
            echo "\033[0;42;37m*           __             _____             __               *\033[0m\n";
            echo "\033[0;42;37m*          / /            / ___/            / /               *\033[0m\n";
            echo "\033[0;42;37m*         / /_  __   __  / /__     ____    / /_    ____       *\033[0m\n";
            echo "\033[0;42;37m*        / __ \ \ \ / / / .__/    / __ \  / __ \  / __ \      *\033[0m\n";
            echo "\033[0;42;37m*       / / / /  \_/ / / /       / /_/ / / / / / / /_/ /      *\033[0m\n";
            echo "\033[0;42;37m*      /_/ /_/    / / /_/       / .___/ /_/ /_/ / .___/       *\033[0m\n";
            echo "\033[0;42;37m*               _/_/           /_/             /_/            *\033[0m\n";
            echo "\033[0;42;37m*                                                             *\033[0m\n";
            echo "\033[0;42;37m*                                                             *\033[0m\n";
            echo "\033[0;42;37m***************************************************************\033[0m\n";
            echo "\n服务已经正常启动...";
            echo "\nphp版本: \033[32m" . PHP_VERSION . "\033[0m";
            echo "\nswoole版本: \033[32m" . swoole_version() . "\033[0m";
            echo "\nhyf版本: \033[32m" . version() . "\033[0m\n\n";
            if (!self::$daemonize) {
                echo "\n\n\033[0;33m***************************调试模式****************************\033[0m\n\n";
            }
            
            // start server
            call_user_func_array([
                '\\hyf\\server\\serv\\' . \Hyf::$server_config['service_type'], 
                'run'
            ], []);
        } else {
            throw new \Exception("服务正在运行，请勿重复启动\n");
        }
    }

    private static function stopService()
    {
        if (empty(self::$master_pid)) {
            throw new \Exception("服务尚未运行\n");
        } else {
            \system("kill -9 -" . self::$master_pid);
        }
    }

    private static function killall()
    {
        \system('ps -ef|grep hy_' . \Hyf::$server_config['app_name'] . '|grep -v grep|awk \'{print "kill -9 " $2}\' |sh');
    }
    
    /**
     * 平滑重启所有worker，仅针对业务代码所做的修改起效，对全局的定时器、初始化脚本的修改不起作用
     * @throws \Exception
     */
    private static function reload()
    {
        if (empty(self::$master_pid)) {
            throw new \Exception("服务尚未运行\n");
        } else {
            \system("kill -USR1 " . self::$master_pid);
            echo "worker将逐步进行重启\n";
        }
    }
    
    /**
     * 平滑重启所有task，仅针对业务代码所做的修改起效，对全局的定时器、初始化脚本的修改不起作用
     * @throws \Exception
     */
    private static function reload_task()
    {
        if (empty(self::$master_pid)) {
            throw new \Exception("服务尚未运行\n");
        } else {
            \system("kill -USR2 " . self::$master_pid);
            echo "task将逐步进行重启\n";
        }
    }
    
}
