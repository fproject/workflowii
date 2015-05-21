<?php

namespace tests\codeception\unit\models;

use Yii;
use fproject\workflow\core\WorkflowBehavior;
use fproject\workflow\core\IWorkflowDefinitionProvider;

/**
 * @property integer $id
 * @property string $name
 * @property string $status
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
        		'class' => WorkflowBehavior::className(),
        		'statusAttribute' => 'statusAlias',
        		'statusAccessor' => 'status_accessor'
    	    ]
        ];
    }
}
