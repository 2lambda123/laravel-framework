<?php

namespace Illuminate\View\Compilers\Concerns;

use Illuminate\Support\Str;

trait CompilesStacks
{
    /**
     * Compile the stack statements into the content.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileStack($expression)
    {
        return "<?php echo \$__env->yieldPushContent{$expression}; ?>";
    }

    /**
     * Compile the push statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compilePush($expression)
    {
        return "<?php \$__env->startPush{$expression}; ?>";
    }

    /**
     * Compile the push-once statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compilePushOnce($expression)
    {
        $parts = explode(',', $this->stripParentheses($expression));
        
        $stack = $parts[0];
        $id = $parts[1] ?? null;

        $id = trim($id) ?: "'".(string) Str::uuid()."'";

        return '<?php $__env->startPush('.$stack.');
if (! $__env->hasRenderedOnce('.$id.')):
$__env->markAsRenderedOnce('.$id.'); ?>';
    }

    /**
     * Compile the end-push statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndpush()
    {
        return '<?php $__env->stopPush(); ?>';
    }

    /**
     * Compile the end-push-once statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndpushOnce()
    {
        return '<?php endif; $__env->stopPush(); ?>';
    }

    /**
     * Compile the prepend statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compilePrepend($expression)
    {
        return "<?php \$__env->startPrepend{$expression}; ?>";
    }

    /**
     * Compile the prepend-once statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compilePrependOnce($expression)
    {
        $parts = explode(',', $this->stripParentheses($expression));
        
        $stack = $parts[0];
        $id = $parts[1] ?? null;

        $id = trim($id) ?: "'".(string) Str::uuid()."'";

        return '<?php $__env->startPrepend('.$stack.');
if (! $__env->hasRenderedOnce('.$id.')):
$__env->markAsRenderedOnce('.$id.'); ?>';
    }

    /**
     * Compile the end-prepend statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndprepend()
    {
        return '<?php $__env->stopPrepend(); ?>';
    }
    
    /**
     * Compile the end-prepend-once statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndprependOnce()
    {
        return '<?php endif; $__env->stopPrepend(); ?>';
    }
}
