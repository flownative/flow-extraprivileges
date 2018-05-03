<?php
namespace Flownative\Flow\ExtraPrivileges\Security\Authorization\Privilege\Entity\Doctrine;

/*
 * This file is part of the Flownative.Flow.ExtraPrivileges package.
 *
 * (c) Flownative GmbH - www.flownative.com
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Flownative\Flow\ExtraPrivileges\Security\Authorization\Privilege\Entity\CreatePrivilegeInterface;
use Flownative\Flow\ExtraPrivileges\Security\Authorization\Privilege\Entity\DeletePrivilegeInterface;
use Flownative\Flow\ExtraPrivileges\Security\Authorization\Privilege\Entity\EntityPrivilegeSubject;
use Flownative\Flow\ExtraPrivileges\Security\Authorization\Privilege\Entity\UpdatePrivilegeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Authentication\AuthenticationManagerInterface;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Exception\AccessDeniedException;
use Neos\Flow\Security\Exception\AuthenticationRequiredException;
use Neos\Flow\Security\Exception\NoTokensAuthenticatedException;

/**
 * An onFlush listener for Flow's Doctrine PersistenceManager.
 *
 * Used to enforce privileges for create, update and delete operations.
 *
 * @Flow\Scope("singleton")
 * @api
 */
class PrivilegeEnforcementListener
{
    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var AuthenticationManagerInterface
     */
    protected $authenticationManager;

    /**
     * @Flow\Inject(lazy=false)
     * @var PrivilegeManagerInterface
     */
    protected $privilegeManager;

    /**
     * @Flow\Inject
     * @var ObjectManager
     */
    protected $entityManager;

    /**
     * @param LifecycleEventArgs $eventArgs
     * @return void
     * @throws AccessDeniedException
     * @throws AuthenticationRequiredException
     * @throws NoTokensAuthenticatedException
     */
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        if ($this->securityContext->canBeInitialized() === false) {
            return;
        }

        $noTokensAuthenticatedException = $this->authenticate();
        $entity = $eventArgs->getObject();
        $originalEntityData = $eventArgs->getEntityManager()->getUnitOfWork()->getOriginalEntityData($entity);

        $this->checkSubject($entity, $originalEntityData, CreatePrivilegeInterface::class, $noTokensAuthenticatedException);
    }

    /**
     * @param PreUpdateEventArgs $eventArgs
     * @return void
     * @throws AccessDeniedException
     * @throws AuthenticationRequiredException
     * @throws NoTokensAuthenticatedException
     */
    public function preUpdate(PreUpdateEventArgs $eventArgs)
    {
        if ($this->securityContext->canBeInitialized() === false) {
            return;
        }

        $noTokensAuthenticatedException = $this->authenticate();
        $entity = $eventArgs->getObject();
        $changeSet = $eventArgs->getEntityChangeSet();
        $alreadyUpdatedEntityData = $eventArgs->getEntityManager()->getUnitOfWork()->getOriginalEntityData($entity);
        $originalEntityData = array_merge($alreadyUpdatedEntityData, array_combine(array_keys($changeSet), array_column(array_values($changeSet), 0)));

        $this->checkSubject($entity, $originalEntityData, UpdatePrivilegeInterface::class, $noTokensAuthenticatedException);
    }

    /**
     * Removal must be checked using a preRemove event, since at this point the original entity data is still
     * available. Once the remove has been done, it is unset in the UoW by Doctrine.
     *
     * @param LifecycleEventArgs $eventArgs
     * @return void
     * @throws AccessDeniedException
     * @throws AuthenticationRequiredException
     * @throws NoTokensAuthenticatedException
     */
    public function preRemove(LifecycleEventArgs $eventArgs)
    {
        if ($this->securityContext->canBeInitialized() === false) {
            return;
        }

        $noTokensAuthenticatedException = $this->authenticate();
        $entity = $eventArgs->getObject();
        $originalEntityData = $eventArgs->getEntityManager()->getUnitOfWork()->getOriginalEntityData($entity);

        $this->checkSubject($entity, $originalEntityData, DeletePrivilegeInterface::class, $noTokensAuthenticatedException);
    }

    /**
     * Check if the given $entity matches any implementation of the given $privilegeType.
     *
     * @param object $entity
     * @param object|array $originalEntityData
     * @param string $privilegeType
     * @param NoTokensAuthenticatedException $noTokensAuthenticatedException
     * @return void
     * @throws AccessDeniedException
     * @throws NoTokensAuthenticatedException
     */
    protected function checkSubject($entity, $originalEntityData, $privilegeType, NoTokensAuthenticatedException $noTokensAuthenticatedException = null)
    {
        $subject = new EntityPrivilegeSubject($entity, $originalEntityData);
        $reason = '';
        if ($this->privilegeManager->isGranted($privilegeType, $subject, $reason) === false) {
            if ($noTokensAuthenticatedException instanceof NoTokensAuthenticatedException) {
                throw new NoTokensAuthenticatedException($noTokensAuthenticatedException->getMessage() . chr(10) . $reason, $noTokensAuthenticatedException->getCode());
            }
            throw new AccessDeniedException($this->renderDecisionReasonMessage($reason), 1519826436101);
        }
    }

    /**
     * @return \Exception|NoTokensAuthenticatedException|null
     * @throws AuthenticationRequiredException
     */
    protected function authenticate()
    {
        try {
            $noTokensAuthenticatedException = null;
            $this->authenticationManager->authenticate();
        } catch (EntityNotFoundException $exception) {
            throw new AuthenticationRequiredException('Could not authenticate. Looks like a broken session.', 1519643154519, $exception);
        } catch (NoTokensAuthenticatedException $noTokensAuthenticatedException) {
            // checked in checkSubject, the privilege could still be available to "Neos.Flow:Everybody"
        }

        return $noTokensAuthenticatedException;
    }

    /**
     * Returns a string message, giving insights what happened during privilege evaluation.
     *
     * @param string $privilegeReasonMessage
     * @return string
     */
    protected function renderDecisionReasonMessage($privilegeReasonMessage)
    {
        if (count($this->securityContext->getRoles()) === 0) {
            $rolesMessage = 'No authenticated roles';
        } else {
            $rolesMessage = 'Authenticated roles: ' . implode(', ', array_keys($this->securityContext->getRoles()));
        }

        return sprintf('Access denied for operation' . chr(10) . '%s' . chr(10) . chr(10) . '%s', $privilegeReasonMessage, $rolesMessage);
    }
}
