<?php
namespace tests\codeception\unit\models;

use Yii;
use fproject\workflow\core\IWorkflowSource;

class Item08Workflow1 implements IWorkflowSource
{
    public function getDefinition() {
        return [ 
            'initialStatusId' => 'draft',
            'status' => [
                'draft' => [
                    'transition' => ['correction']
                ],
                'correction' => [
                    'transition' => ['draft','ready']
                ],
                'ready' => [
                    'transition' => ['draft', 'correction', 'published']
                ],
                'published' => [
                    'transition' => ['ready', 'archived']
                ],
                'archived' => [
                    'transition' => ['ready']
                ]
            ]
        ];
    }
}