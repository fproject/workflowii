<?php

namespace tests\unit\workflow\events;

use Yii;
use yii\codeception\TestCase;

use tests\codeception\unit\models\Item04;
use fproject\workflow\core\ActiveWorkflowBehavior;
use fproject\workflow\events\WorkflowEvent;

class EnterWorkflowReducedEventTest extends TestCase
{
	use \Codeception\Specify;

	protected function setup()
	{
		parent::setUp();
		$this->eventsBefore = [];
		$this->eventsAfter = [];

		Yii::$app->set('workflowFactory',[
			'class'=> 'fproject\workflow\factory\assoc\WorkflowArrayFactory',
			'namespace' => 'tests\codeception\unit\models'
		]);
		Yii::$app->set('eventSequence',[
			'class'=> 'fproject\workflow\events\ReducedEventSequence',
		]);

		$this->model = new Item04();
		$this->model->attachBehavior('workflow', [
			'class' => ActiveWorkflowBehavior::className()
		]);
	}

	protected function tearDown()
	{
		$this->model->delete();
		parent::tearDown();
	}

    public function testOnEnterWorkflowSuccess()
    {
    	$this->model->on(
    		WorkflowEvent::beforeEnterWorkflow('Item04Workflow'),
    		function($event) {
    			$this->eventsBefore[] = $event;
    		}
    	);
    	$this->model->on(
    		WorkflowEvent::afterEnterWorkflow('Item04Workflow'),
    		function($event) {
    			$this->eventsAfter[] = $event;
    		}
    	);

    	verify('event handler handlers have been called', count($this->eventsBefore) == 0 &&   count($this->eventsAfter) == 0)->true();

    	$this->model->enterWorkflow();

    	verify('current status is set',$this->model->hasWorkflowStatus())->true();

    	expect('beforeChangeStatus handler has been called',count($this->eventsBefore))->equals(1);
    	expect('afterChangeStatus handler has been called',count($this->eventsAfter))->equals(1);
    }

    public function testOnEnterWorkflowError()
    {
    	$this->model->on(
    		WorkflowEvent::beforeEnterWorkflow('Item04Workflow'),
    		function($event) {
    			$this->eventsBefore[] = $event;
    			$event->isValid = false;
    		}
    	);
    	$this->model->on(
    		WorkflowEvent::afterEnterWorkflow('Item04Workflow'),
    		function($event) {
    			$this->eventsAfter[] = $event;
    		}
    	);

    	verify('event handler handlers have been called', count($this->eventsBefore) == 0 &&   count($this->eventsAfter) == 0)->true();

    	$this->model->enterWorkflow();

    	verify('current status is not set',$this->model->hasWorkflowStatus())->false();

    	expect('beforeChangeStatus handler has been called',count($this->eventsBefore))->equals(1);
    	expect('afterChangeStatus handler has not been called',count($this->eventsAfter))->equals(0);
    }
}
