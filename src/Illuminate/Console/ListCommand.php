<?php

namespace Illuminate\Console;

use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Console\Application;
use ReflectionClass;
use ReflectionFunction;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\ListCommand as SymfonyListCommand;
use Symfony\Component\Console\Command\Command;

class ListCommand extends SymfonyListCommand
{
    protected function configure()
    {
        parent::configure();
        
        $definition = $this->getDefinition();
        $definition->addOption(
            new InputOption('except-vendor', null, InputOption::VALUE_NONE, 'Do not include commands defined by vendor packages (except ClosureCommands)'),
        );

        $this->setDefinition($definition);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $exceptVendor = $input->getOption('except-vendor');

        if ($exceptVendor) {
            $this->getApplication()->setShouldExcludeVendor(true);
        }

        $returnCode = parent::execute($input, $output);

        if ($exceptVendor) {
            $this->getApplication()->setShouldExcludeVendor(true);
        }

        return $returnCode;
    }
}
