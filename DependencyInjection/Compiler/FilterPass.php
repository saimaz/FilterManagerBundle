<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\FilterManagerBundle\DependencyInjection\Compiler;

use ONGR\FilterManagerBundle\DependencyInjection\ONGRFilterManagerExtension;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiles custom filters.
 */
class FilterPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $filters = [];
        foreach ($container->findTaggedServiceIds('ongr_filter_manager.filter') as $filterId => $filterTags) {
            foreach ($filterTags as $tag) {
                if (!array_key_exists('type', $tag)) {
                    throw new InvalidConfigurationException(
                        sprintf('Filter tagged with `%s` must have `type` set.', $filterId)
                    );
                }

                if (array_key_exists($tag['type'], $filters)) {
                    throw new InvalidConfigurationException(
                        "Filter type `{$tag['filter_name']}` is already defined please select different type name."
                    );
                }
                $filters[$tag['type']] = $filterId;
            }
        }

        $config = $container->getExtensionConfig('ongr_filter_manager')[0];

        foreach ($config['filters'] as $filterName => $filterConfig) {
        }

        foreach ($config['managers'] as $name => $manager) {

            $filterContainer = new Definition('ONGR\FilterManagerBundle\Search\FilterContainer');

            foreach ($manager['filters'] as $filterName) {

                $filter = new DefinitionDecorator($filters[$config['filters'][$filterName]['type']]);
                $filter->addMethodCall('setConfig', [$config['filters'][$filterName]]);
                $generatedFilterName = sprintf('ongr_filter_manager.filter.%s.%s',$name, $filterName);
                $container->setDefinition($generatedFilterName, $filter);

                $filterContainer->addMethodCall(
                    'set',
                    [$filterName, new Reference($generatedFilterName)]
                );
            }

            $managerDefinition = new Definition(
                'ONGR\FilterManagerBundle\Search\FilterManager',
                [
                    $filterContainer,
                    new Reference($manager['repository']),
                ]
            );

            $managerDefinition->addTag('es.filter_manager');

            $container->setDefinition(sprintf('ongr_filter_manager.%s', $name), $managerDefinition);
        }
    }

    /**
     * Adds relation to filter.
     *
     * @param Definition $definition
     * @param array      $filter
     * @param string     $urlType
     * @param string     $relationType
     */
    private function addRelation(Definition $definition, $filter, $urlType, $relationType)
    {
        if (!empty($filter['relations'][$urlType][$relationType])) {
            $definition->addMethodCall(
                'set' . ucfirst($urlType) . 'Relation',
                [$this->getRelation($relationType, $filter['relations'][$urlType][$relationType])]
            );
        }
    }

    /**
     * Creates relation definition by given parameters.
     *
     * @param string $type
     * @param array  $relations
     *
     * @return Definition
     */
    private function getRelation($type, $relations)
    {
        return new Definition(
            sprintf('ONGR\FilterManagerBundle\Relation\%sRelation', ucfirst($type)),
            [$relations]
        );
    }
}
