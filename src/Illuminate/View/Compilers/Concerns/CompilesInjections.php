<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesInjections
{
    /**
     * Compile the inject statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileInject($expression)
    {
        $segments = explode(',', preg_replace("/[\(\)\\\"\']/", '', $expression));

        $variable = trim($segments[0]);

        $service = trim($segments[1]);

        if (strpos($service, '@')) {
            [$service, $method] = explode('@', $service);

            return "<?php \${$variable} = (app('{$service}'))->{$method}(); ?>";
        }

        return "<?php \${$variable} = app('{$service}'); ?>";
    }
}
