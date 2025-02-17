<?php

declare(strict_types=1);

namespace Dominikb\ComposerLicenseChecker;

use Dominikb\ComposerLicenseChecker\Traits\DependencyLoaderAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InitCommand extends Command
{
    use DependencyLoaderAwareTrait;

    protected function configure()
    {
        $this->setDefinition(new InputDefinition([
            new InputOption(
                'project-path',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Path to directory of composer.json file',
                realpath('.')
            ),
            new InputOption(
                'composer',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Path to composer executable',
                'composer'
            ),
            new InputOption(
                'no-dev',
                null,
                InputOption::VALUE_OPTIONAL,
                'Do not include dev dependencies',
                'false'
            ),
            new InputOption(
                'output',
                'o',
                InputOption::VALUE_OPTIONAL,
                'Path to file to write the used licenses to',
                'allowlist.txt'
            ),
            new InputOption(
                'force',
                '-f',
                InputOption::VALUE_NONE,
                'Ignore any existing allowlist file and potentially overwrite it with new content',
            ),
        ]));
    }

    public static function getDefaultName(): ?string
    {
        return 'init';
    }

    public static function getDefaultDescription(): ?string
    {
        return 'Generate a list of all licenses used in the project';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Generating list of all licenses used in the project...');

        if (! $this->canWriteToOutfile($input, $io)) {
            return Command::FAILURE;
        }

        $dependencies = $this->dependencyLoader->loadDependencies(
            $input->getOption('composer'),
            $input->getOption('project-path'),
            ($input->getOption('no-dev') ?? 'true') === 'true'
        );

        $output = join(PHP_EOL, $this->extractLicenses($dependencies)).PHP_EOL;
        $io->block($output);

        if (! file_put_contents($input->getOption('output'), $output)) {
            $io->error("Failed to write to '".$input->getOption('output')."'.");

            return Command::FAILURE;
        }

        $io->success("List of used licenses written to '".$input->getOption('output')."'.");

        return Command::SUCCESS;
    }

    /**
     * @param  Dependency[]  $dependencies
     * @return string[]
     */
    private function extractLicenses(array $dependencies): array
    {
        $licenses = [];
        foreach ($dependencies as $dependency) {
            $licenses[] = $dependency->getLicenses();
        }

        $uniqueLicenses = array_values(array_unique(array_flatten($licenses)));
        sort($uniqueLicenses);

        return $uniqueLicenses;
    }

    /**
     * @param  InputInterface  $input
     * @param  SymfonyStyle  $io
     * @return bool
     */
    protected function canWriteToOutfile(InputInterface $input, SymfonyStyle $io): bool
    {
        if ($input->getOption('force')) {
            return true;
        }

        if (! file_exists($input->getOption('output'))) {
            return true;
        }

        $io->warning('File '.$input->getOption('output').' already exists.');

        return $io->confirm('Overwrite existing allowlist?', false);
    }
}
