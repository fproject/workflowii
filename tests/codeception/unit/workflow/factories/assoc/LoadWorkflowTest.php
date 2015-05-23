<?php

namespace tests\unit\workflow\factories\assoc;

use fproject\workflow\core\ArrayWorkflowItemFactory;
use Yii;
use yii\codeception\TestCase;

class LoadWorkflowTest extends TestCase
{
	use \Codeception\Specify;

	public $src;

	protected function setUp()
	{
		parent::setUp();
		$this->src = new ArrayWorkflowItemFactory();
	}


    public function testLoadWorkflowSuccess1()
    {
    	$src = new ArrayWorkflowItemFactory();
    	$src->addWorkflowDefinition('wid', [
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
    	
    	verify($src->getStatus('wid/A', null, null))->notNull();
    	verify($src->getStatus('wid/B', null, null))->notNull();
    	verify($src->getStatus('wid/C', null, null))->notNull();
    	verify($src->getStatus('wid/D', null, null))->notNull();
    	
    	verify(count($src->getTransitions('wid/A', null, null)))->equals(2);
    }
    
    public function testLoadWorkflowSuccess2()
    {
    	$src = new ArrayWorkflowItemFactory();
    	$src->addWorkflowDefinition('wid', [
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
    	 
    	verify($src->getStatus('wid/A', null, null))->notNull();
    	verify($src->getStatus('wid/B', null, null))->notNull();
    	 
    	verify(count($src->getTransitions('wid/A', null, null)))->equals(2);
    }    
}
