<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Composer;
use Illuminate\Foundation\PackageCompiler;
use ClassPreloader\Command\PreCompileCommand;
use Symfony\Component\Console\Input\InputOption;

class OptimizeCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'optimize';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Optimize the framework for better performance";

	/**
	 * The composer instance.
	 *
	 * @var \Illuminate\Foundation\Composer
	 */
	protected $composer;

	/**
	 * The package compiler instance.
	 * 
	 * @var \Illuminate\Foundation\PackageCompiler
	 */
	protected $packages;

	/**
	 * Create a new optimize command instance.
	 *
	 * @param  \Illuminate\Foundation\Composer  $composer
	 * @param  \Illuminate\Foundation\PackageCompiler  $packages
	 * @return void
	 */
	public function __construct(Composer $composer, PackageCompiler $packages)
	{
		parent::__construct();

		$this->composer = $composer;
		$this->packages = $packages;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->info('Generating optimized class loader');

		if ($this->option('psr'))
		{
			$this->composer->dumpAutoloads();
		}
		else
		{
			$this->composer->dumpOptimized();
		}

		if ($this->option('force') || ! $this->laravel['config']['app.debug'])
		{
			$this->info('Compiling common classes');

			$this->compileClasses();
		}
		else
		{
			$this->call('clear-compiled');
		}
	}

	/**
	 * Generate the compiled class file.
	 *
	 * @return void
	 */
	protected function compileClasses()
	{
		$this->registerClassPreloaderCommand();

		$outputPath = $this->laravel['path.base'].'/bootstrap/compiled.php';

		$this->callSilent('compile', array(
			'--config' => implode(',', $this->getClassFiles()),
			'--output' => $outputPath,
			'--strip_comments' => 1,
		));
	}

	/**
	 * Get the classes that should be combined and compiled.
	 *
	 * @return array
	 */
	protected function getClassFiles()
	{
		$app = $this->laravel;

		$core = require __DIR__.'/Optimize/config.php';

		$packages = $this->option('with-packages') ? $this->getPackageClassFiles() : array();

		return array_merge($core, $packages, $this->laravel['config']['compile']);
	}

	/**
	 * Get the package classes that should be combined and compiled.
	 * 
	 * @return array
	 */
	protected function getPackageClassFiles()
	{
		$paths = array();

		foreach ($this->laravel->getLoadedProviders() as $provider => $loaded)
		{
			$provider = $this->laravel->getRegistered($provider);

			$paths[] = $this->packages->compile($provider);
		}

		return array_flatten($paths);
	}

	/**
	 * Register the pre-compiler command instance with Artisan.
	 *
	 * @return void
	 */
	protected function registerClassPreloaderCommand()
	{
		$this->getApplication()->add(new PreCompileCommand);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('force', null, InputOption::VALUE_NONE, 'Force the compiled class file to be written.'),

			array('psr', null, InputOption::VALUE_NONE, 'Do not optimize Composer dump-autoload.'),

			array('with-packages', null, InputOption::VALUE_NONE, 'Compile package class files.'),
		);
	}

}
