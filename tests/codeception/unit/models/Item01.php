<?php

namespace tests\codeception\unit\models;

use Yii;
use fproject\workflow\base\WorkflowBehavior;

/**
 * This is the model class for table "item".
 *
 * @property integer $id
 * @property string $name
 * @property string $status
 */
class Item01 extends \yii\db\ActiveRecord
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
    	    ]
        ];
    }
}
