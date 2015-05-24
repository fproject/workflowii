<?php

namespace tests\unit\workflow\helpers;

use Codeception\Specify;
use Yii;
use yii\codeception\TestCase;
use fproject\workflow\helpers\WorkflowHelper;

class WorkflowHelperTest extends TestCase
{
	use Specify;
	
	protected function setup()
	{
		parent::setUp();
		Yii::$app->set('workflowFactory',[
			'class'=> 'fproject\workflow\core\ArrayWorkflowItemFactory',
			'workflowSourceNamespace' => 'tests\codeception\unit\models'
		]);
	}
	
	protected function tearDown()
	{
		parent::tearDown();
	}
	
	public function testGetAllStatusListData()
	{
		$ar = WorkflowHelper::getAllStatusListData('Item04Workflow', Yii::$app->workflowFactory, null);
		
		$expected = [
			'Item04Workflow/A' => 'Entry',
			'Item04Workflow/B' => 'Published',
			'Item04Workflow/C' => 'node C',
			'Item04Workflow/D' => 'node D',
		];
		
		$this->assertEquals(4, count(array_intersect_assoc($expected,$ar)));

	}
}
