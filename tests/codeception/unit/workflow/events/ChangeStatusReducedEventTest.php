<?php

namespace tests\unit\workflow\events;

use Yii;
use yii\codeception\DbTestCase;
use yii\base\InvalidConfigException;
use fproject\workflow\core\WorkflowBehavior;
use tests\codeception\unit\models\Item04;
use fproject\workflow\core\WorkflowException;
use fproject\workflow\events\WorkflowEvent;
use fproject\workflow\core\Status;
use fproject\workflow\core\Transition;
use yii\base\Exception;

class ChangeStatusReducedEventTest extends DbTestCase
{
	use \Codeception\Specify;
	public $eventsBefore = [];
	public $eventsAfter = [];

	protected function setup()
	{
		parent::setUp();
		$this->eventsBefore = [];
		$this->eventsAfter = [];

		Yii::$app->set('workflowSource',[
			'class'=> 'fproject\workflow\factory\assoc\WorkflowArrayFactory',
			'namespace' => 'tests\codeception\unit\models'
		]);
		Yii::$app->set('eventSequence',[
			'class'=> 'fproject\workflow\events\ReducedEventSequence',
		]);

		$this->model = new Item04();
		$this->model->attachBehavior('workflow', [
			'class' => WorkflowBehavior::className()
		]);
	}

    protected function tearDown()
    {
    	$this->model->delete();
        parent::tearDown();
    }

    public function testChangeStatusEventOnSaveSuccess()
    {
    	$this->model->on(
    		WorkflowEvent::beforeChangeStatus('Item04Workflow/A', 'Item04Workflow/B'),
    		function($event) {
    			$this->eventsBefore[] = $event;
    		}
    	);
    	$this->model->on(
    		WorkflowEvent::afterChangeStatus('Item04Workflow/A', 'Item04Workflow/B'),
    		function($event) {
    			$this->eventsAfter[] = $event;
    		}
    	);

    	$this->model->enterWorkflow();
    	verify('current status is set',$this->model->hasWorkflowStatus())->true();
    	verify('event handler handlers have been called', count($this->eventsBefore) == 0 &&   count($this->eventsAfter) == 0)->true();

    	$this->model->status = 'Item04Workflow/B';
    	verify('save succeeds',$this->model->save())->true();

    	expect('model has changed to status B',$this->model->getWorkflowStatus()->getId())->equals('Item04Workflow/B');
    	expect('beforeChangeStatus handler has been called',count($this->eventsBefore))->equals(1);
    	expect('afterChangeStatus handler has been called',count($this->eventsAfter))->equals(1);
    }

    public function testChangeStatusEventOnSaveFails()
    {
    	$this->model->on(
    		WorkflowEvent::beforeChangeStatus('Item04Workflow/A', 'Item04Workflow/B'),
    		function($event) {
    			$this->eventsBefore[] = $event;
    			$event->isValid = false;
    		}
    	);
    	$this->model->on(
    		WorkflowEvent::afterChangeStatus('Item04Workflow/A', 'Item04Workflow/B'),
    		function($event) {
    			$this->eventsAfter[] = $event;
    		}
    	);
    	$this->model->enterWorkflow();
    	verify('current status is set',$this->model->hasWorkflowStatus())->true();
    	verify('event handlers have never been called', count($this->eventsBefore) == 0 &&   count($this->eventsAfter) == 0)->true();

    	$this->model->status = 'Item04Workflow/B';
    	verify('save fails',$this->model->save())->false();

    	expect('model has not changed status',$this->model->getWorkflowStatus()->getId())->equals('Item04Workflow/A');
    	expect('beforeChangeStatus handler has been called',count($this->eventsBefore))->equals(1);
    	expect('afterChangeStatus handler has not been called',count($this->eventsAfter))->equals(0);
    }
}
