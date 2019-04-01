<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Harmony\Bundle\RoutingBundle\Tests\Fixtures\App;

use Harmony\Bundle\ResourceRestBundle\CmfResourceRestBundle;
use Harmony\Component\Testing\HttpKernel\TestKernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class Kernel extends TestKernel
{
    public function configure()
    {
        $this->requireBundleSet('default');

        if ('phpcr' === $this->environment) {
            $this->requireBundleSets([
                'phpcr_odm',
            ]);
        } elseif ('orm' === $this->environment) {
            $this->requireBundleSet('doctrine_orm');
        }

        $this->registerConfiguredBundles();

        if (class_exists(CmfResourceRestBundle::class)) {
            $this->addBundles([
                new \Harmony\Bundle\ResourceBundle\CmfResourceBundle(),
                new \Harmony\Bundle\ResourceRestBundle\CmfResourceRestBundle(),
            ]);
        }
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->environment.'.php');
    }
}
