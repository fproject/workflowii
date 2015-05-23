<?php

namespace tests\unit\workflow\behavior;

use tests\codeception\unit\models\Item04;
use Yii;
use yii\codeception\DbTestCase;
use tests\codeception\unit\models\Item01;
use yii\base\InvalidConfigException;
use fproject\workflow\core\ActiveWorkflowBehavior;
use tests\codeception\unit\fixtures\ItemFixture04;
use yii\db\ActiveRecord;

class ChangeStatusTest extends DbTestCase
{
	use \Codeception\Specify;

	public function fixtures()
	{
		return [
			'items' => ItemFixture04::className(),
		];
	}
	protected function setup()
	{
		parent::setUp();
		Yii::$app->set('workflowFactory',[
			'class'=> 'fproject\workflow\factory\assoc\WorkflowArrayFactory',
			'namespace' => 'tests\codeception\unit\models'
		]);
	}

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testChangeStatusOnSaveFailed()
    {
        /** @var ActiveRecord $item */
    	$item = $this->items('item1');
    	$this->assertTrue($item->workflowStatus->getId() == 'Item04Workflow/B');

    	$this->setExpectedException(
    		'fproject\workflow\core\WorkflowException',
    		'No status found with id Item04Workflow/Z'
    	);

    	$item->status = 'Item04Workflow/Z';
    	$item->save(false);
    }

    public function testChangeStatusByMethodFailed()
    {
        /** @var ActiveWorkflowBehavior|Item04 $item */
    	$item = $this->items('item1');
    	$this->assertTrue($item->workflowStatus->getId() == 'Item04Workflow/B');

    	$this->setExpectedException(
    		'fproject\workflow\core\WorkflowException',
    		'No status found with id Item04Workflow/Z'
    	);

		$item->sendToStatus('Item04Workflow/Z');
    }

    public function testChangeStatusOnSaveSuccess()
    {
        /** @var ActiveWorkflowBehavior|Item04 $item */
    	$item = $this->items('item1');
    	$this->specify('success saving model and perform transition',function() use ($item) {

    		$item->status = 'Item04Workflow/C';
    		verify('current status is ok',$item->workflowStatus->getId())->equals('Item04Workflow/B');
    		expect('save returns true',$item->save(false))->equals(true);
    		verify('model status attribute has not been modified',$item->status)->equals('Item04Workflow/C');
    		verify('model current status has not been modified',$item->getWorkflowStatus()->getId())->equals('Item04Workflow/C');
    	});
    }
}
