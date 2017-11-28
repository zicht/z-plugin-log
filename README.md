# Logging for Z

With this plugin you can very easily trigger curl REST request to be done for any type of task.

The plugin is built in such a way that you only need to configure the names of the task you
want to log, and how. 

It assumes the remote is able to handle POST requests as json data, but you can configure any
custom log command.

## Example:

```yml
log:
    projectname: "My project"
    tasks: ['my_task']
    endpoints:
        - endpoint: http://example.org
          format: ''
```

This would do a POST request with curl to the specified endpoint before and after the task. Tasks can
be considered succesful if both the `pre` and the `post` have been trigger.

```
echo 'curl -s -XPOST http://example.org -d '\'(data omitted for legibility')\'' > /dev/null' | /bin/bash -e
echo 'echo "This is the task"' | /bin/bash -e
echo 'curl -s -XPOST http://example.org -d '\''(data omitted for legibility)'\'' > /dev/null' | /bin/bash -e
```

# Maintainers
* Philip Bergman <philip@zicht.nl>
* Michael Roterman <michael@zicht.nl>
