<?php
namespace tests\codeception\unit\models;

use fproject\workflow\core\IStatus;
use fproject\workflow\core\WorkflowException;


class MyStatus implements IStatus
{
	/* (non-PHPdoc)
	 * @see \fproject\workflow\core\IStatus::getId()
	 */
	public function getId() {
		// TODO: Auto-generated method stub

	}

	/* (non-PHPdoc)
	 * @see \fproject\workflow\core\IStatus::getLabel()
	 */
	public function getLabel() {
		// TODO: Auto-generated method stub

	}

	/* (non-PHPdoc)
	 * @see \fproject\workflow\core\IStatus::getWorkflowId()
	 */
	public function getWorkflowId() {
		// TODO: Auto-generated method stub

	}

	/* (non-PHPdoc)
	 * @see \fproject\workflow\core\IStatus::getTransitions()
	 */
	public function getTransitions() {
		// TODO: Auto-generated method stub

	}

    /**
     *
     * @param string $paramName when null the method returns the complet metadata array, otherwise it returns the
     * value of the corresponding metadata.
     * @param string $defaultValue
     *
     * @return string
     *
     * @throws WorkflowException
     */
    public function getMetadata($paramName = null, $defaultValue = null)
    {
        // TODO: Implement getMetadata() method.
    }
}