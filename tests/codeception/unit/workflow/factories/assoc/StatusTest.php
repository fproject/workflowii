<?php

namespace tests\unit\workflow\factories\assoc;

use fproject\workflow\core\ArrayWorkflowItemFactory;
use Yii;
use yii\codeception\TestCase;

class StatusTest extends TestCase
{
	use \Codeception\Specify;

    /** @var  ArrayWorkflowItemFactory */
	public $src;

	protected function setUp()
	{
		parent::setUp();
		$this->src = new ArrayWorkflowItemFactory();
	}

    /**
     * @expectedException fproject\workflow\core\WorkflowValidationException
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
    	$this->src->addWorkflowDefinition('wid', [
			'initialStatusId' => 'A',
    		'status' => [
				'A' => [
					'label' => 'label A'
    			],
    			'B' => []
    		]
    	]);
    	$this->specify('status can be obtained',function() {
			$w = $this->src->getWorkflow('wid', null);
			verify('non null workflow instance is returned',  $w != null)->true();

			verify('workflow contains status A', $this->src->getStatus('wid/A', null, null) != null)->true();

			verify('initial status is A ', $w->getInitialStatusId())->equals('wid/A');


			verify('status A has correct id', $this->src->getStatus('wid/A', null, null)->getId() )->equals('wid/A');
			verify('status A has correct label', $this->src->getStatus('wid/A', null, null)->getLabel() )->equals('label A');

			verify('workflow contains status B', $this->src->getStatus('wid/B', null, null) != null)->true();
			verify('status B has correct id', $this->src->getStatus('wid/B', null, null)->getId() )->equals('wid/B');
			verify('status B has default label', $this->src->getStatus('wid/B', null, null)->getLabel() )->equals('B');

			//verify('workflow does not contains status C', $this->src->getStatus('wid/C') == null)->true();
    	});
    }
    public function testLoadStatusSuccess2()
    {
    	$this->src->addWorkflowDefinition('wid', [
    		'initialStatusId' => 'A',
    		'status' => [
    			'A' => null
    		]
    	]);
    	$this->specify('a null status definition is not allowed',function() {
    		$w = $this->src->getWorkflow('wid', null);
    		verify('non null workflow instance is returned',  $w != null)->true();
    		verify('status A cannot be loaded', $this->src->getStatus('wid/A', null, null) !== null)->true();
    	});
    }
    public function testStatusCached()
    {
    	$this->src->addWorkflowDefinition('wid', [
    		'initialStatusId' => 'A',
    		'status' => [
    			'A' => []
    		]
    	]);

    	$this->specify('status are loaded once',function() {
    		$this->src->getWorkflow('wid', null);
    		verify('status instances are the same', spl_object_hash($this->src->getStatus('wid/A', null, null)))->equals(spl_object_hash($this->src->getStatus('wid/A', null, null)));
    	});
    }
}
