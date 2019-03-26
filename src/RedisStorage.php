<?php

namespace HughCube\YiiCounter;

use HughCube\Counter\StorageInterface;
use yii\di\Instance;
use yii\redis\Connection;

class RedisStorage implements StorageInterface
{
    /**
     * @var Connection|string
     */
    public $redis = 'redis';

    /**
     * @inheritdoc
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function incr($key, $value = 1)
    {
        return $this->getRedis()->executeCommand('INCRBY', [$key, $value]);
    }

    /**
     * @inheritdoc
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function decr($key, $value = 1)
    {
        return $this->getRedis()->executeCommand('DECRBY', [$key, $value]);
    }

    /**
     * @inheritdoc
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function set($key, $value)
    {
        return $this->getRedis()->executeCommand('SET', [$key, $value]);
    }

    /**
     * @inheritdoc
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function get($key)
    {
        return $this->getRedis()->executeCommand('GET', [$key]);
    }

    /**
     * @inheritdoc
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function getMultiple(array $keys)
    {
        $result = [];
        $response = $this->getRedis()->executeCommand('MGET', $keys);

        $i = 0;
        foreach($keys as $key){
            $result[$key] = intval($response[$i++]);
        }

        return $result;
    }

    /**
     * @inheritdoc
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function has($key)
    {
        return true == $this->getRedis()->executeCommand('EXISTS', [$key]);
    }

    /**
     * @return Connection
     * @throws \yii\base\InvalidConfigException
     */
    public function getRedis()
    {
        if (!$this->redis instanceof Connection){
            $this->redis = Instance::ensure($this->redis, Connection::class);
        }

        return $this->redis;
    }
}
