<?php

namespace Illuminate\Tests\Console\Factory;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Config\Repository as Config;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Database\Console\Factories\FactoryMakeCommand;

class FactoryMakeCommandTest extends TestCase
{
    protected $filesystem;

    public function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem;
    }

    protected function tearDown(): void
    {
        m::close();

        $this->filesystem->deleteDirectory(__DIR__.'/database');
    }

    /** @test */
    public function it_is_giving_a_default_model_name_from_the_factory_name()
    {
        $command = $this->setupEnvironment();

        $name = 'UserFactory';

        $this->runCommand($command, ['name' => $name]);

        $slug = $this->filesystem->get(__DIR__."/database/factories/{$name}.php");

        $this->assertTrue((bool) strpos($slug, 'User::class'));
        $this->assertTrue((bool) strpos($slug, 'App\\Models\\User'));
    }

    /** @test */
    public function it_is_giving_the_specified_model_name()
    {
        $command = $this->setupEnvironment();

        $name = 'UserFactory';

        $this->runCommand($command, ['name' => $name, '--model' => 'ModelName']);

        $slug = $this->filesystem->get(__DIR__."/database/factories/{$name}.php");

        $this->assertFalse((bool) strpos($slug, 'User::class'));
        $this->assertFalse((bool) strpos($slug, 'UserFactory::class'));

        $this->assertTrue((bool) strpos($slug, 'ModelName::class'));
        $this->assertTrue((bool) strpos($slug, 'App\\Models\\ModelName'));
    }

    /** @test */
    public function it_is_giving_same_name_when_name_is_Factory_uppercase()
    {
        $command = $this->setupEnvironment();

        $name = 'Factory';

        $this->runCommand($command, ['name' => $name]);

        $slug = $this->filesystem->get(__DIR__."/database/factories/{$name}.php");

        $this->assertTrue((bool) strpos($slug, 'Factory::class'));
        $this->assertTrue((bool) strpos($slug, 'App\\Models\\Factory'));
    }

    /** @test */
    public function it_is_giving_same_name_when_factory_name_is_factory_lowercase()
    {
        $command = $this->setupEnvironment();

        $name = 'factory';

        $this->runCommand($command, ['name' => $name]);

        $slug = $this->filesystem->get(__DIR__."/database/factories/{$name}.php");

        $this->assertTrue((bool) strpos($slug, 'factory::class'));
        $this->assertTrue((bool) strpos($slug, 'App\\Models\\factory'));
    }

    protected function setupEnvironment()
    {
        $command = new FactoryMakeCommand($this->filesystem);

        $app = new Application;

        app()->singleton('config', function () {
            return new Config([
                'auth' => [
                    'defaults' => ['guard' => 'default'],
                    'guards' => [
                        'default' => ['driver' => 'default'],
                        'secondary' => ['driver' => 'secondary'],
                    ],
                ],
            ]);
        });

        $app->basePath(__DIR__);

        $app->useDatabasePath(__DIR__.'/database');

        $command->setLaravel($app);

        return $command;
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}

class Application extends LaravelApplication
{
    public function getNamespace()
    {
        return 'App\\Models\\';
    }
}
