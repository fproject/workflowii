<?php

namespace tests\unit\workflow\activebehavior;

use Codeception\Specify;
use fproject\workflow\core\IStatus;
use fproject\workflow\core\Status;
use Yii;
use yii\codeception\DbTestCase;
use tests\codeception\unit\models\Item04;
use fproject\workflow\core\ActiveWorkflowBehavior;
use tests\codeception\unit\fixtures\ItemFixture04;
use tests\codeception\unit\models\Item05;
use fproject\workflow\events\WorkflowEvent;

/**
 * Class GetNextStatusTest
 * @package tests\unit\workflow\activebehavior
 * @method ActiveWorkflowBehavior[] items()
 */
class GetNextStatusTest extends DbTestCase
{
    use Specify;

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
			'workflowSourceNamespace' => 'tests\codeception\unit\models'
		]);
	}

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testGetNextStatusInWorkflow()
    {
        /** @var ActiveWorkflowBehavior $item */
    	$item = $this->items('item1');
    	$this->assertTrue($item->getWorkflowStatus()->getId() == 'Item04Workflow/B');

    	$this->specify('2 status are returned as next status',function() use ($item) {

    		$n = $item->getNextStatuses();

    		expect('array is returned',is_array($n) )->true();
    		expect('array has 2 items',count($n) )->equals(2);
    		expect('status Item04Workflow/A is returned as index',isset($n['Item04Workflow/A']) )->true();

            /** @var IStatus $stsA */
            $stsA = $n['Item04Workflow/A']['status'];

    		expect('status Item04Workflow/A is returned as Status',$stsA->getId() )->equals('Item04Workflow/A');

    		expect('status Item04Workflow/C is returned as index',isset($n['Item04Workflow/C']) )->true();

            /** @var IStatus $stsC */
            $stsC = $n['Item04Workflow/C']['status'];
    		expect('status Item04Workflow/A is returned as Status',$stsC->getId() )->equals('Item04Workflow/C');
    	});
    }

    public function testGetNextStatusWithLabel()
    {
        /** @var ActiveWorkflowBehavior $item */
        $item = $this->items('item1');
        $this->assertEquals('Item04Workflow/B', $item->getWorkflowStatus()->getId());

        /** @var Status[] $n */
        $n = $item->getNextStatuses();
        $this->assertTrue(is_array($n));
        $this->assertEquals(2, count($n));
        $this->assertEquals('Item04Workflow/A',$n[0]->getId());
        $this->assertEquals('Entry',$n[0]->getLabel());
        $this->assertEquals('Item04Workflow/c',$n[1]->getId());
        $this->assertEquals('node C',$n[1]->getLabel());
    }

    public function testGetNextStatusOnEnter()
    {
        /** @var Item04|ActiveWorkflowBehavior $item */
    	$item = new Item04();

    	$this->assertTrue($item->hasWorkflowStatus() == false);

    	$this->specify('the initial status is returned as next status',function() use ($item) {

    		$n = $item->getNextStatuses();

    		expect('array is returned',is_array($n) )->true();
    		expect('array has 1 items',count($n) )->equals(1);
     		expect('status Item04Workflow/A is returned as index',isset($n['Item04Workflow/A']) )->true();

            /** @var IStatus $sts */
            $sts = $n['Item04Workflow/A']['status'];

     		expect('status Item04Workflow/A is returned as Status',$sts->getId() )->equals('Item04Workflow/A');

     		verify('status returned is the initial status',$item
     			->getWorkflowFactory()
     			->getWorkflow('Item04Workflow', $item)
     			->getInitialStatusId() )->equals($sts->getId());
    	});
    }

    public function testGetNextStatusFails()
    {
        /** @var Item04|ActiveWorkflowBehavior $item */
    	$item = new Item04();
    	$item->detachBehavior('workflow');
    	$item->attachBehavior('workflowForTest', [
    		'class' => ActiveWorkflowBehavior::className(),
    		'defaultWorkflowId' => 'INVALID_ID'
    	]);

    	$this->specify('getNextStatus throws exception if default workflow Id is invalid',function() use ($item) {
			$this->setExpectedException(
				'fproject\workflow\core\WorkflowException',
				"Invalid workflow Id : 'INVALID_ID'"
    		);
    		$item->getNextStatuses();
    	});
    }


    public function testReturnReportWithEventsOnEnterWorkflow()
    {
        /** @var Item04|ActiveWorkflowBehavior $model */
    	$model = new Item04();
    	$model->on(
    		WorkflowEvent::beforeEnterStatus('Item04Workflow/A'),
    		function($event)  {/** @var WorkflowEvent $event*/
    			$event->invalidate('my error message');
    		}
    	);

    	$report = $model->getNextStatuses(false,true);
    	$this->assertCount(1, $report);
    	$this->assertArrayHasKey('Item04Workflow/A', $report);
    	$this->assertInstanceOf('fproject\workflow\core\Status', $report['Item04Workflow/A']['status']);

    	$this->assertCount(2, $report['Item04Workflow/A']['event']);

    	$this->assertEquals(
    		[
	            0 => [
	                'name' => 'beforeEnterWorkflow{Item04Workflow}',
	                'success' => null
	            ],
	            1 => [
	                'name' => 'beforeEnterStatus{Item04Workflow/A}',
	                'success' => false,
	                'messages' => [
	                    0 => 'my error message'
	                ]
	            ]
	        ],
			$report['Item04Workflow/A']['event']
    	);
		$this->assertEquals(false, $report['Item04Workflow/A']['isValid']);
    }

    public function testReturnReportWithValidation()
    {
        /** @var Item05|ActiveWorkflowBehavior $model */
    	// prepare
    	$model = new Item05();
    	$model->status = 'Item05Workflow/new';
    	verify_that($model->save());

    	// test
    	$report = $model->getNextStatuses(true,false);
    	$this->assertCount(2, $report,' report contains 2 entries as 2 statuses can be reached from "new"');

    	$this->assertArrayHasKey('Item05Workflow/correction', $report,'  a transition exists between "new" and "correction" ');
    	$this->assertTrue($report['Item05Workflow/correction']['isValid'] == false);
    	$this->assertInstanceOf('fproject\workflow\core\Status', $report['Item05Workflow/correction']['status']);

        /** @var IStatus $sts */
        $sts = $report['Item05Workflow/correction']['status'];

    	$this->assertEquals('Item05Workflow/correction', $sts->getId());

    	$this->assertEquals(
    		[
	            0 => [
	                'scenario' => 'leave status {Item05Workflow/new}',
	                'success' => null
	            ],
	            1 => [
	                'scenario' => 'from {Item05Workflow/new} to {Item05Workflow/correction}',
	                'success' => false,
	                'errors' => [
	                    'name' => [
	                        0 => 'Name cannot be blank.'
	                    ]
	                ]
	            ],
	            2 => [
	                'scenario' => 'enter status {Item05Workflow/correction}',
	                'success' => null
	            ]
    		],
    		$report['Item05Workflow/correction']['validation']
    	);


    	$this->assertArrayHasKey('Item05Workflow/published',  $report,'  a transition exists between "new" and "published" ');
    	$this->assertTrue($report['Item05Workflow/published']['isValid'] == true);
    	$this->assertInstanceOf('fproject\workflow\core\Status', $report['Item05Workflow/published']['status']);

        /** @var IStatus $sts */
        $sts = $report['Item05Workflow/published']['status'];
    	$this->assertEquals('Item05Workflow/published', $sts->getId());

    	$this->assertEquals(
			[
	            0 => [
	                'scenario' => 'leave status {Item05Workflow/new}',
	                'success' => null
	            ],
	            1 => [
	                'scenario' => 'from {Item05Workflow/new} to {Item05Workflow/published}',
	                'success' => null
	            ],
	            2 => [
	                'scenario' => 'enter status {Item05Workflow/published}',
	                'success' => true
	            ]
	        ],
    		$report['Item05Workflow/published']['validation']
    	);
    }

    public function testReturnReportWithNothing()
    {
        /** @var Item05|ActiveWorkflowBehavior $model */
    	// prepare
    	$model = new Item05();
    	$model->status = 'Item05Workflow/new';
    	verify_that($model->save());

    	// test
    	$report = $model->getNextStatuses();
    	$this->assertCount(2, $report,' report contains 2 entries as 2 statuses can be reached from "new"');
    	$this->assertArrayHasKey('Item05Workflow/correction', $report,'  a transition exists between "new" and "correction" ');
    	$this->assertArrayHasKey('Item05Workflow/published',  $report,'  a transition exists between "new" and "published" ');

    	$this->assertTrue( !isset($report['Item05Workflow/correction']['isValid']));
    	$this->assertTrue( !isset($report['Item05Workflow/correction']['validation']));
    	$this->assertTrue( !isset($report['Item05Workflow/correction']['event']));

    	$this->assertTrue( !isset($report['Item05Workflow/published']['isValid']));
    	$this->assertTrue( !isset($report['Item05Workflow/published']['validation']));
    	$this->assertTrue( !isset($report['Item05Workflow/published']['event']));

    }

    public function testReturnEmptyReport()
    {
        /** @var Item04|ActiveWorkflowBehavior $model */
    	$model = $this->items('item4'); // status = D
    	$report = $model->getNextStatuses();
    	$this->assertCount(0, $report,' report contains no entries : D does not have any next status ');

    }
}
