<?php
namespace Flownative\Flow\ExtraPrivileges\Security\Authorization\Privilege\Entity;

/*
 * This file is part of the Flownative.Flow.ExtraPrivileges package.
 *
 * (c) Flownative GmbH - www.flownative.com
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\CompilingEvaluator;
use Neos\Eel\Utility;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeSubjectInterface;

/**
 * A privilege to secure entity creation.
 *
 * @Flow\Proxy(false)
 */
class CreatePrivilege extends AbstractPrivilege implements CreatePrivilegeInterface
{
    /**
     * Returns TRUE, if this privilege covers the given subject.
     *
     * @param PrivilegeSubjectInterface|EntityPrivilegeSubject $subject
     * @return boolean
     * @throws \Neos\Eel\Exception
     */
    public function matchesSubject(PrivilegeSubjectInterface $subject)
    {
        $context = ['entity' => $subject->getEntity(), 'originalEntityData' => $subject->getOriginalEntityData()];

        $evaluator = $this->objectManager->get(CompilingEvaluator::class);
        $result = Utility::evaluateEelExpression($this->getMatcher(), $evaluator, $context);

        return (bool)$result;
    }
}
