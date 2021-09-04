<?php

use yii\db\Migration;

class m210710_081213_activerecordhistory_init extends Migration
{
    const TABLE_NAME = '{{%activerecord_models_history}}';

    public function up()
    {
        $this->createTable(self::TABLE_NAME, [
            'id' => $this->bigPrimaryKey(),
            'user_id' => $this->integer(),
            'type' => $this->smallInteger()->notNull(),
            'model' => $this->string()->notNull(),
            'model_id' => $this->string()->notNull(),
            'field_name' => $this->string(),
            'old_value' => $this->text(),
            'new_value' => $this->text(),
            'created_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-modelhistory-model_id', self::TABLE_NAME, ['model', 'model_id']);
        $this->createIndex('idx-modelhistory-type', self::TABLE_NAME, ['type']);
        $this->createIndex('idx-modelhistory-user', self::TABLE_NAME, ['user_id']);
    }

    public function down()
    {
        $this->dropTable(self::TABLE_NAME);
    }
}

/*
CREATE TABLE IF NOT EXISTS `activerecord_models_history`
(
    `id`         bigint       NOT NULL AUTO_INCREMENT,
    `user_id`    int(11),
    `type`       smallint     NOT NULL,
    `model`      varchar(255) NOT NULL,
    `model_id`   varchar(255) NOT NULL,
    `field_name` varchar(255),
    `old_value`  text,
    `new_value`  text,
    `created_at` int(11)      not null,

    PRIMARY KEY (id),
    INDEX (model, model_id),
    INDEX (user_id),
    INDEX (type)
) ENGINE = InnoDB;
 */
