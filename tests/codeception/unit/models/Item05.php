<?php

namespace tests\codeception\unit\models;

use Yii;
use fproject\workflow\core\ActiveWorkflowBehavior;
use fproject\workflow\helpers\WorkflowScenario;

/**
 * @property integer $id
 * @property string $name
 * @property string $status
 * @property string $dynamicWorkflowId
 */
class Item05 extends \yii\db\ActiveRecord
{
	public $category = 'default';
	public $tags = null;
	public $author = "default";
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'item';
    }

	public function rules() {
		return [
			[['status'], '\fproject\workflow\validators\WorkflowValidator'],
			['name','required',
				'on' => WorkflowScenario::changeStatus('Item05Workflow/new', 'Item05Workflow/correction') ],

			['category', 'required',
				'on' => WorkflowScenario::enterWorkflow('Item05Workflow')],

			['category', 'compare', 'compareValue' => 'done',
				'on' => WorkflowScenario::leaveWorkflow()],

			['tags', 'required',
				'on' => WorkflowScenario::leaveStatus('Item05Workflow/correction')],

			['author', 'required' ,
				'on' => WorkflowScenario::enterStatus('Item05Workflow/published')]
		];

	}

    public function behaviors()
    {
        return [
        	'workflow' => [
        		'class' => ActiveWorkflowBehavior::className()
    	    ]
        ];
    }
}
