<?php
namespace MCStreetguy\FusionLinter\Factory;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Http\Response;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Http\Request;
use Neos\Flow\Mvc\ActionRequest;

/**
 * @Flow\Scope("singleton")
 */
class MockControllerContextFactory
{
    public function buildControllerContext()
    {
        $context = new ControllerContext(
            $this->getCurrentActionRequest(),
            new Response,
            new Arguments,
            new UriBuilder
        );

        return $context;
    }

    protected function getCurrentRequest()
    {
        return Request::createFromEnvironment();
    }

    protected function getCurrentActionRequest()
    {
        return new ActionRequest($this->getCurrentRequest());
    }
}
