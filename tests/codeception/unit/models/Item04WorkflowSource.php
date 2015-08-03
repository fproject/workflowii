<?php

namespace tests\codeception\unit\models;

use fproject\workflow\core\IWorkflowSource;

class Item04WorkflowSource implements IWorkflowSource
{
	public function getDefinition($model)
	{
		return [
			'initialStatusId' => 'A',
			'status' => [
				'A' => [
					'label' => 'Entry',
					'transition' => [
						'B' => [
                            'task' => 'taskId001'
                        ],
						'A' => []
					],
					'metadata' => [
						'color' => '#FF545669',
						'priority' => 1
					]
				],
				'B' => [
					'label' => 'Published',
					'transition' => [
						'A' => [],
						'C' => []
					]
				],
				'C' => [
					'label' => 'node C',
					'transition' => [
						'A' => [],
						'D' => []
					]
				],
				'D' => [
					'label' => 'node D',
					'transition' => []
				]
			]
		];
	}
}