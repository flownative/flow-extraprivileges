<?php
namespace Flownative\Flow\ExtraPrivileges\Tests\Functional\ViewHelpers\Security;

/*
 * This file is part of the Flownative.Flow.ExtraPrivileges package.
 *
 * (c) Flownative GmbH - www.flownative.com
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Flownative\Flow\ExtraPrivileges\Tests\Functional\Security\Fixtures\Domain\Model\Invoice;
use Flownative\Flow\ExtraPrivileges\Tests\Functional\Security\Fixtures\Domain\Repository\InvoiceRepository;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Uri;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Persistence\Doctrine\PersistenceManager as DoctrinePersistenceManager;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\FluidAdaptor\Tests\Functional\View\Fixtures\View\StandaloneView;

/**
 * Testcase for IfAccessViewHelper
 */
class IfAccessViewHelperTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected $testableSecurityEnabled = true;

    /**
     * @var string
     */
    protected $standaloneViewNonce = '23';

    /**
     * @Flow\Inject
     * @var InvoiceRepository
     */
    protected $invoiceRepository;

    /**
     * Every testcase should run *twice*. First, it is run in *uncached* way, second,
     * it is run *cached*. To make sure that the first run is always uncached, the
     * $standaloneViewNonce is initialized to some random value which is used inside
     * an overridden version of StandaloneView::createIdentifierForFile.
     */
    public function runBare(): void
    {
        $this->standaloneViewNonce = uniqid();
        parent::runBare();
        $numberOfAssertions = $this->getNumAssertions();
        parent::runBare();
        $this->addToAssertionCount($numberOfAssertions);
    }

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof DoctrinePersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }

        /** @noinspection PhpParamsInspection */
        $this->invoiceRepository = new InvoiceRepository();
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

    /**
     * @test
     */
    public function ifAccessRendersThenForGrantedPrivilegeTarget()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:PrivilegedUser']);

        $invoice = new Invoice();
        $invoice->setAmount(100);
        $this->invoiceRepository->add($invoice);
        $this->persistenceManager->persistAll();

        $httpRequest = Request::create(new Uri('http://localhost'));
        $actionRequest = new ActionRequest($httpRequest);

        $standaloneView = new StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->assign('invoice', $invoice);
        $standaloneView->setTemplateSource('{namespace ep=Flownative\\Flow\\ExtraPrivileges\\ViewHelpers}<ep:security.ifAccess privilegeTarget="Flownative.Flow.ExtraPrivileges:DeleteExpensiveInvoice" subject="{invoice}"><f:then>has access</f:then><f:else>has no access</f:else></ep:security.ifAccess>');

        $expected = 'has access';
        $actual = $standaloneView->render();
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function ifAccessRendersElseForDeniedPrivilegeTarget()
    {
        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:PrivilegedUser']);

        $invoice = new Invoice();
        $invoice->setAmount(100);
        $this->invoiceRepository->add($invoice);
        $this->persistenceManager->persistAll();

        $httpRequest = Request::create(new Uri('http://localhost'));
        $actionRequest = new ActionRequest($httpRequest);

        $standaloneView = new StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->assign('invoice', $invoice);
        $standaloneView->setTemplateSource('{namespace ep=Flownative\\Flow\\ExtraPrivileges\\ViewHelpers}<ep:security.ifAccess privilegeTarget="Flownative.Flow.ExtraPrivileges:DeleteExpensiveInvoice" subject="{invoice}"><f:then>has access</f:then><f:else>has no access</f:else></ep:security.ifAccess>');

        $this->authenticateRoles(['Flownative.Flow.ExtraPrivileges:RegularUser']);

        $expected = 'has no access';
        $actual = $standaloneView->render();
        $this->assertSame($expected, $actual);
    }
}
