<?php

namespace HughCube\YiiCounter;

use HughCube\Counter\StorageInterface;
use yii\db\Connection;
use yii\db\Exception as DbException;
use yii\db\Expression;
use yii\db\Query;
use yii\db\Transaction as DbTransaction;
use yii\di\Instance;

class DbStorage implements StorageInterface
{
    /**
     * @type Connection yiiDB组件
     */
    public $db = 'db';

    /**
     * @var string 表名
     */
    public $counterTable = '{{%counter}}';

    /**
     * @var int 最小的行数据编号, 为了减少死锁的概率, 把数据分散在多行数据
     */
    public $slot = 0;

    /**
     * @inheritdoc
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws DbException
     */
    public function decr($key, $value = 1)
    {
        return $this->incr($key, 0 - $value);
    }

    /**
     * @inheritdoc
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws DbException
     */
    public function incr($key, $value = 1)
    {
        return $this->update($key, $value, true);
    }

    /**
     * @inheritdoc
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws DbException
     */
    public function get($key)
    {
        $values = $this->getMultiple([$key]);

        return isset($values[$key]) ? $values[$key] : 0;
    }

    /**
     * @inheritdoc
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws DbException
     */
    public function getMultiple(array $keys)
    {
        $rows = $this->getDb()->noCache(function (Connection $db) use ($keys){
            $query = (new Query())
                ->select(['id', 'count'])
                ->from($this->counterTable)
                ->where(['id' => $keys])
                ->createCommand($db);

            return $query->queryAll();
        });

        $result = [];
        foreach($keys as $key){
            $result[$key] = 0;
        }

        foreach($rows as $row){
            $result[$row['id']] += $row['count'];
        }

        return $result;
    }

    /**
     * @inheritdoc
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws DbException
     */
    public function has($key)
    {
        return $this->getDb()->noCache(function (Connection $db) use ($key){
            return (new Query())
                ->from($this->counterTable)
                ->where(['id' => $key])
                ->exists($db);
        });
    }

    /**
     * @inheritdoc
     * @throws DbException
     * @throws \Throwable
     */
    public function set($key, $value)
    {
        return $this->update($key, $value, false);
    }

    /**
     * @return Connection
     * @throws \yii\base\InvalidConfigException
     */
    public function getDb()
    {
        if (!$this->db instanceof Connection){
            $this->db = Instance::ensure($this->db, Connection::class);
        }

        return $this->db;
    }

    /**
     * 计数设置成0
     *
     * @param $id
     * @return bool
     * @throws \yii\base\InvalidConfigException
     * @throws DbException
     */
    protected function setZero($id)
    {
        $affectedRows = $this->getDb()
            ->createCommand()
            ->update($this->counterTable, ['count' => 0], ['id' => $id])
            ->execute();

        if (false === $affectedRows){
            throw new DbException("set zero failure");
        }

        return true;
    }

    /**
     * 修改计数
     *
     * @param $id
     * @param $count
     * @param bool $incr
     * @return int
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws DbException
     */
    protected function update($id, $count, $incr = false)
    {
        /** 操作数为零的 */
        if (0 == $count){
            return $incr ? $this->get($id) : ($this->setZero($id) ? 0 : 0);
        }

        /** @var DbTransaction|null $transaction , 如果是设置需要开启事务 */
        $transaction = !$incr ? $this->getDb()->beginTransaction() : null;
        try{
            /** 如果是设置, 先把所有的数据设置为零 */
            if (!$incr){
                $this->setZero($id);
            }

            $slot = rand(0, $this->slot);

            try{
                $command = $this->getDb()->createCommand()->insert(
                    $this->counterTable,
                    ['id' => $id, 'slot' => $slot, 'count' => $count]
                );
                $command->execute();

            }catch(DbException $exception){
                if ($count > 0){
                    $expression = new Expression(sprintf('[[count]]+:count'), [':count' => abs($count)]);
                }elseif ($count < 0){
                    $expression = new Expression(sprintf('[[count]]-:count'), [':count' => abs($count)]);
                }else{
                    $expression = $count;
                }

                $command = $this->getDb()->createCommand()->update(
                    $this->counterTable,
                    ['count' => $expression],
                    ['id' => $id, 'slot' => $slot]
                );
                $command->execute();
            }
            (($transaction instanceof DbTransaction) AND $transaction->commit());
        }catch(\Exception $exception){
            (($transaction instanceof DbTransaction) AND $transaction->rollBack());
            throw $exception;
        }

        return $incr ? $this->get($id) : $count;
    }
}
