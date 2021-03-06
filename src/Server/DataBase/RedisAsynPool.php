<?php
/**
 * redis 异步客户端连接池
 * Created by PhpStorm.
 * User: tmtbe
 * Date: 16-7-22
 * Time: 上午10:19
 */

namespace Server\DataBase;


use Noodlehaus\Config;
use Server\CoreBase\SwooleException;
use Server\SwooleMarco;
use Server\SwooleServer;

class RedisAsynPool extends AsynPool
{
    const AsynName = 'redis';
   
    protected $redis_max_count = 0;
    /**
     * 连接
     * @var array
     */
    public $connect;
    public function __construct($connect=null)
    {
        parent::__construct();
        $this->connect = $connect;
    }

    /**
     * 映射redis方法
     * @param $name
     * @param $arguments
     */
    public function __call($name, $arguments)
    {
        $callback = array_pop($arguments);
        $data = [
            'name' => $name,
            'arguments' => $arguments
        ];
        $data['token'] = $this->addTokenCallback($callback);
        //写入管道
        $this->asyn_manager->writePipe($this, $data, $this->worker_id);
    }

    /**
     * 协程模式
     * @param $name
     * @param $arguments
     * @return RedisCoroutine
     */
    public function coroutineSend($name, ...$arg)
    {
        return new RedisCoroutine($this, $name, $arg);
    }
    /**
     * 执行redis命令
     * @param $data
     */
    public function execute($data)
    {
        if (count($this->pool)==0) {//代表目前没有可用的连接
            $this->prepareOne();
            $this->commands->push($data);
        } else {
            $client = $this->pool->shift();
            $arguments = $data['arguments'];
            //特别处理下M命令(批量)
            $harray = $arguments[1]??null;
            if ($harray != null && is_array($harray)) {
                unset($arguments[1]);
                $arguments = array_merge($arguments, $harray);
                $data['arguments'] = $arguments;
                $data['M'] = $harray;
            }
            $arguments[] = function ($client, $result) use ($data) {
                if (key_exists('M', $data)) {//批量命令
                    $data['result'] = [];
                    for ($i = 0; $i < count($result); $i++) {
                        $data['result'][$data['M'][$i]] = $result[$i];
                    }
                } else {
                    $data['result'] = $result;
                }
                unset($data['M']);
                unset($data['arguments']);
                unset($data['name']);
                //给worker发消息
                $this->asyn_manager->sendMessageToWorker($this, $data);
                //回归连接
                $this->pushToPool($client);
            };
            $client->__call($data['name'], $arguments);
        }
    }

    /**
     * 准备一个redis
     */
    public function prepareOne()
    {
        if ($this->redis_max_count > $this->config->get('redis.asyn_max_count', 10)) {
            return;
        }
        $client = new \swoole_redis();
        $callback = function ($client, $result) {
            if (!$result) {
                throw new SwooleException($client->errMsg);
            }
            $client->auth($this->config['redis']['password'], function ($client, $result) {
                if (!$result) {
                    $errMsg = $client->errMsg;
                    unset($client);
                    throw new SwooleException($errMsg);
                }
                $client->select($this->config['redis']['select'], function ($client, $result) {
                    if (!$result) {
                        throw new SwooleException($client->errMsg);
                    }
                    $this->redis_max_count++;
                    $this->pushToPool($client);
                });
            });
        };
        if($this->connect==null){
            $this->connect = [$this->config['redis']['ip'], $this->config['redis']['port']];
        }
        $client->connect($this->connect[0], $this->connect[1], $callback);
    }
    /**
     * @return string
     */
    public function getAsynName(){
        return self::AsynName;
    }

    /**
     * @return int
     */
    public function getMessageType(){
        return SwooleMarco::MSG_TYPE_REDIS_MESSAGE;
    }
}