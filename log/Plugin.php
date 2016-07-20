<?php

namespace Zicht\Tool\Plugin\Log;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Zicht\Tool\Container;
use Zicht\Tool\Plugin as BasePlugin;

/**
 * Class Plugin
 * @package Zicht\Tool\Plugin\Log
 */
class Plugin extends BasePlugin
{
    /**
     * @param ArrayNodeDefinition $rootNode
     */
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
                        ->arrayNode('endpoints')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('endpoint')
                                        ->isRequired()
                                    ->end()
                                    ->scalarNode('format')
                                        ->isRequired()
                                    ->end()
                                    ->scalarNode('command')
                                        ->defaultValue('curl -s -XPOST %s -d %s > /dev/null')
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('projectname')
                            ->isRequired()
                        ->end()
                    ->end()
                    ->addDefaultsIfNotSet()
                ->end()
            ->end()
        ;
    }


    /**
     * @param Container\ContainerBuilder $container
     */
    public function setContainerBuilder(Container\ContainerBuilder $container)
    {
        foreach ($container->config['log']['tasks'] as $task) {

            foreach (array('pre', 'post') as $step) {

                $data = array(
                    'user' => '$(user)',
                    'task' => $task,
                    'step' => $step,
                    'projectname' => '$(log.projectname)',
                    'timestamp' => '$(log.now)',
                    'vcs' => '$(vcs.url)'
                );

                $args = $container->config['tasks'][$task]['args'];

                foreach ($args as $key => $value) {
                    $data[$key] = sprintf('$(%s)', $key);
                }

                foreach($container->config['log']['endpoints'] as $endpoint){

                    if ($endpoint['format'] === 'slack') {
                        $data['timestamp'] = '$(log.now_readable)';
                        $data = $this->slackify($data);
                    }

                    $container->config['tasks'][$task][$step][] = sprintf(
                        $endpoint['command'],
                        $endpoint['endpoint'],
                        "'" . json_encode($data) . "'"
                    );
                }
            }
        }
    }

    /**
     * @param Container\Container $container
     */
    public function setContainer(Container\Container $container)
    {
        $container->decl('log.now', function() {
            return (new \DateTime())->getTimestamp();
        });

        $container->decl('log.now_readable', function() {
            return (new \DateTime());
        });
    }

    /**
     * Format the data in slack format
     *
     * @param $data
     * @return string
     */
    private function slackify($data)
    {
        $fields = array();

        foreach ($data as $key => $value) {
            $obj = new \stdClass();
            $obj->title = $key;
            $obj->value = $value;
            $obj->short = true;

            $fields[] = $obj;
        }

        return array('text' => sprintf('%s - %s (%s)', $data['projectname'], $data['task'], $data['step']), 'fields' => $fields);
    }
}
