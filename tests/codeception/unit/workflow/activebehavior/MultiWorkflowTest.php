<?php
namespace tests\unit\workflow\activebehavior;

use Yii;
use yii\codeception\TestCase;
use tests\codeception\unit\models\Item08;
use yii\base\InvalidConfigException;
use fproject\workflow\core\ActiveWorkflowBehavior;
use yii\codeception\DbTestCase;
use tests\codeception\unit\fixtures\ItemFixture04;

class MultiWorkflowTest extends DbTestCase {
	
	use \Codeception\Specify;
	
	protected function setup()
	{
		parent::setUp();
		Yii::$app->set('workflowFactory',[
			'class'=> 'fproject\workflow\factory\assoc\WorkflowArrayFactory',
			'namespace' => 'tests\codeception\unit\models'
		]);
	}	
	
	public function testSetStatusAssignedSuccess()
	{
		$o = new Item08();
		
		$o->status = 'draft';
		$o->status_ex = 'success';
		expect_that($o->save());
		verify_that( $o->status == 'Item08Workflow1/draft');
		verify_that( $o->status_ex == 'Item08Workflow2/success');
		
		$o = new Item08();		
		$o->status = 'draft';
		expect_that($o->save());
		verify_that( $o->status == 'Item08Workflow1/draft');
		verify_that( $o->status_ex == null);	

		$o = new Item08();
		$o->status_ex = 'success';
		expect_that($o->save());
		verify_that( $o->status == null);
		verify_that( $o->status_ex == 'Item08Workflow2/success');		
	}	
	
	/**
	 * @expectedException fproject\workflow\core\WorkflowException
	 * @expectedExceptionMessageRegExp #No status found with id Item08Workflow2/DUMMY#
	 */	
	public function testSetStatusAssignedFails1()
	{
		$o = new Item08();
	
		$o->status = 'draft';
		$o->status_ex = 'DUMMY';
		$o->save();
	}
		
	/**
	 * @expectedException fproject\workflow\core\WorkflowException
	 * @expectedExceptionMessageRegExp #No status found with id Item08Workflow1/DUMMY#
	 */
	public function testSetStatusAssignedFails2()
	{
		$o = new Item08();
	
		$o->status = 'DUMMY';
		$o->status_ex = 'succcess';
		$o->save();
	}
		
	public function testSetStatusBehaviorSuccess()
	{
        /** @var Item08|ActiveWorkflowBehavior $o */
		$o = new Item08();

        /** @var ActiveWorkflowBehavior $b1 */
		$b1 = $o->getBehavior('w1');
		$b1->sendToStatus('draft');

        /** @var ActiveWorkflowBehavior $b2 */
        $b2 = $o->getBehavior('w2');
		$b2->sendToStatus('success');

		verify_that( $b1->getWorkflowStatus()->getId() == 'Item08Workflow1/draft');
		verify_that( $b2->getWorkflowStatus()->getId() == 'Item08Workflow2/success');
		
		$b1->sendToStatus('correction');
		$b2->sendToStatus('onHold');
		
		verify_that( $b1->getWorkflowStatus()->getId() == 'Item08Workflow1/correction');
		verify_that( $b2->getWorkflowStatus()->getId() == 'Item08Workflow2/onHold');
	}	
	
	/**
	 * @expectedException fproject\workflow\core\WorkflowException
	 * @expectedExceptionMessageRegExp #No status found with id Item08Workflow1/DUMMY#
	 */	
	public function testSetStatusBehaviorFails1()
	{
        /** @var Item08|ActiveWorkflowBehavior $o */
		$o = new Item08();
        /** @var ActiveWorkflowBehavior $b */
        $b = $o->getBehavior('w1');
		$b->sendToStatus('DUMMY');
	}	
	
	/**
	 * @expectedException fproject\workflow\core\WorkflowException
	 * @expectedExceptionMessageRegExp #No status found with id Item08Workflow2/DUMMY#
	 */
	public function testSetStatusBehaviorFails2()
	{
        /** @var Item08|ActiveWorkflowBehavior $o */
		$o = new Item08();
        /** @var ActiveWorkflowBehavior $b */
        $b = $o->getBehavior('w2');
		$b->sendToStatus('DUMMY');
	}	
	
	public function testEnterWorkflowSuccess()
	{
        /** @var Item08|ActiveWorkflowBehavior $o */
		$o = new Item08();

        /** @var ActiveWorkflowBehavior $b1 */
        $b1 = $o->getBehavior('w1');
		$b1->enterWorkflow();

        /** @var ActiveWorkflowBehavior $b2 */
        $b2 = $o->getBehavior('w2');
		$b2->enterWorkflow();


		verify_that( $b1->getWorkflowStatus()->getId() == 'Item08Workflow1/draft');
		verify_that( $b2->getWorkflowStatus()->getId() == 'Item08Workflow2/success');
	}	
}