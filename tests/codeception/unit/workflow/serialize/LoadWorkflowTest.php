<?php

namespace tests\unit\workflow\serialize;

use Codeception\Specify;
use fproject\workflow\core\ArrayWorkflowItemFactory;
use Yii;
use yii\codeception\TestCase;

class LoadWorkflowTest extends TestCase
{
	use Specify;

	public $src;

	protected function setUp()
	{
		parent::setUp();
		$this->src = new ArrayWorkflowItemFactory();
	}


    public function testLoadWorkflowSuccess1()
    {
    	$factory = new ArrayWorkflowItemFactory();
    	$factory->addWorkflowDefinition('wid', [
			'initialStatusId' => 'A',
			'status' => [
				'A' => [
					'label' => 'Entry',
					'transition' => ['B','A']
				],
				'B' => [
					'label' => 'Published',
					'transition' => ['A','C']
				],
				'C' => [
					'label' => 'node C',
					'transition' => ['A','D']
				],'D'
			]
		]);
    	
    	verify($factory->getStatus('wid/A', null, null))->notNull();
    	verify($factory->getStatus('wid/B', null, null))->notNull();
    	verify($factory->getStatus('wid/C', null, null))->notNull();
    	verify($factory->getStatus('wid/D', null, null))->notNull();
    	
    	verify(count($factory->getTransitions('wid/A', null, null)))->equals(2);
    }
    
    public function testLoadWorkflowSuccess2()
    {
    	$factory = new ArrayWorkflowItemFactory();
    	$factory->addWorkflowDefinition('wid', [
    		'initialStatusId' => 'A',
    		'status' => [
    			'A' => [
    				'label' => 'Entry',
    				'transition' => 'A,B'
    			],
    			'B' => [
    				'label' => 'Published',
    				'transition' => '  A  , B  '
    			],
    		]
    	]);
    	 
    	verify($factory->getStatus('wid/A', null, null))->notNull();
    	verify($factory->getStatus('wid/B', null, null))->notNull();
    	 
    	verify(count($factory->getTransitions('wid/A', null, null)))->equals(2);
    }    
}
