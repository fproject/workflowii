<?php
namespace tests\unit\workflow\activebehavior;

use Codeception\Specify;
use Yii;
use yii\codeception\TestCase;
use tests\codeception\unit\models\Item04;
use fproject\workflow\core\StatusIdConverter;
use fproject\workflow\core\ActiveWorkflowBehavior;

class StatusIdConvertionTest extends TestCase
{
    use Specify;

	public $item;
	protected function setup()
	{
		parent::setUp();

		Yii::$app->set('workflowFactory',[
			'class'=> 'fproject\workflow\core\ArrayWorkflowItemFactory',
			'workflowSourceNamespace' => 'tests\codeception\unit\models'
		]);

		Yii::$app->set('converter',[
			'class'=> 'fproject\workflow\core\StatusIdConverter',
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
        /** @var Item04|ActiveWorkflowBehavior $item */
		$item = new Item04();
		$item->attachBehavior('workflow',[
			'class' => ActiveWorkflowBehavior::className(),
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
			'class' => ActiveWorkflowBehavior::className(),
			'statusConverter' => 'not_found_component'
		]);
	}
	public function testConvertionOnChangeStatus()
	{
        /** @var Item04|ActiveWorkflowBehavior $item */
		$item = new Item04();
		$item->attachBehavior('workflow',[
			'class' => ActiveWorkflowBehavior::className(),
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
        /** @var Item04|ActiveWorkflowBehavior $item */
		$item = new Item04();
		$item->attachBehavior('workflow',[
			'class' => ActiveWorkflowBehavior::className(),
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
