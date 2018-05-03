<?php
namespace Flownative\Flow\ExtraPrivileges\Tests\Functional\Security\Authorization\Privilege\Entity;

/*
 * This file is part of the Flownative.Flow.ExtraPrivileges package.
 *
 * (c) Flownative GmbH - www.flownative.com
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\EntityManagerInterface;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Flownative\Flow\ExtraPrivileges\Tests\Functional\Security\Fixtures;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Testcase for authorization privileges.
 */
class ExtraPrivilegesTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected $testableSecurityEnabled = true;

    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var Fixtures\Domain\Repository\InvoiceRepository
     */
    protected $invoiceRepository;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof PersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }

        $this->invoiceRepository = new Fixtures\Domain\Repository\InvoiceRepository();
    }

    /**
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        $this->objectManager->forgetInstance(EntityManagerInterface::class);
        $this->objectManager->forgetInstance(PersistenceManagerInterface::class);
    }

    /*
     * Adding non-expensive and expensive invoices by everybody, regular and privileged
     */

    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception\AccessDeniedException
     */
    public function everybodyIsNotAllowedToAddInvoices()
    {
        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(5);

        $this->invoiceRepository->add($invoice);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception\AccessDeniedException
     */
    public function everybodyIsNotAllowedToAddExpensiveInvoices()
    {
        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(100);

        $this->invoiceRepository->add($invoice);
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function regularUserIsAllowedToAddInvoices()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:RegularUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(5);

        $this->invoiceRepository->add($invoice);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception\AccessDeniedException
     */
    public function regularUserIsNotAllowedToAddExpensiveInvoices()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:RegularUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(100);

        $this->invoiceRepository->add($invoice);
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function privilegedUserIsAllowedToAddInvoices()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:PrivilegedUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(5);

        $this->invoiceRepository->add($invoice);
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function privilegedUserIsAllowedToAddExpensiveInvoices()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:PrivilegedUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(100);

        $this->invoiceRepository->add($invoice);
    }

    /*
     * Fetching non-expensive and expensive invoices by everybody, regular and privileged
     */

    /**
     * @test
     */
    public function everybodyIsNotAllowedToSeeInvoices()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:RegularUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(5);

        $this->invoiceRepository->add($invoice);
        $invoiceIdentifier = $this->persistenceManager->getIdentifierByObject($invoice);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $this->authenticateRoles([]);

        $result = $this->invoiceRepository->findAll();
        $this->assertTrue(count($result) === 0);

        $this->assertNull($this->persistenceManager->getObjectByIdentifier($invoiceIdentifier, Fixtures\Domain\Model\Invoice::class));
    }

    /**
     * @test
     */
    public function everybodyIsNotAllowedToSeeExpensiveInvoices()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:PrivilegedUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(100);

        $this->invoiceRepository->add($invoice);
        $invoiceIdentifier = $this->persistenceManager->getIdentifierByObject($invoice);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $this->authenticateRoles([]);

        $result = $this->invoiceRepository->findAll();
        $this->assertTrue(count($result) === 0);

        $this->assertNull($this->persistenceManager->getObjectByIdentifier($invoiceIdentifier, Fixtures\Domain\Model\Invoice::class));
    }

    /**
     * @test
     */
    public function regularUserIsAllowedToSeeInvoices()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:RegularUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(5);

        $this->invoiceRepository->add($invoice);
        $invoiceIdentifier = $this->persistenceManager->getIdentifierByObject($invoice);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->invoiceRepository->findAll();
        $this->assertTrue(count($result) === 1);

        $this->assertNotNull($this->persistenceManager->getObjectByIdentifier($invoiceIdentifier, Fixtures\Domain\Model\Invoice::class));
    }

    /**
     * @test
     */
    public function regularUserIsNotAllowedToSeeExpensiveInvoices()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:PrivilegedUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(100);

        $this->invoiceRepository->add($invoice);
        $invoiceIdentifier = $this->persistenceManager->getIdentifierByObject($invoice);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:RegularUser']);

        $result = $this->invoiceRepository->findAll();
        $this->assertTrue(count($result) === 0);

        $this->assertNull($this->persistenceManager->getObjectByIdentifier($invoiceIdentifier, Fixtures\Domain\Model\Invoice::class));
    }

    /**
     * @test
     */
    public function privilegedUserIsAllowedToSeeInvoices()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:PrivilegedUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(5);

        $this->invoiceRepository->add($invoice);
        $invoiceIdentifier = $this->persistenceManager->getIdentifierByObject($invoice);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->invoiceRepository->findAll();
        $this->assertTrue(count($result) === 1);

        $this->assertNotNull($this->persistenceManager->getObjectByIdentifier($invoiceIdentifier, Fixtures\Domain\Model\Invoice::class));
    }

    /**
     * @test
     */
    public function privilegedUserIsAllowedToSeeExpensiveInvoices()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:PrivilegedUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(100);

        $this->invoiceRepository->add($invoice);
        $invoiceIdentifier = $this->persistenceManager->getIdentifierByObject($invoice);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->invoiceRepository->findAll();
        $this->assertTrue(count($result) === 1);

        $this->assertNotNull($this->persistenceManager->getObjectByIdentifier($invoiceIdentifier, Fixtures\Domain\Model\Invoice::class));
    }

    /*
     * Updating non-expensive and expensive invoices by everybody, regular and privileged
     */

    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception\AccessDeniedException
     */
    public function everybodyIsNotAllowedToUpdateInvoices()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:RegularUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(5);
        $this->invoiceRepository->add($invoice);
        $this->persistenceManager->persistAll();

        $this->authenticateRoles([]);

        $invoice->setAmount(6);
        $this->invoiceRepository->update($invoice);
        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception\AccessDeniedException
     */
    public function everybodyIsNotAllowedToUpdateExpensiveInvoices()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:PrivilegedUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(100);
        $this->invoiceRepository->add($invoice);
        $this->persistenceManager->persistAll();

        $this->authenticateRoles([]);

        $invoice->setAmount(6);
        $this->invoiceRepository->update($invoice);
        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception\AccessDeniedException
     */
    public function everybodyIsNotAllowedToMakeInvoicesExpensive()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:PrivilegedUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(5);
        $this->invoiceRepository->add($invoice);
        $this->persistenceManager->persistAll();

        $this->authenticateRoles([]);

        $invoice->setAmount(100);
        $this->invoiceRepository->update($invoice);
        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function regularUserIsAllowedToUpdateInvoices()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:RegularUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(5);
        $this->invoiceRepository->add($invoice);
        $this->persistenceManager->persistAll();

        $invoice->setAmount(6);
        $this->invoiceRepository->update($invoice);
        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception\AccessDeniedException
     */
    public function regularUserIsNotAllowedToUpdateExpensiveInvoices()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:PrivilegedUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(100);
        $this->invoiceRepository->add($invoice);
        $this->persistenceManager->persistAll();

        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:RegularUser']);

        $invoice->setAmount(6);
        $this->invoiceRepository->update($invoice);
        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception\AccessDeniedException
     */
    public function regularUserIsNotAllowedToMakeInvoicesExpensive()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:PrivilegedUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(5);
        $this->invoiceRepository->add($invoice);
        $this->persistenceManager->persistAll();

        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:RegularUser']);

        $invoice->setAmount(100);
        $this->invoiceRepository->update($invoice);
        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function privilegedUserIsAllowedToUpdateInvoices()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:PrivilegedUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(5);
        $this->invoiceRepository->add($invoice);
        $this->persistenceManager->persistAll();

        $invoice->setAmount(6);
        $this->invoiceRepository->update($invoice);
        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function privilegedUserIsAllowedToUpdateExpensiveInvoices()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:PrivilegedUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(100);
        $this->invoiceRepository->add($invoice);
        $this->persistenceManager->persistAll();

        $invoice->setAmount(6);
        $this->invoiceRepository->update($invoice);
        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function privilegedUserIsAllowedToMakeInvoicesExpensive()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:PrivilegedUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(5);
        $this->invoiceRepository->add($invoice);
        $this->persistenceManager->persistAll();

        $invoice->setAmount(100);
        $this->invoiceRepository->update($invoice);
        $this->persistenceManager->persistAll();
    }

    /*
     * Deleting non-expensive and expensive invoices by everybody, regular and privileged
     */

    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception\AccessDeniedException
     */
    public function everybodyIsNotAllowedToDeleteInvoices()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:RegularUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(5);
        $this->invoiceRepository->add($invoice);
        $this->persistenceManager->persistAll();

        $this->authenticateRoles([]);

        $this->invoiceRepository->remove($invoice);
        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception\AccessDeniedException
     */
    public function everybodyIsNotAllowedToDeleteExpensiveInvoices()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:PrivilegedUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(100);
        $this->invoiceRepository->add($invoice);
        $this->persistenceManager->persistAll();

        $this->authenticateRoles([]);

        $this->invoiceRepository->remove($invoice);
        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function regularUserIsAllowedToDeleteInvoices()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:RegularUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(5);
        $this->invoiceRepository->add($invoice);
        $this->persistenceManager->persistAll();

        $this->invoiceRepository->remove($invoice);
        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception\AccessDeniedException
     */
    public function regularUserIsNotAllowedToDeleteExpensiveInvoices()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:PrivilegedUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(100);
        $this->invoiceRepository->add($invoice);
        $this->persistenceManager->persistAll();

        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:RegularUser']);

        $this->invoiceRepository->remove($invoice);
        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function privilegedUserIsAllowedToDeleteInvoices()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:PrivilegedUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(5);
        $this->invoiceRepository->add($invoice);
        $this->persistenceManager->persistAll();

        $this->invoiceRepository->remove($invoice);
        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function privilegedUserIsAllowedToDeleteExpensiveInvoices()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:PrivilegedUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(100);
        $this->invoiceRepository->add($invoice);
        $this->persistenceManager->persistAll();

        $this->invoiceRepository->remove($invoice);
        $this->persistenceManager->persistAll();
    }

    /*
     * Further tests
     */

    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception\AccessDeniedException
     */
    public function regularUserIsNotAllowedToUpdateInvoiceRecipient()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:PrivilegedUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(5);
        $invoice->setRecipient('John Doe');
        $this->invoiceRepository->add($invoice);
        $this->persistenceManager->persistAll();

        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:RegularUser']);

        $invoice->setRecipient('Jane Smith');
        $this->invoiceRepository->update($invoice);
        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function privilegedUserIsAllowedToUpdateInvoiceRecipient()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:PrivilegedUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(5);
        $invoice->setRecipient('John Doe');
        $this->invoiceRepository->add($invoice);
        $this->persistenceManager->persistAll();

        $invoice->setRecipient('Jane Smith');
        $this->invoiceRepository->update($invoice);
        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception\AccessDeniedException
     */
    public function regularUserIsNotAllowedToUpdateInvoiceRecipientAndAmount()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:PrivilegedUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(5);
        $invoice->setRecipient('John Doe');
        $this->invoiceRepository->add($invoice);
        $this->persistenceManager->persistAll();

        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:RegularUser']);

        $invoice->setAmount(6);
        $invoice->setRecipient('Jane Smith');
        $this->invoiceRepository->update($invoice);
        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function privilegedUserIsAllowedToUpdateInvoiceRecipientAndAmount()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:PrivilegedUser']);

        $invoice = new Fixtures\Domain\Model\Invoice();
        $invoice->setAmount(5);
        $invoice->setRecipient('John Doe');
        $this->invoiceRepository->add($invoice);
        $this->persistenceManager->persistAll();

        $invoice->setAmount(6);
        $invoice->setRecipient('Jane Smith');
        $this->invoiceRepository->update($invoice);
        $this->persistenceManager->persistAll();
    }
}
