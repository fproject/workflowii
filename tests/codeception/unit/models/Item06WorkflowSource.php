<?php

namespace tests\codeception\unit\models;

use fproject\workflow\core\IWorkflowSource;

class Item06WorkflowSource implements IWorkflowSource
{
	public function getDefinition($model)
	{
		return [
			'initialStatusId' => 'new',
			'status' => [
				'new' => [
					'label' => 'New Item',
					'transition' => [
						'correction' => [],
						'published' => []
					]
				],
				'correction' => [
					'label' => 'In Correction',
					'transition' => [
						'published' => []
					]
				],
				'published' => [
					'label' => 'Published',
					'transition' => [
						'correction' => [],
						'archive' => []
					]
				],
				'archive' => [
					'label' => 'Archived',
					'transition' => []
				],
                'deleted' => [
                    'label' => 'Deleted',
                    'transition' => []
                ],
			]
		];
	}
}