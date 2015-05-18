<?php
namespace tests\unit\workflow\behavior;

use Yii;
use yii\codeception\TestCase;
use yii\base\InvalidConfigException;
use tests\codeception\unit\models\Item04;
use fproject\workflow\base\Workflow;
use fproject\workflow\base\Status;
use fproject\workflow\base\Transition;
use fproject\workflow\base\StatusIdConverter;
use fproject\workflow\base\SimpleWorkflowBehavior;

class StatusIdConvertionTest extends TestCase
{
	use\Codeception\Specify;

	public $item;
	protected function setup()
	{
		parent::setUp();

		Yii::$app->set('workflowSource',[
			'class'=> 'fproject\workflow\source\php\WorkflowPhpSource',
			'namespace' => 'tests\codeception\unit\models'
		]);

		Yii::$app->set('converter',[
			'class'=> 'fproject\workflow\base\StatusIdConverter',
			'map' => [
				'Item04Workflow/A' => '1',
				'Item04Workflow/C' => '2',
				StatusIdConverter::VALUE_NULL => '55',
				'Item04Workflow/B' => StatusIdConverter::VALUE_NULL
			]
		]);
	}

	public function testConvertionOnAttachSuccess()
	{
		$item = new Item04();
		$item->attachBehavior('workflow',[
			'class' => SimpleWorkflowBehavior::className(),
			'statusConverter' => 'converter'
		]);
		$this->specify('on attach, initialize status and convert NULL to status ID', function() use ($item) {
			$this->assertEquals('Item04Workflow/B', $item->getWorkflowStatus()->getId());
			$this->assertTrue($item->getWorkflow()->getId() == 'Item04Workflow');
			$this->assertEquals(null, $item->status);
		});
	}
	public function testConvertionOnAttachFails()
	{
		$item = new Item04();
		$this->setExpectedException('yii\base\InvalidConfigException', 'Unknown component ID: not_found_component');
		$item->attachBehavior('workflow',[
			'class' => SimpleWorkflowBehavior::className(),
			'statusConverter' => 'not_found_component'
		]);
	}
	public function testConvertionOnChangeStatus()
	{
		$item = new Item04();
		$item->attachBehavior('workflow',[
			'class' => SimpleWorkflowBehavior::className(),
			'statusConverter' => 'converter'
			]);

		$this->specify('convertion is done on change status when setting the model attribute', function() use ($item) {
			$item->status = 1;
			verify($item->save())->true();
			$this->assertEquals('Item04Workflow/A', $item->getWorkflowStatus()->getId());
		});

		$this->specify('convertion is done on change status when using SendToStatus()', function() use ($item) {
			$item->sendToStatus('Item04Workflow/B');

			$this->assertEquals('Item04Workflow/B', $item->getWorkflowStatus()->getId());
			$this->assertEquals(null, $item->status);
		});
	}

	public function testConvertionOnLeaveWorkflow()
	{
		$item = new Item04();
		$item->attachBehavior('workflow',[
			'class' => SimpleWorkflowBehavior::className(),
			'statusConverter' => 'converter'
		]);

		$this->assertEquals(null, $item->status);
		$this->assertEquals('Item04Workflow/B', $item->getWorkflowStatus()->getId());

		$this->specify('convertion is done when leaving workflow', function() use ($item) {
			$item->sendToStatus(null);
			expect('item to not be in a workflow',$item->getWorkflow())->equals(null);
			expect('item to not have status',$item->hasWorkflowStatus())->false();
			expect('status attribut to be converted into 55', $item->status)->equals(55);
		});
	}
}
