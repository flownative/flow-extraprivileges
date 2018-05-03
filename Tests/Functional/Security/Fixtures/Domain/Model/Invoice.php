<?php
namespace Flownative\Flow\ExtraPrivileges\Tests\Functional\Security\Fixtures\Domain\Model;

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

/**
 * @Flow\Entity
 */
class Invoice
{
    /**
     * @var integer
     */
    protected $amount;

    /**
     * @var string
     */
    protected $recipient = '';

    /**
     * @return integer
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param integer $amount
     * @return void
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * @param string $recipient
     * @return void
     */
    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;
    }
}
