<?php
/**
 * timer server
 *
 * @author
 */
namespace hyf\server\serv;

use hyf\server\lib\core;

class timer
{

    public static function run()
    {
        core::run();
    }

    public static function handle($server, $fd, $from_id, $data)
    {
        $server->send($fd, 'NULL');
        $server->close($fd);
    }
}
