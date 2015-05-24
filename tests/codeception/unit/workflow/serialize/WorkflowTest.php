<?php

namespace tests\unit\workflow\serialize;

use Codeception\Specify;
use fproject\workflow\core\ArrayWorkflowItemFactory;
use tests\codeception\unit\models\Item00;
use Yii;
use yii\codeception\TestCase;

class WorkflowTest extends TestCase
{
	use Specify;

    /** @var  ArrayWorkflowItemFactory $factory*/
	public $factory;

	protected function setUp()
	{
		parent::setUp();
		$this->factory = new ArrayWorkflowItemFactory();
	}

	public function testIsValidWorkflowId()
	{
		$this->assertFalse($this->factory->isValidWorkflowId('workflow id'));
		$this->assertFalse($this->factory->isValidWorkflowId('-workflowId'));
		$this->assertFalse($this->factory->isValidWorkflowId(' workflowId'));
		$this->assertFalse($this->factory->isValidWorkflowId('workflowId/'));
		$this->assertFalse($this->factory->isValidWorkflowId('1'));
		$this->assertFalse($this->factory->isValidWorkflowId('WORKFLOW_id'));

		$this->assertTrue($this->factory->isValidWorkflowId('workflowId'));
		$this->assertTrue($this->factory->isValidWorkflowId('workflow-Id'));
		$this->assertTrue($this->factory->isValidWorkflowId('workflow01-Id02'));
		$this->assertTrue($this->factory->isValidWorkflowId('w01-2'));
	}

	public function testIsValidStatusId()
	{
		$this->assertFalse($this->factory->isValidStatusId('id'));
		$this->assertFalse($this->factory->isValidStatusId('/id'));
		$this->assertFalse($this->factory->isValidStatusId('id/'));
		$this->assertFalse($this->factory->isValidStatusId('/'));
		$this->assertFalse($this->factory->isValidStatusId('workflow_id/status_id'));
		$this->assertFalse($this->factory->isValidStatusId('workflow id/status id'));

		$this->assertTrue($this->factory->isValidStatusId('ID/ID'));
		$this->assertTrue($this->factory->isValidStatusId('workflow-id/status-id'));
	}

	public function testParseStatusId()
	{
		list($wId, $lid) = $this->factory->parseStatusId('Wid/Id', null, null);
		$this->assertEquals('Wid', $wId);
		$this->assertEquals('Id', $lid);
		$this->assertTrue(count($this->factory->parseStatusId('Wid/Id', null, null)) == 2);
	}

	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessageRegExp #No status definition found#
	 */	
	public function testAddInvalidWorkflowDefinition()
	{
		$this->factory->addWorkflowDefinition('wid', ['initialStatusId' => 'A']);
	}

	public function testGetWorkflowSourceClassNameFail()
	{
		$this->specify('exception thrown on invalid workflow id', function() {
			$this->factory->getWorkflowSourceClassName('', null);
		},['throws'=> 'fproject\workflow\core\WorkflowException']);
	}

    public function testGetWorkflowSourceClassNameSuccess1()
    {
        $this->factory->workflowSourceNamespace = null;
        $this->assertEquals('app\models\PostWorkflowSource', $this->factory->getWorkflowSourceClassName('PostWorkflow', null));
        $this->factory->workflowSourceNamespace = 'a\b\c';
        $this->assertEquals('a\b\c\PostWorkflowSource', $this->factory->getWorkflowSourceClassName('PostWorkflow', null));
        $this->factory->workflowSourceNamespace = '';
        $this->assertEquals('\PostWorkflowSource', $this->factory->getWorkflowSourceClassName('PostWorkflow', null));
    }

    public function testGetWorkflowSourceClassNameSuccess2()
    {
        $item = new Item00();
        $this->factory->workflowSourceNamespace = null;
        $this->assertEquals('tests\codeception\unit\models\Item00WorkflowSource', $this->factory->getWorkflowSourceClassName('Item00Workflow', $item));
    }

    public function testFailToLoadWorkflowSourceClass()
    {
    	$this->specify('incorrect status id format', function () {
    		$this->factory->getStatus('id', null, null);
    	},['throws' => 'fproject\workflow\core\WorkflowException']);

    	$this->specify('empty provider fails to load workflow from non-existant workflow class', function () {
    		$this->factory->getWorkflow('id', null);
    	},['throws' => 'fproject\workflow\core\WorkflowException']);

    	$this->specify('empty provider fails to load status from non-existant workflow class', function () {
    		$this->factory->getStatus('w/s', null, null);
    	},['throws' => 'fproject\workflow\core\WorkflowException']);

    	$this->specify('empty provider fails to load transition from non-existant workflow class', function ()  {
    		$this->factory->getTransitions('w/s', null, null);
    	},['throws' => 'fproject\workflow\core\WorkflowException']);
    }

    public function testLoadMinimalWorkflowSuccess()
    {
    	$src = new ArrayWorkflowItemFactory();
    	$src->addWorkflowDefinition('wid', [
    		'initialStatusId' => 'A',
    		'status' => ['A']
    	]);
    	
    	$this->specify('can load workflow', function () use ($src) {
    		$w = $src->getWorkflow('wid', null);
    		verify('a Workflow instance is returned', get_class($w) )->equals('fproject\workflow\core\Workflow');
    		verify('workflow id is consistent', $w->getId())->equals('wid');
    	});
    }

    public function testWorkflowCached()
    {
    	$this->factory->addWorkflowDefinition('wid', [
    		'initialStatusId' => 'A',
    		'status' => ['A']
    	]);

    	$this->specify('workflow are loaded once',function() {
    		verify('workflow instances are the same', spl_object_hash($this->factory->getWorkflow('wid', null)) )->equals(spl_object_hash($this->factory->getWorkflow('wid', null)));
    	});
    }
}
