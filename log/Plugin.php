<?php

namespace Zicht\Tool\Plugin\Log;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Zicht\Tool\Container;
use Zicht\Tool\Plugin as BasePlugin;

class Plugin extends BasePlugin
{
    public function appendConfiguration(ArrayNodeDefinition $rootNode)
    {
        parent::appendConfiguration($rootNode);

        $rootNode
            ->children()
                ->arrayNode('log')
                    ->children()
                        ->arrayNode('tasks')
                            ->prototype('scalar')->end()
                            ->defaultValue(array())
                        ->end()
                        ->scalarNode('endpoint')->end()
                        ->scalarNode('command')->defaultValue('curl -XPOST %s -d %s')->end()
                    ->end()
                    ->addDefaultsIfNotSet()
                ->end()
            ->end()
        ;
    }


    public function setContainerBuilder(Container\ContainerBuilder $container)
    {
        $container->config['tasks'];

        foreach ($container->config['log']['tasks'] as $item) {
            foreach (array('pre', 'post') as $when) {
                $data = array(
                    'user' => '$(user)',
                    'task' => $item,
                    'when' => $when
                );
                $container->config['tasks'][$item][$when][]= sprintf(
                    $container->config['log']['command'],
                    $container->config['log']['endpoint'],
                    "'" . json_encode($data) . "'"
                );
            }
        }
    }
}