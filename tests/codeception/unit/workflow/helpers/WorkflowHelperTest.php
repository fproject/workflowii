<?php

namespace tests\unit\workflow\helpers;

use Yii;
use yii\codeception\TestCase;
use tests\codeception\unit\models\Item04;
use yii\base\InvalidConfigException;
use yii\base\Exception;
use fproject\workflow\helpers\WorkflowHelper;
use fproject\workflow\base\Status;
use fproject\workflow\base\Transition;
use fproject\workflow\base\Workflow;


class WorkflowHelperTest extends TestCase
{
	use \Codeception\Specify;
	
	protected function setup()
	{
		parent::setUp();
		Yii::$app->set('workflowSource',[
			'class'=> 'fproject\workflow\source\php\WorkflowPhpSource',
			'namespace' => 'tests\codeception\unit\models'
		]);
	}
	
	protected function tearDown()
	{
		parent::tearDown();
	}
	
	public function testGetAllStatusListData()
	{
		$ar = WorkflowHelper::getAllStatusListData('Item04Workflow', Yii::$app->workflowSource);
		
		$expected = [
			'Item04Workflow/A' => 'Entry',
			'Item04Workflow/B' => 'Published',
			'Item04Workflow/C' => 'node C',
			'Item04Workflow/D' => 'node D',
		];
		
		$this->assertEquals(4, count(array_intersect_assoc($expected,$ar)));

	}
}
