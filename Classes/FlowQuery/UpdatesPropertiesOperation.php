<?php
namespace Flownative\Flow\ExtraPrivileges\FlowQuery;

/*
 * This file is part of the Flownative.Flow.ExtraPrivileges package.
 *
 * (c) Flownative GmbH - www.flownative.com
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\FlowQueryException;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\ObjectAccess;

/**
 * Compare properties of two objects.
 *
 * Expects the names of properties as first argument, and two subjects in the
 * context as elements.
 *
 * If the context is empty, false is returned. Otherwise the value of the properties
 * of the first context element is compared to the ones on the second. If they all
 * differ, true is returned.
 */
class UpdatesPropertiesOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'updatesProperties';

    /**
     * {@inheritdoc}
     *
     * @var boolean
     */
    protected static $final = true;

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments the property name to use (in index 0)
     * @return mixed
     * @throws FlowQueryException
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        if (!isset($arguments[0]) || empty($arguments[0]) || !is_array($arguments[0])) {
            throw new FlowQueryException('updatesProperties() must be given an array of property names as first argument.', 1525341065);
        }

        $context = $flowQuery->getContext();
        if (!isset($context[0]) || !isset($context[1])) {
            return false;
        }

        $element = $context[0];
        $otherElement = $context[1];
        $propertyPaths = $arguments[0];

        foreach ($propertyPaths as $propertyPath) {
            if (ObjectAccess::getPropertyPath($element, $propertyPath) === ObjectAccess::getPropertyPath($otherElement, $propertyPath)) {
                return false;
            }
        }

        return true;
    }
}
