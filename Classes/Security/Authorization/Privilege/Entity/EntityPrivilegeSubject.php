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

/**
 * An entity privilege subject
 */
class EntityPrivilegeSubject implements EntityPrivilegeSubjectInterface
{
    /**
     * @var object
     */
    protected $entity;

    /**
     * @var object
     */
    private $originalEntityData;

    /**
     * @param object $entity
     * @param object $originalEntityData
     */
    public function __construct($entity, $originalEntityData)
    {
        $this->entity = $entity;
        $this->originalEntityData = $originalEntityData;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return object
     */
    public function getOriginalEntityData()
    {
        return $this->originalEntityData;
    }
}
