<?php

namespace Symfony\Cmf\Bundle\ChainRoutingBundle\Tests\Routing;

use Symfony\Cmf\Bundle\ChainRoutingBundle\Test\CmfUnitTestCase;
use Symfony\Cmf\Bundle\ChainRoutingBundle\Routing\ContentRouter;

class ContentRouterTest extends CmfUnitTestCase
{
    public function setUp()
    {
        $this->node = $this->buildMock('Navigation', array('getReference'));
        $this->document = $this->buildMock('Document');
        $this->loader_interface = $this->buildMock("\Symfony\Component\Config\Loader\LoaderInterface");
        $this->object_manager = $this->buildMock("\Doctrine\Common\Persistence\ObjectManager", array('find'));
        $this->controller_resolver = $this->buildMock('\Symfony\Cmf\Bundle\ChainRoutingBundle\Controller\ControllerResolver', array('getController'));

        $this->router = new ContentRouter($this->loader_interface, array());
        $this->router->setObjectManager($this->object_manager);
        $this->router->setControllerResolver($this->controller_resolver);
    }

    public function testMatch()
    {
        $url_alias = "/company/more";

        $this->node->expects($this->once())
                ->method('getReference')
                ->will($this->returnValue($this->document));

        $this->object_manager->expects($this->once())
                ->method('find')
                ->with(null, $url_alias)
                ->will($this->returnValue($this->node));

        $this->controller_resolver->expects($this->once())
                ->method('getController')
                ->with($this->document);

        $this->router->match($url_alias);
    }

    public function testNoReferenceMatch()
    {
        $url_alias = "/company/more_no_reference";

        $this->node->expects($this->once())
                ->method('getReference')
                ->will($this->returnValue(null));

        $this->object_manager->expects($this->once())
                ->method('find')
                ->with(null, $url_alias)
                ->will($this->returnValue($this->node));

        $this->assertFalse($this->router->match($url_alias));
    }

    public function testNoNodeMatch()
    {
        $url_alias = "/company/more_no_match";

        $this->object_manager->expects($this->once())
                ->method('find')
                ->with(null, $url_alias)
                ->will($this->returnValue(null));

        $this->assertFalse($this->router->match($url_alias));
    }
}