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

use Flownative\Flow\ExtraPrivileges\Security\Authorization\Privilege\Entity\ReadPrivilegeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Authorization\Privilege\Entity\Doctrine\EntityPrivilege;

/**
 * A filter to rewrite doctrine queries according to the security policy.
 *
 * This is a replacement for the `Entity\Doctrine\EntityPrivilege` of Flow, the latter will
 * be removed eventually.
 *
 * @Flow\Proxy(false)
 */
class ReadPrivilege extends EntityPrivilege implements ReadPrivilegeInterface
{

}
