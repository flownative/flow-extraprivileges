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

use Neos\Eel\CompilingEvaluator;
use Neos\Eel\Utility;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Configuration\Exception\InvalidConfigurationTypeException;
use Neos\Flow\Security\Authorization\Privilege\AbstractPrivilege as FlowAbstractPrivilege;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeSubjectInterface;

/**
 * An abstract privilege to secure entity operations.
 *
 * @Flow\Proxy(false)
 */
abstract class AbstractPrivilege extends FlowAbstractPrivilege
{
    /**
     * Default context configuration with helper definitions
     *
     * @var array
     */
    protected $defaultContextConfiguration;

    /**
     * Returns TRUE, if this privilege covers the given subject.
     *
     * @param PrivilegeSubjectInterface|EntityPrivilegeSubjectInterface $subject
     * @return boolean
     * @throws \Neos\Eel\Exception
     */
    public function matchesSubject(PrivilegeSubjectInterface $subject)
    {
        if (!$subject instanceof EntityPrivilegeSubjectInterface) {
            return false;
        }

        $context = ['entity' => $subject->getEntity(), 'originalEntityData' => [$subject->getOriginalEntityData()]];

        return (bool)$this->evaluateEelExpression($this->getMatcher(), $context);
    }

    /**
     * Get variables from configuration that should be set in the context by default.
     * For example Eel helpers are made available by this.
     *
     * @return array Array with default context variable objects.
     */
    protected function getDefaultContextConfiguration()
    {
        if ($this->defaultContextConfiguration === null) {
            try {
                $this->defaultContextConfiguration = $this->objectManager->get(ConfigurationManager::class)->getConfiguration(
                    ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
                    'Flownative.Flow.ExtraPrivileges.defaultContext'
                );
                if (!is_array($this->defaultContextConfiguration)) {
                    $this->defaultContextConfiguration;
                }
            } catch (InvalidConfigurationTypeException $e) {
                $this->defaultContextConfiguration = [];
            }
        }

        return $this->defaultContextConfiguration;
    }

    /**
     * Evaluate the given $expression against the $context.
     *
     * The configured default context elements are added transparently.
     *
     * @param $expression
     * @param array $context
     * @return mixed
     * @throws \Neos\Eel\Exception
     */
    protected function evaluateEelExpression($expression, array $context)
    {
        $expression = trim($expression);
        if ($expression[0] !== '$' || $expression[1] !== '{') {
            // We still assume this is an Eel expression and wrap the markers
            $expression = '${' . $expression . '}';
        }

        $evaluator = $this->objectManager->get(CompilingEvaluator::class);
        $result = Utility::evaluateEelExpression($expression, $evaluator, $context, $this->getDefaultContextConfiguration());

        return $result;
    }
}
