<?php

namespace tests\unit\workflow\activebehavior;

use tests\codeception\unit\models\Item04;
use Yii;
use yii\codeception\DbTestCase;
use fproject\workflow\core\ActiveWorkflowBehavior;
use tests\codeception\unit\fixtures\ItemFixture04;

/**
 * Class ChangeStatusTest
 *
 * @method Item04[] items()
 *
 * @package tests\unit\workflow\activebehavior
 */
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
			'class'=> 'fproject\workflow\core\ArrayWorkflowItemFactory',
			'namespace' => 'tests\codeception\unit\models'
		]);
	}

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testChangeStatusOnSaveFailed()
    {
        /** @var Item04|ActiveWorkflowBehavior $item */
    	$item = $this->items('item1');
    	$this->assertTrue($item->getWorkflowStatus()->getId() == 'Item04Workflow/B');

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
    	$this->assertTrue($item->getWorkflowStatus()->getId() == 'Item04Workflow/B');

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
    		verify('current status is ok',$item->getWorkflowStatus()->getId())->equals('Item04Workflow/B');
    		expect('save returns true',$item->save(false))->equals(true);
    		verify('model status attribute has not been modified',$item->status)->equals('Item04Workflow/C');
    		verify('model current status has not been modified',$item->getWorkflowStatus()->getId())->equals('Item04Workflow/C');
    	});
    }
}
