<?php

use HughCube\counter\DbCounter;
use yii\di\Instance;

class m160914_172227_counter_init extends \yii\db\Migration
{
    /**
     * @var DbCounter
     */
    public $counter = 'counter';

    public function init()
    {
        parent::init();
        $this->counter = Instance::ensure($this->counter, DbCounter::className());
        $this->db = $this->counter->db;
    }

    public function up()
    {
        $this->createTable($this->counter->counterTable, [
            'id' => $this->char(100)->notNull()->comment('计数器的key'),
            'slot' => $this->smallInteger()->unsigned()->defaultValue(0)->notNull()->comment('计数器的key'),
            'count' => $this->bigInteger()->defaultValue(0)->notNull()->comment('计数'),
            'PRIMARY KEY ([[key]],[[slot]])',
        ], $this->getTableOptions());
    }

    public function getTableOptions()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql'){
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci';
        }

        return $tableOptions;
    }

    public function down()
    {
        $this->dropTable($this->counter->counterTable);
    }
}
