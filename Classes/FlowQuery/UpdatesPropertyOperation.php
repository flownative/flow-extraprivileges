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
 * Compare a property of two objects.
 *
 * Expects the name of a property as first argument, and two subjects in the
 * context as elements.
 *
 * If the context is empty, false is returned. Otherwise the value of the property
 * of the first context element is compared to the one on the second. If they
 * differ, true is returned.
 */
class UpdatesPropertyOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'updatesProperty';

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
        if (!isset($arguments[0]) || empty($arguments[0]) || !is_string($arguments[0])) {
            throw new FlowQueryException('updatesProperty() must be given a property name as first argument.', 1525275955);
        }

        $context = $flowQuery->getContext();
        if (!isset($context[0]) || !isset($context[1])) {
            return false;
        }

        $element = $context[0];
        $otherElement = $context[1];
        $propertyPath = $arguments[0];

        return ObjectAccess::getPropertyPath($element, $propertyPath) !== ObjectAccess::getPropertyPath($otherElement, $propertyPath);
    }
}
