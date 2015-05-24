<?php

namespace tests\codeception\unit\models;

use Yii;
use fproject\workflow\core\ActiveWorkflowBehavior;

/**
 * @property integer $id
 * @property string $name
 * @property string $status
 * @property string $dynamicWorkflowId
 */
class Item07 extends \yii\db\ActiveRecord
{
	public $statusAlias = null;
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
        		'class' => ActiveWorkflowBehavior::className(),
        		'statusAttribute' => 'statusAlias',
        		'statusAccessor' => 'status_accessor'
    	    ]
        ];
    }
}
