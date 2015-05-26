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
                        ->scalarNode('endpoint')
                            ->isRequired()
                        ->end()
                        ->scalarNode('projectname')
                            ->isRequired()
                        ->end()
                        ->scalarNode('command')
                            ->defaultValue('curl -s -XPOST %s -d %s > /dev/null')
                        ->end()
                    ->end()
                    ->addDefaultsIfNotSet()
                ->end()
            ->end()
        ;
    }


    public function setContainerBuilder(Container\ContainerBuilder $container)
    {
        $container->config['tasks'];

        foreach ($container->config['log']['tasks'] as $task) {

            foreach (array('pre', 'post') as $step) {

                $data = array(
                    'user' => '$(user)',
                    'task' => $task,
                    'step' => $step,
                    'projectname' => $container->config['log']['projectname'],
                    'timestamp' => (new \DateTime())->getTimestamp(),
                    'vcs' => $container->config['vcs']['url']
                );

                $args = $container->config['tasks'][$task]['args'];

                foreach ($args as $key => $value) {
                    $data[$key] = sprintf('$(%s)', $key);
                }
                
                $container->config['tasks'][$task][$step][]= sprintf(
                    $container->config['log']['command'],
                    $container->config['log']['endpoint'],
                    "'" . json_encode($data) . "'"
                );
            }
        }
    }
}