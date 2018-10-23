<?php
/**
 * Created by PhpStorm.
 * User: caohejie
 * Date: 2018/10/8
 * Time: 14:18
 */

use \Workerman\Worker;
use \Workerman\Lib\Timer;

class Visit
{

    /**
     * 存储所有客户端用户信息
     * @var array
     */
    public $userinfo = array();

    /**
     * 心跳时间间隔
     * @var int
     */
    public $heartbeatTime = 20;


    public function __construct()
    {

        // 创建一个Worker监听2345端口，使用http协议通讯
        $user = new Worker("websocket://0.0.0.0:2348");
        // 启动1个进程对外提供服务
        $user->count = 1;

        $user->name = 'Visit';

        $user->onWorkerStart = array($this, 'onStart');

        $user->onConnect = array($this, 'onConnect');

        $user->onMessage = array($this, 'onMessage');

        $user->onClose = array($this, 'onClose');

    }

    /**
     * 服务启动
     * @param $connection
     */

    public function onStart($worker)
    {

        Timer::add(5, function () use ($worker) {
            $time_now = time();
            foreach ($this->userinfo as $connection) {
                // 有可能该connection还没收到过消息，则lastMessageTime设置为当前时间
                if (empty($connection->lastHeartbeateTime)) {
                    $connection->lastHeartbeateTime = $time_now;
                    continue;
                }
                // 上次通讯时间间隔大于心跳间隔，则认为客户端已经下线，关闭连接
                if ($time_now - $connection->lastHeartbeateTime > ($this->heartbeatTime * 2)) {
                    echo "超时关闭\r\n";
                    //删除心跳定时器
                    Timer::del($this->userinfo[$connection->id]->heartbeattimeid);
                    //关闭连接
                    $connection->close();
                }
            }
        });


    }

    /**
     * 客户端连接
     * @param $connection
     */
    public function onConnect($connection)
    {

        echo time() . "-->收到客服端" . $connection->id . "的连接的消息\r\n";

        $this->userinfo[$connection->id] = $connection;

        //如果10S内没有发送认证信息 断开连接
        $timeer_id = Timer::add(10, function () use ($connection) {

            $connection->close();

        }, array(), false);

        //每20S发送一个心跳请求
        $heartbeattimeid = Timer::add(20, function () use ($connection) {

            $data = array();
            $data['type'] = "ping";

            $connection->send(json_encode($data));

        });

        $this->userinfo[$connection->id]->timeid = $timeer_id;
        $this->userinfo[$connection->id]->heartbeattimeid = $heartbeattimeid;


    }

    /**
     * 收到客户端消息
     * @param $connection
     * @param $data
     */
    public function onMessage($connection, $data)
    {

        $arr = json_decode($data, true);

        if (!is_array($arr)) {
            //走删除流程
            $connection->close();
        }

        switch ($arr['type']) {
            case "auth":
                //收到客户端认证请求 删除定时器id
                if (isset($arr['role'])) {
                    //说明是服务端连接
                    $this->userinfo[$connection->id]->roleid = 1;
                } else {
                    //说明是web端连接
                    $this->userinfo[$connection->id]->roleid = 0;
                }
                Timer::del($this->userinfo[$connection->id]->timeid);
                break;
            case "pong":
                $this->userinfo[$connection->id]->lastHeartbeateTime = time();
                return;
                break;
            case "msg":
                //判断是客户端消息还是服务端消息
                if ($this->userinfo[$connection->id]->roleid == 1) {
                    //服务端消息  需要进行转发

                    $data = array();
                    $data['type'] = "text";
                    $data['from'] = $connection->id;
                    $data['to'] = $arr['to'];
                    $data['msg'] = $arr['msg'];
                    $this->sendToAll($data);

                }
                break;
        }


    }

    /**
     * 连接断开回调
     * @param $connection
     */
    public function onClose($connection)
    {

        echo time() . "-->收到客服端" . $connection->id . "的关闭消息\r\n";
        $this->del($connection);

    }

    /**
     * 开始服务
     */
    public function start()
    {

        // 运行所有服务
        Worker::runAll();

    }

    /**
     * 踢出客户端连接
     * @param $con
     */
    public function del($con)
    {

        if (isset($this->userinfo[$con->id])) {

            unset($this->userinfo[$con->id]);


        }


    }

    /**
     * 给所有的客户端转发消息
     * @param $msg
     */
    public function sendToAll($data)
    {

        foreach ($this->userinfo as $key => $lianjie) {
            //排除服务端连接 和 自己
            if ($lianjie->roleid != 1 && $key != $data['from']) {
                $lianjie->send(json_encode($data));
            }
        }

    }

    /**
     * 给指定连接发送消息
     * @param $data
     */
    public function sendToClient($data)
    {

        //检查连接是否存在
        if (isset($this->userinfo[$data['to']])) {
            //如果存在
            $this->userinfo[$data['to']]->send(json_encode($data));
        } else {
            //如果不存在
            return;
        }


    }


}