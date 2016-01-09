<?php

namespace tests\unit\workflow\activebehavior;

use Codeception\Specify;
use Yii;
use tests\codeception\unit\models\Item00;
use fproject\workflow\core\ActiveWorkflowBehavior;
use yii\codeception\TestCase;

class AutoInsertTest extends TestCase
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

    public function testSetAutoInsertWithTrue()
    {

    	$this->specify('autoInsert True : insert the model in default workflow', function() {
            /** @var ActiveWorkflowBehavior|Item00 $o */
	    	$o = new Item00();
	    	$o->attachBehavior('workflow', [
	    		'class' =>  ActiveWorkflowBehavior::className(),
	    		'autoInsert' => true,
	    		'defaultWorkflowId' => 'Item04Workflow'
	    	]);

	    	expect('model as status',
	    		$o->hasWorkflowStatus()
	    	)->true();

	    	expect('model status is Item04Workflow/A',
	    		$o->getWorkflowStatus()->getId()
	    	)->equals('Item04Workflow/A');

	    	expect('model status is initial status',
	    		$o->statusEquals($o->getWorkflow()->getInitialStatusId())
	    	)->true();

    	});
    	
    	$this->specify('autoInsert True : no update if status already set', function() {
            /** @var ActiveWorkflowBehavior|Item00 $o */
    		$o = new Item00();
    		$o->status = 'Item05Workflow/new';
    		$o->attachBehavior('workflow', [
    			'class' =>  ActiveWorkflowBehavior::className(),
    			'autoInsert' => true,
    			'defaultWorkflowId' => 'Item04Workflow'
    		]);

    		expect('model as status',
    			$o->hasWorkflowStatus()
    		)->true();

    		expect('model status is NOT Item04Workflow/A',
    			$o->getWorkflowStatus()->getId()
    		)->notEquals('Item04Workflow/A');

    		expect('model status is Item05Workflow/new',
    			$o->getWorkflowStatus()->getId()
    		)->equals('Item05Workflow/new');

    		expect('model status is initial status',
    			$o->statusEquals($o->getWorkflow()->getInitialStatusId())
    		)->true();
    	});
    }
    
    public function testSetAutoInsertWithWorkflowStatus()
    {
    	$this->specify('autoInsert Status : insert the model in provided workflow', function() {
            /** @var ActiveWorkflowBehavior|Item00 $o */
    		$o = new Item00();
    		$o->attachBehavior('workflow', [
    			'class' =>  ActiveWorkflowBehavior::className(),
    			'autoInsert' => 'Item05Workflow',
    			'defaultWorkflowId' => 'Item04Workflow'
    		]);
    
    		expect('model as status',
    			$o->hasWorkflowStatus()
    		)->true();
    		
    		expect('model status is Item05Workflow/new',
    			$o->getWorkflowStatus()->getId()
    		)->equals('Item05Workflow/new');
    		
    		expect('model status is initial status',
    			$o->statusEquals($o->getWorkflow()->getInitialStatusId())
    		)->true();
    
    	});
    	
    	$this->specify('autoInsert Status : no update if status already set', function() {
            /** @var ActiveWorkflowBehavior|Item00 $o */
    		$o = new Item00();
    		$o->status = 'Item05Workflow/new';
    		$o->attachBehavior('workflow', [
    			'class' =>  ActiveWorkflowBehavior::className(),
    			'autoInsert' => 'Item04Workflow',
    			'defaultWorkflowId' => 'Item04Workflow'
    		]);
    	
    		expect('model as status',
    			$o->hasWorkflowStatus()
    		)->true();
    		
    		expect('model status is NOT Item04Workflow/A',
    			$o->getWorkflowStatus()->getId()
    		)->notEquals('Item04Workflow/A');
    		
    		expect('model status is Item05Workflow/new',
    			$o->getWorkflowStatus()->getId()
    		)->equals('Item05Workflow/new');
    		
    		expect('model status is initial status',
    			$o->statusEquals($o->getWorkflow()->getInitialStatusId())
    		)->true();
    	});    	
    }    
    public function testSetAutoInsertWithDefaultValue()
    {
    	$this->specify('autoInsert Status : insert the model in provided workflow', function() {
            /** @var ActiveWorkflowBehavior|Item00 $o */
    		$o = new Item00();
    		$o->attachBehavior('workflow', [
    			'class' =>  ActiveWorkflowBehavior::className(),
    			'defaultWorkflowId' => 'Item04Workflow'
    		]);
    
    		expect('model as status',
    			$o->hasWorkflowStatus()
    		)->false();
    	});
    }    
    /**
	 * @expectedException fproject\workflow\core\WorkflowException
	 * @expectedExceptionMessage Failed to load workflow definition : Class tests\codeception\unit\models\NOTFOUNDSource does not exist
	 */	
    public function testAutoInsertFails1()
    {
    	$o = new Item00();
    	$o->attachBehavior('workflow', [
    		'class' =>  ActiveWorkflowBehavior::className(),
    		'autoInsert' => true,
    		'defaultWorkflowId' => 'NOTFOUND'
    	]);
    }   
    /**
     * @expectedException fproject\workflow\core\WorkflowException
     * @expectedExceptionMessage Failed to load workflow definition : Class tests\codeception\unit\models\NOTFOUNDSource does not exist
     */
    public function testAutoInsertFails2()
    {
    	$o = new Item00();
    	$o->attachBehavior('workflow', [
    		'class' =>  ActiveWorkflowBehavior::className(),
    		'autoInsert' => 'NOTFOUND',
    		'defaultWorkflowId' => 'Item04Workflow'
    	]);
    }     
}
