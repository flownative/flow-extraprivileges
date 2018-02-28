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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Authorization\Privilege\AbstractPrivilege as FlowAbstractPrivilege;

/**
 * An abstract privilege to secure entity operations.
 *
 * @Flow\Proxy(false)
 */
abstract class AbstractPrivilege extends FlowAbstractPrivilege
{
    /**
     * @return string
     */
    public function getMatcher()
    {
        $matcher = parent::getMatcher();
        if ($matcher[0] !== '$' && $matcher[1] !== '{') {
            $matcher = '${' . $matcher . '}';
        }

        return $matcher;
    }
}
