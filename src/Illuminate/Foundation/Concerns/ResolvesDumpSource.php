<?php

namespace Illuminate\Foundation\Concerns;

use Throwable;

trait ResolvesDumpSource
{
    /**
     * The most common editor's href format.
     *
     * @var array<string, string>
     */
    protected $editorsHref = [
        'sublime' => 'subl://open?url=file://{file}&line={line}',
        'textmate' => 'txmt://open?url=file://{file}&line={line}',
        'emacs' => 'emacs://open?url=file://{file}&line={line}',
        'macvim' => 'mvim://open/?url=file://{file}&line={line}',
        'phpstorm' => 'phpstorm://open?file={file}&line={line}',
        'idea' => 'idea://open?file={file}&line={line}',
        'vscode' => 'vscode://file/{file}:{line}',
        'vscode-insiders' => 'vscode-insiders://file/{file}:{line}',
        'vscode-remote' => 'vscode://vscode-remote/{file}:{line}',
        'vscode-insiders-remote' => 'vscode-insiders://vscode-remote/{file}:{line}',
        'vscodium' => 'vscodium://file/{file}:{line}',
        'atom' => 'atom://core/open/file?filename={file}&line={line}',
        'nova' => 'nova://core/open/file?filename={file}&line={line}',
        'netbeans' => 'netbeans://open/?f={file}:{line}',
        'xdebug' => 'xdebug://{file}@{line}',
    ];

    /**
     * The source resolver.
     *
     * @var (callable(): (array{0: string, 1: string, 2: int|null}|null))|null
     */
    protected static $dumpSourceResolver;

    /**
     * Resolve the source of the dump call.
     *
     * @return array{0: string, 1: string, 2: int|null}|null
     */
    public function resolveDumpSource()
    {
        if (static::$dumpSourceResolver) {
            return call_user_func(static::$dumpSourceResolver);
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20);

        $sourceKey = null;

        foreach ($trace as $traceKey => $traceFile) {
            if (isset($traceFile['file']) && str_ends_with(
                $traceFile['file'],
                'symfony/var-dumper/Resources/functions/dump.php'
            )) {
                $sourceKey = $traceKey + 1;

                break;
            }
        }

        if (is_null($sourceKey)) {
            return;
        }

        $file = $trace[$sourceKey]['file'] ?? null;
        $line = $trace[$sourceKey]['line'] ?? null;

        if (is_null($file) || is_null($line)) {
            return;
        }

        $relativeFile = $file;

        if ($this->isCompiledViewFile($file)) {
            $file = $this->getOriginalFileForCompiledView($file);
            $line = null;
        }

        if (str_starts_with($file, $this->basePath)) {
            $relativeFile = substr($file, strlen($this->basePath) + 1);
        }

        return [$file, $relativeFile, $line];
    }

    /**
     * Resolves the source href, if possible.
     *
     * @param  string  $file
     * @param  int|null  $line
     * @return string|null
     */
    protected function resolveSourceHref($file, $line)
    {
        try {
            $editor = config('app.editor');
        } catch (Throwable $e) {
            // ..
        }

        if (! isset($editor)) {
            return;
        }

        $href = is_array($editor) && isset($editor['href'])
            ? $editor['href']
            : ($this->editorsHref[$editor['name'] ?? $editor] ?? sprintf('%s://open?file={file}&line={line}', $editor['name'] ?? $editor));

        if ($basePath = $editor['base_path'] ?? false) {
            $file = str_replace($this->basePath, $basePath, $file);
        }

        $href = str_replace(
            ['{file}', '{line}'],
            [$file, is_null($line) ? 1 : $line],
            $href,
        );

        return $href;
    }

    /**
     * Determine if the given file is a view compiled.
     *
     * @param  string  $file
     * @return bool
     */
    protected function isCompiledViewFile($file)
    {
        return str_starts_with($file, $this->compiledViewPath);
    }

    /**
     * Get the original view compiled file by the given compiled file.
     *
     * @param  string  $file
     * @return string
     */
    protected function getOriginalFileForCompiledView($file)
    {
        preg_match('/\/\*\*PATH\s(.*)\sENDPATH/', file_get_contents($file), $matches);

        if (isset($matches[1])) {
            $file = $matches[1];
        }

        return $file;
    }

    /**
     * Set the resolver that resolves the source of the dump call.
     *
     * @param  (callable(): (array{0: string, 1: string, 2: int|null}|null))|null  $callable
     * @return void
     */
    public static function resolveDumpSourceUsing($callable)
    {
        static::$dumpSourceResolver = $callable;
    }
}
