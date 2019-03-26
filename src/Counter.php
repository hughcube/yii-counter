<?php

namespace HughCube\YiiCounter;

use HughCube\Counter\StorageInterface;
use yii\di\Instance;

class Counter extends \HughCube\Counter\Counter
{
    /**
     * @param mixed $storage
     * @throws \yii\base\InvalidConfigException
     */
    public function setStorage($storage)
    {
        if (null === $storage){
            $this->storage = null;
        }

        $this->storage = Instance::ensure($storage, StorageInterface::class);
    }
}
