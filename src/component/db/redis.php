<?php
namespace hyf\component\db;

class redis
{

    public $redis;

    public function __construct($dbType = "redis")
    {
        $dbConf = \Hyf::$config[$dbType];
        $this->redis = new \Redis();
        $this->redis->connect($dbConf['host'], $dbConf['port']);
        if (!empty($dbConf['auth'])) { // 需要认证
            $isauth = $this->redis->auth($dbConf['password']);
            if (!$isauth) {
                throw new \Exception('redis认证失败，请联系管理员', 401);
            }
        }
    }


    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->redis, $name], $arguments);
    }
}
