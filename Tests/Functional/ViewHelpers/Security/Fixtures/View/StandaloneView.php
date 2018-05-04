<?php
namespace Flownative\Flow\ExtraPrivileges\Tests\Functional\ViewHelpers\Security\Fixtures\View;

/*
 * This file is part of the Flownative.Flow.ExtraPrivileges package.
 *
 * (c) Flownative GmbH - www.flownative.com
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\ActionRequest;
use Neos\FluidAdaptor\View\StandaloneView as FluidAdaptorStandaloneView;

/**
 * Extended StandaloneView for testing purposes
 */
class StandaloneView extends FluidAdaptorStandaloneView
{
    /**
     * @var string
     */
    protected $fileIdentifierPrefix = '';

    /**
     * Constructor
     *
     * @param ActionRequest $request The current action request. If none is specified it will be created from the environment.
     * @param string $fileIdentifierPrefix
     * @param array $options
     * @throws \Neos\FluidAdaptor\Exception
     */
    public function __construct(ActionRequest $request = null, string $fileIdentifierPrefix = '', array $options = [])
    {
        $this->fileIdentifierPrefix = $fileIdentifierPrefix;
        parent::__construct($request, $options);
    }

    /**
     * @param string $pathAndFilename
     * @param string $prefix
     * @return mixed
     */
    protected function createIdentifierForFile(string $pathAndFilename, string $prefix)
    {
        return parent::createIdentifierForFile($pathAndFilename, $this->fileIdentifierPrefix . $prefix);
    }
}
