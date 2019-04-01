<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Harmony\Bundle\RoutingBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\DoctrineMongoDBMappingsPass;
use Doctrine\Common\Persistence\Mapping\Driver\DefaultFileLocator;
use Doctrine\ODM\MongoDB\Mapping\Driver as OdmXmlDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver as OrmXmlDriver;
use Doctrine\ORM\Version as ORMVersion;
use Harmony\Bundle\RoutingBundle\DependencyInjection\Compiler\SetRouterPass;
use Harmony\Bundle\RoutingBundle\DependencyInjection\Compiler\TemplatingValidatorPass;
use Harmony\Bundle\RoutingBundle\DependencyInjection\Compiler\ValidationPass;
use Symfony\Cmf\Component\Routing\DependencyInjection\Compiler\RegisterRouteEnhancersPass;
use Symfony\Cmf\Component\Routing\DependencyInjection\Compiler\RegisterRoutersPass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use function class_exists;
use function realpath;
use function sprintf;

/**
 * Class CmfRoutingBundle
 *
 * @package Harmony\Bundle\RoutingBundle
 */
class HarmonyRoutingBundle extends Bundle
{

    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new RegisterRoutersPass());
        $container->addCompilerPass(new RegisterRouteEnhancersPass());
        $container->addCompilerPass(new SetRouterPass());
        $container->addCompilerPass(new ValidationPass());
        $container->addCompilerPass(new TemplatingValidatorPass());

        $this->buildOdmCompilerPass($container);
        $this->buildOrmCompilerPass($container);
    }

    /**
     * Creates and registers compiler passes for ODM mapping.
     *
     * @param ContainerBuilder $container
     */
    private function buildOdmCompilerPass(ContainerBuilder $container)
    {
        if (!class_exists(DoctrineMongoDBMappingsPass::class)) {
            return;
        }

        $container->addCompilerPass(
            $this->buildBaseCompilerPass(DoctrineMongoDBMappingsPass::class, OdmXmlDriver::class, 'mongodb')
        );
        $container->addCompilerPass(DoctrineMongoDBMappingsPass::createXmlMappingDriver(
            [realpath(__DIR__ . '/Resources/config/doctrine-model') => 'Harmony\Bundle\RoutingBundle\Model'],
            ['cmf_routing.dynamic.persistence.mongodb.manager_name'],
            'cmf_routing.backend_type_mongodb',
            ['CmfRoutingBundle' => 'Harmony\Bundle\RoutingBundle\Doctrine\MongoDB']
        )
        );
    }

    /**
     * Creates and registers compiler passes for ORM mappings if both doctrine
     * ORM and a suitable compiler pass implementation are available.
     *
     * @param ContainerBuilder $container
     */
    private function buildOrmCompilerPass(ContainerBuilder $container)
    {
        if (!class_exists(ORMVersion::class)) {
            return;
        }

        $container->addCompilerPass(
            $this->buildBaseCompilerPass(DoctrineOrmMappingsPass::class, OrmXmlDriver::class, 'orm')
        );
        $container->addCompilerPass(
            DoctrineOrmMappingsPass::createXmlMappingDriver(
                [realpath(__DIR__ . '/Resources/config/doctrine-model') => 'Harmony\Bundle\RoutingBundle\Model'],
                ['cmf_routing.dynamic.persistence.orm.manager_name'],
                'cmf_routing.backend_type_orm_default',
                ['CmfRoutingBundle' => 'Harmony\Bundle\RoutingBundle\Doctrine\Orm']
            )
        );

        $container->addCompilerPass(
            DoctrineOrmMappingsPass::createXmlMappingDriver(
                [realpath(__DIR__ . '/Resources/config/doctrine-model') => 'Harmony\Bundle\RoutingBundle\Model'],
                ['cmf_routing.dynamic.persistence.orm.manager_name'],
                'cmf_routing.backend_type_orm_custom',
                []
            )
        );
    }

    /**
     * Builds the compiler pass for the symfony core routing component. The
     * compiler pass factory method uses the SymfonyFileLocator which does
     * magic with the namespace and thus does not work here.
     *
     * @param string $compilerClass the compiler class to instantiate
     * @param string $driverClass   the xml driver class for this backend
     * @param string $type          the backend type name
     *
     * @return CompilerPassInterface
     */
    private function buildBaseCompilerPass($compilerClass, $driverClass, $type)
    {
        $arguments = [[realpath(__DIR__ . '/Resources/config/doctrine-base')], sprintf('.%s.xml', $type)];
        $locator   = new Definition(DefaultFileLocator::class, $arguments);
        $driver    = new Definition($driverClass, [$locator]);

        return new $compilerClass(
            $driver,
            ['Symfony\Component\Routing'],
            [sprintf('cmf_routing.dynamic.persistence.%s.manager_name', $type)],
            sprintf('cmf_routing.backend_type_%s', $type)
        );
    }
}
