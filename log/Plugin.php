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


    public function setContainerBuilder(Container\ContainerBuilder $container)
    {
        foreach ($container->config['log']['tasks'] as $task) {

            foreach (array('pre', 'post') as $step) {

                $data = array(
                    'user' => '$(user)',
                    'task' => $task,
                    'step' => $step,
                    'projectname' => '$(log.projectname)',
                    'timestamp' => (new \DateTime())->getTimestamp(),
                    'vcs' => '$(vcs.url)'
                );

                $args = $container->config['tasks'][$task]['args'];

                foreach ($args as $key => $value) {
                    $data[$key] = sprintf('$(%s)', $key);
                }

                foreach($container->config['log']['endpoints'] as $endpoint){

                    if ($endpoint['format'] === 'slack') {
                        $data['timestamp'] = new \DateTime(); //overwrite the timestamp property, just because we want to ^^
                        $data = $this->slackify($data);
                    }

                    $container->config['tasks'][$task][$step][]= sprintf(
                        $endpoint['command'],
                        $endpoint['endpoint'],
                        "'" . json_encode($data) . "'"
                    );
                }
            }
        }
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
