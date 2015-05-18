<?php

namespace tests\codeception\unit\models;

use fproject\workflow\base\IWorkflowDefinitionProvider;

class Workflow1 implements IWorkflowDefinitionProvider
{

	public function getDefinition()
	{
		return [
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
				],
				'D'
			]
		];
	}
}