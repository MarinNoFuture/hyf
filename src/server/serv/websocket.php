<?php
namespace hyf\server\serv;

use hyf\server\lib\core;

class websocket
{

    public static function run()
    {
        core::run();
    }

    public static function handle($server, $frame)
    {
        \Hyf::$websocket_frame = $frame;
        
        //var_dump($frame);
        try {
            // 数据处理
            if (method_exists('\\application\\' . app_name() . '\\data\\dispatch', 'run')) {
                $data = call_user_func_array([
                    '\\application\\' . app_name() . '\\data\\dispatch',
                    'run'
                ], [
                    $frame->data
                ]);
            } else {
                throw new \Exception("项目中缺少处理数据的dispatch类");
            }
        } catch (\Exception $e) {
            $data = $e->getMessage();
        } catch (\Error $e) {
            $data = $e->getMessage();
        } finally {
            $server->push($frame->fd, $data);
        }
        
    }
}