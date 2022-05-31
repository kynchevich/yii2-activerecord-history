<?php

use kirillemko\activeRecordHistory\models\ActiveRecordHistory;
use yii\db\Migration;

class m220531_081213_activerecordhistory_event_instead_of_type extends Migration
{
    const TABLE_NAME = '{{%activerecord_models_history}}';

    public function up()
    {
        $this->addColumn(self::TABLE_NAME, 'event', $this->string());
        $this->createIndex('idx-modelhistory-event', self::TABLE_NAME, ['event']);

        foreach (ActiveRecordHistory::find()->each(100) as $history) {
            if( $history->type === 1){
                $history->event = 'afterInsert';
            } elseif( $history->type === 2){
                $history->event = 'afterUpdate';
            } elseif( $history->type === 3){
                $history->event = 'afterDelete';
            }
            $history->save(false);
        }



        $this->dropIndex('idx-modelhistory-type', self::TABLE_NAME, ['type']);
        $this->dropColumn(self::TABLE_NAME, 'type');

    }

    public function down()
    {
        $this->addColumn(self::TABLE_NAME, 'type', $this->integer());
        $this->createIndex('idx-modelhistory-type', self::TABLE_NAME, ['type']);

        foreach (ActiveRecordHistory::find()->each(100) as $history) {
            if( $history->event === 'afterInsert'){
                $history->type = 1;
            } elseif( $history->event === 'afterUpdate'){
                $history->type = 2;
            } elseif( $history->event === 'afterDelete'){
                $history->type = 3;
            }
            $history->save(false);
        }

        $this->dropIndex('idx-modelhistory-event', self::TABLE_NAME, ['event']);
        $this->dropColumn(self::TABLE_NAME, 'event');
    }
}

