<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\FilterManagerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages bundle configuration.
 */
class ONGRFilterManagerExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
    }

    /**
     * Adds filters based on configuration.
     *
     * @param array            $config    Configuration.
     * @param ContainerBuilder $container Service container.
     */
    private function addFilters(array $config, ContainerBuilder $container)
    {
        $this->validateFilterNames($config);

        foreach ($config as $type => $filters) {
            foreach ($filters as $name => $config) {
                $filterDefinition = $this
                    ->getFilterFactory($type)
                    ->setConfiguration($config)
                    ->getDefinition();

                $this->addRelation($filterDefinition, $config, 'search', 'include');
                $this->addRelation($filterDefinition, $config, 'search', 'exclude');
                $this->addRelation($filterDefinition, $config, 'reset', 'include');
                $this->addRelation($filterDefinition, $config, 'reset', 'exclude');

                $container->setDefinition(self::getFilterId($name), $filterDefinition);
            }
        }
    }

}
