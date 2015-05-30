<?php

namespace tests\unit\workflow\serialize;

use Codeception\Specify;
use fproject\workflow\core\ArrayWorkflowItemFactory;
use Yii;
use yii\codeception\TestCase;

class StatusTest extends TestCase
{
	use Specify;

    /** @var  ArrayWorkflowItemFactory */
	public $factory;

	protected function setUp()
	{
		parent::setUp();
		$this->factory = new ArrayWorkflowItemFactory();
	}

    /**
     * @expectedException fproject\workflow\core\WorkflowException
     * @expectedExceptionMessageRegExp #No status definition found#
     */
	public function testStatusNotFoundSuccess()
	{
		$src = new ArrayWorkflowItemFactory();
		$src->addWorkflowDefinition('wid', [
			'initialStatusId' => 'A',
			'status' => null
		]);

		$this->specify('status is not found', function () use ($src) {
			$status = $src->getStatus('wid/A', null, null);
			verify('a Workflow instance is returned', $status )->equals(null);
		});
	}
	
    public function testLoadStatusSuccess()
    {
    	$this->factory->addWorkflowDefinition('wid', [
			'initialStatusId' => 'A',
    		'status' => [
				'A' => [
					'label' => 'label A'
    			],
    			'B' => []
    		]
    	]);
    	$this->specify('status can be obtained',function() {
			$w = $this->factory->getWorkflow('wid', null);
			verify('non null workflow instance is returned',  $w != null)->true();

			verify('workflow contains status A', $this->factory->getStatus('wid/A', null, null) != null)->true();

			verify('initial status is A ', $w->getInitialStatusId())->equals('wid/A');


			verify('status A has correct id', $this->factory->getStatus('wid/A', null, null)->getId() )->equals('wid/A');
			verify('status A has correct label', $this->factory->getStatus('wid/A', null, null)->getLabel() )->equals('label A');

			verify('workflow contains status B', $this->factory->getStatus('wid/B', null, null) != null)->true();
			verify('status B has correct id', $this->factory->getStatus('wid/B', null, null)->getId() )->equals('wid/B');
			verify('status B has default label', $this->factory->getStatus('wid/B', null, null)->getLabel() )->equals('B');

			//verify('workflow does not contains status C', $this->src->getStatus('wid/C') == null)->true();
    	});
    }
    public function testLoadStatusSuccess2()
    {
    	$this->factory->addWorkflowDefinition('wid', [
    		'initialStatusId' => 'A',
    		'status' => [
    			'A' => null
    		]
    	]);
    	$this->specify('a null status definition is not allowed',function() {
    		$w = $this->factory->getWorkflow('wid', null);
    		verify('non null workflow instance is returned',  $w != null)->true();
    		verify('status A cannot be loaded', $this->factory->getStatus('wid/A', null, null) !== null)->true();
    	});
    }
    public function testStatusCached()
    {
    	$this->factory->addWorkflowDefinition('wid', [
    		'initialStatusId' => 'A',
    		'status' => [
    			'A' => []
    		]
    	]);

    	$this->specify('status are loaded once',function() {
    		$this->factory->getWorkflow('wid', null);
    		verify('status instances are the same', spl_object_hash($this->factory->getStatus('wid/A', null, null)))->equals(spl_object_hash($this->factory->getStatus('wid/A', null, null)));
    	});
    }
}
