<?php
namespace Flownative\Flow\ExtraPrivileges\ViewHelpers\Security;

/*
 * This file is part of the Flownative.Flow.ExtraPrivileges package.
 *
 * (c) Flownative GmbH - www.flownative.com
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\EntityManagerInterface as DoctrineEntityManagerInterface;
use Flownative\Flow\ExtraPrivileges\Security\Authorization\Privilege\Entity\EntityPrivilegeSubject;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManager;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Flow\Security\Policy\Role;
use Neos\FluidAdaptor\ViewHelpers\Security\IfAccessViewHelper as FluidAdaptorIfAccessViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * This view helper implements an ifAccess/else condition.
 *
 * = Examples =
 *
 * <code title="Basic usage">
 * <f:security.ifAccess privilegeTarget="somePrivilegeTargetIdentifier">
 *   This is being shown in case you have access to the given privilege
 * </f:security.ifAccess>
 * </code>
 *
 * Everything inside the <f:ifAccess> tag is being displayed if you have access to the given privilege.
 *
 * <code title="IfAccess / then / else">
 * <f:security.ifAccess privilegeTarget="somePrivilegeTargetIdentifier">
 *   <f:then>
 *     This is being shown in case you have access.
 *   </f:then>
 *   <f:else>
 *     This is being displayed in case you do not have access.
 *   </f:else>
 * </f:security.ifAccess>
 * </code>
 *
 * Everything inside the "then" tag is displayed if you have access.
 * Otherwise, everything inside the "else"-tag is displayed.
 *
 * <code title="Inline syntax with privilege parameters">
 * {f:security.ifAccess(privilegeTarget: 'someTarget', parameters: '{param1: \'value1\'}', then: 'has access', else: 'has no access')}
 * </code>
 *
 * If you check against an entity privilege, you need to give the entity to check against as "subject":
 *
 * <code title="Inline syntax with privilege subject">
 * {f:security.ifAccess(privilegeTarget: 'someTarget', subject: {someEntity}, then: 'has access', else: 'has no access')}
 * </code>
 *
 * @api
 */
class IfAccessViewHelper extends FluidAdaptorIfAccessViewHelper
{
    /**
     * Initializes the "subject" argument
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('subject', 'object', 'The subject to check, needed for entity privileges', false, []);
    }

    /**
     * @param array $arguments
     * @param RenderingContextInterface $renderingContext
     * @return boolean
     * @throws \Neos\Flow\ObjectManagement\Exception\UnknownObjectException
     * @throws \Neos\Flow\Security\Exception\InvalidPrivilegeTypeException
     */
    protected static function evaluateCondition($arguments = null, RenderingContextInterface $renderingContext)
    {
        /** @var ObjectManager $objectManager */
        $objectManager = $renderingContext->getObjectManager();
        $securityContext = $objectManager->get(Context::class);
        if ($securityContext != null && !$securityContext->canBeInitialized()) {
            return false;
        }

        $privilegeTargetIdentifier = $arguments['privilegeTarget'];
        $privilegeParameters = $arguments['parameters'];
        $subject = $arguments['subject'];

        $originalEntityData = (object)$objectManager->get(DoctrineEntityManagerInterface::class)->getUnitOfWork()->getOriginalEntityData($subject);
        $privilegeSubject = new EntityPrivilegeSubject($subject, $originalEntityData);

        $matchedPrivilegesCount = 0;
        $accessGrants = 0;
        $accessDenies = 0;
        $accessAbstains = 0;
        foreach ($securityContext->getRoles() as $role) {
            $privilege = $role->getPrivilegeForTarget($privilegeTargetIdentifier, $privilegeParameters);
            if ($privilege === null) {
                continue;
            }
            if ($privilege->matchesSubject($privilegeSubject)) {
                $matchedPrivilegesCount++;
                if ($privilege->isGranted()) {
                    $accessGrants++;
                } elseif ($privilege->isDenied()) {
                    $accessDenies++;
                } else {
                    $accessAbstains++;
                }
            }
        }

        if ($matchedPrivilegesCount === 0) {
            return true;
        }
        if ($accessDenies > 0) {
            return false;
        }
        if ($accessGrants > 0) {
            return true;
        }

        return false;
    }
}
