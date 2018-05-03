<?php
namespace Flownative\Flow\ExtraPrivileges\Tests\Functional\Security\Fixtures\Domain\Repository;

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
use Neos\Flow\Persistence\QueryInterface;
use Neos\Flow\Persistence\Doctrine\Repository;

/**
 * @Flow\Scope("singleton")
 */
class InvoiceRepository extends Repository
{
    /**
     * @var array
     */
    protected $defaultOrderings = ['amount' => QueryInterface::ORDER_ASCENDING];
}
