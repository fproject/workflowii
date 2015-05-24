<?php

namespace tests\unit\workflow\serialize;

use fproject\workflow\core\ArrayWorkflowItemFactory;
use Yii;
use yii\codeception\TestCase;

class WorkflowTest extends TestCase
{
	use \Codeception\Specify;

    /** @var  ArrayWorkflowItemFactory $src*/
	public $src;

	protected function setUp()
	{
		parent::setUp();
		$this->src = new ArrayWorkflowItemFactory();
	}

	public function testIsValidWorkflowId()
	{
		$this->assertFalse($this->src->isValidWorkflowId('workflow id'));
		$this->assertFalse($this->src->isValidWorkflowId('-workflowId'));
		$this->assertFalse($this->src->isValidWorkflowId(' workflowId'));
		$this->assertFalse($this->src->isValidWorkflowId('workflowId/'));
		$this->assertFalse($this->src->isValidWorkflowId('1'));
		$this->assertFalse($this->src->isValidWorkflowId('WORKFLOW_id'));

		$this->assertTrue($this->src->isValidWorkflowId('workflowId'));
		$this->assertTrue($this->src->isValidWorkflowId('workflow-Id'));
		$this->assertTrue($this->src->isValidWorkflowId('workflow01-Id02'));
		$this->assertTrue($this->src->isValidWorkflowId('w01-2'));
	}

	public function testIsValidStatusId()
	{
		$this->assertFalse($this->src->isValidStatusId('id'));
		$this->assertFalse($this->src->isValidStatusId('/id'));
		$this->assertFalse($this->src->isValidStatusId('id/'));
		$this->assertFalse($this->src->isValidStatusId('/'));
		$this->assertFalse($this->src->isValidStatusId('workflow_id/status_id'));
		$this->assertFalse($this->src->isValidStatusId('workflow id/status id'));

		$this->assertTrue($this->src->isValidStatusId('ID/ID'));
		$this->assertTrue($this->src->isValidStatusId('workflow-id/status-id'));
	}

	public function testParseStatusId()
	{
		list($wId, $lid) = $this->src->parseStatusId('Wid/Id', null, null);
		$this->assertEquals('Wid', $wId);
		$this->assertEquals('Id', $lid);
		$this->assertTrue(count($this->src->parseStatusId('Wid/Id', null, null)) == 2);
	}

	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessageRegExp #No status definition found#
	 */	
	public function testAddInvalidWorkflowDefinition()
	{
		$this->src->addWorkflowDefinition('wid', ['initialStatusId' => 'A']);
	}

	public function testGetWorkflowSourceClassName()
	{
		$this->src->namespace = 'a\b\c';
		$this->assertEquals('a\b\c\PostWorkflowSource', $this->src->getWorkflowSourceClassName('PostWorkflow'));
		$this->src->namespace = '';
		$this->assertEquals('\PostWorkflowSource', $this->src->getWorkflowSourceClassName('PostWorkflow'));

		$this->specify('exception thrown on invalid workflow id', function() {
			$this->src->getWorkflowSourceClassName('');
		},['throws'=> 'fproject\workflow\core\WorkflowException']);

	}

    public function testFailToLoadWorkflowSourceClass()
    {
    	$this->specify('incorrect status id format', function () {
    		$this->src->getStatus('id', null, null);
    	},['throws' => 'fproject\workflow\core\WorkflowException']);

    	$this->specify('empty provider fails to load workflow from non-existant workflow class', function () {
    		$this->src->getWorkflow('id', null);
    	},['throws' => 'fproject\workflow\core\WorkflowException']);

    	$this->specify('empty provider fails to load status from non-existant workflow class', function () {
    		$this->src->getStatus('w/s', null, null);
    	},['throws' => 'fproject\workflow\core\WorkflowException']);

    	$this->specify('empty provider fails to load transition from non-existant workflow class', function ()  {
    		$this->src->getTransitions('w/s', null, null);
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
    	$this->src->addWorkflowDefinition('wid', [
    		'initialStatusId' => 'A',
    		'status' => ['A']
    	]);

    	$this->specify('workflow are loaded once',function() {
    		verify('workflow instances are the same', spl_object_hash($this->src->getWorkflow('wid', null)) )->equals(spl_object_hash($this->src->getWorkflow('wid', null)));
    	});
    }
}
