<?php namespace Illuminate\Foundation\Console; use Illuminate\Console\Command; use Illuminate\Filesystem\Filesystem; use Symfony\Component\Console\Input\InputOption; use Symfony\Component\Console\Input\InputArgument; class CommandMakeCommand extends Command { protected $name = 'command:make'; protected $description = "Create a new Artisan command"; public function __construct(Filesystem $files) { parent::__construct(); $this->files = $files; } public function fire() { $path = $this->getPath(); $stub = $this->files->get(__DIR__.'/stubs/command.stub'); $file = $path.'/'.$this->input->getArgument('name').'.php'; $this->writeCommand($file, $stub); } protected function writeCommand($file, $stub) { if ( ! file_exists($file)) { $this->files->put($file, $this->formatStub($stub)); $this->info('Command created successfully.'); } else { $this->error('Command already exists!'); } } protected function formatStub($stub) { $stub = str_replace('{{class}}', $this->input->getArgument('name'), $stub); if ( ! is_null($this->option('command'))) { $stub = str_replace('command:name', $this->option('command'), $stub); } return $this->addNamespace($stub); } protected function addNamespace($stub) { if ( ! is_null($namespace = $this->input->getOption('namespace'))) { return str_replace('{{namespace}}', ' namespace '.$namespace.';', $stub); } else { return str_replace('{{namespace}}', '', $stub); } } protected function getPath() { $path = $this->input->getOption('path'); if (is_null($path)) { return $this->laravel['path'].'/commands'; } else { return $this->laravel['path.base'].'/'.$path; } } protected function getArguments() { return array( array('name', InputArgument::REQUIRED, 'The name of the command.'), ); } protected function getOptions() { return array( array('command', null, InputOption::VALUE_OPTIONAL, 'The terminal command that should be assigned.', null), array('path', null, InputOption::VALUE_OPTIONAL, 'The path where the command should be stored.', null), array('namespace', null, InputOption::VALUE_OPTIONAL, 'The command namespace.', null), ); } }
