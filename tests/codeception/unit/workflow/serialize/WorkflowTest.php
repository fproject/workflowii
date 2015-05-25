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
