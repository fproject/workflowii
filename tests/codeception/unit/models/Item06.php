<?php

namespace tests\codeception\unit\models;

use Yii;
use fproject\workflow\base\WorkflowBehavior;
use fproject\workflow\events\WorkflowEvent;

/**
 * @property integer $id
 * @property string $name
 * @property string $status
 */
class Item06 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'item';
    }
    public function behaviors()
    {
        return [
        	'workflow' => [
        		'class' => WorkflowBehavior::className()
    	    ],
        	'activeWorkflow' => [
        		'class' => Item06Behavior::className()
        	]
        ];
    }
}
