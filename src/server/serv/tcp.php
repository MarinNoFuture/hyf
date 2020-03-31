<?php
/**
 * timer server
 *
 * @author
 */
namespace hyf\server\serv;

use hyf\server\lib\core;

class tcp
{

    public static function run()
    {
        core::run();
    }

    public static function handle($server, $fd, $from_id, $data)
    {
        \Hyf::$tcp_data = $data;
        
        try {
            // 自定义解包过程(可选)
            if (method_exists('\\application\\' . app_name() . '\\pack\\unpack', 'run')) {
                $data = call_user_func_array([
                    '\\application\\' . app_name() . '\\pack\\unpack', 
                    'run'
                ], [
                    $data
                ]);
            }
            
            // 数据处理
            if (method_exists('\\application\\' . app_name() . '\\data\\dispatch', 'run')) {
                $data = call_user_func_array([
                    '\\application\\' . app_name() . '\\data\\dispatch', 
                    'run'
                ], [
                    $data
                ]);
            } else {
                throw new \Exception("项目中缺少处理数据的dispatch类");
            }
        } catch (\Exception $e) {
            $data = $e->getMessage();
        } catch (\Error $e) {
            $data = $e->getMessage();
        } finally {
            // 自定义打包过程(可选)
            if (method_exists('\\application\\' . app_name() . '\\pack\\pack', 'run')) {
                $data = call_user_func_array([
                    '\\application\\' . app_name() . '\\pack\\pack', 
                    'run'
                ], [
                    $data
                ]);
            }
            if ($server->send($fd, $data)) {
                $server->close($fd);
            }
        }
    }
}
