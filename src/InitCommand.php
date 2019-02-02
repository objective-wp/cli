<?php

namespace ObjectiveWP\Console;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use ZipArchive;

class InitCommand extends Command
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Initializes an objective-wp application')
            ->addArgument('type', InputArgument::REQUIRED, 'Options: plugin, enfold')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the project (the directory)')
            ->addOption('dev', null, InputOption::VALUE_NONE, 'Installs the latest "development" release')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Forces install even if the directory already exists');
    }

    /**
     * Execute the command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface $input
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('The Zip PHP extension is not installed. Please install it and try again.');
        }
        $type = $input->getArgument('type');
        $directory =  $this->makeFilename($input->getArgument('name'), $type);

        if (!$input->getOption('force')) {
            $this->verifyApplicationDoesntExist($directory);
        }

        $output->writeln('<info>Initializing application...</info>');

        $version = $this->getVersion($input);


        $this->download($output, $type, $directory, $version)
            ->cleanUp($directory, $output);

        $composer = $this->findComposer();

        $commands = [
            $composer . ' install',
        ];

        if ($input->getOption('no-ansi')) {
            $commands = array_map(function ($value) {
                return $value . ' --no-ansi';
            }, $commands);
        }

        if ($input->getOption('quiet')) {
            $commands = array_map(function ($value) {
                return $value . ' --quiet';
            }, $commands);
        }
        $this->executeCommand(implode(' && ', $commands), $directory, $output);

        $output->writeln('<comment>Application ready! Build something amazing.</comment>');
    }

    protected function executeCommand($commands, $directory, $output) {
        $process = new Process($commands, $directory, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            $process->setTty(true);
        }

        $process->run(function ($type, $line) use ($output) {
            $output->write($line);
        });
    }

    /**
     * Verify that the application does not already exist.
     *
     * @param  string $directory
     * @return void
     */
    protected function verifyApplicationDoesntExist($directory)
    {
        if ((is_dir($directory) || is_file($directory)) && $directory != getcwd()) {
            throw new RuntimeException('Application already exists!');
        }
    }

    /**
     * Generate a random temporary filename.
     *
     * @param $name
     * @param $type
     * @return string
     */
    protected function makeFilename($name, $type)
    {
        $filesystem = new Filesystem();

        if(!$name) {
            if($type === 'enfold') {
                if($filesystem->exists('wp-content'))
                    return realpath('wp-content/themes/enfold-child');
                else
                    return 'enfold-child';

            }
            else if($name == 'plugin')
                return 'owp-plugin';
        }
        return $name;
    }

    /**
     * Clone repository into directory.
     *
     * @param  OutputInterface $output
     * @param  string $type
     * @param  string $name
     * @param  string $version
     * @return $this
     */
    protected function download($output, $type, $name, $version = 'master')
    {
        $url = 'https://github.com/' . $this->getRepo($type);
        $this->executeCommand(['git', 'clone', '--single-branch', '--branch', $version, $url, $name], getcwd(), $output);
        return $this;
    }

    /**
     * @param string $type
     * @return string
     */
    protected function getRepo($type) {
        $org = '';
        if(file_exists(Config::getConfigPath()))
            $org = file_get_contents(Config::getConfigPath()) . '/';
        if($type === 'enfold')
            return $org . 'enfold-child-starter';
        else if($type === 'plugin')
            return $org . 'objective-wp-plugin-starter';
        else
            return $type;
    }

    protected function getDeleteCommand($name) {
        $path =  realpath($name);
        if("\\" === DIRECTORY_SEPARATOR)
            return 'rd /s /q "' . $path . '"';
        else
            return 'rm -rf' . $path;
    }

    /**
     * Clean-up the Zip file.
     *
     * @param  string $name
     * @param $output
     * @return $this
     */
    protected function cleanUp($name, $output)
    {
//        chmod($name . '/.git', 0777);
//        $path = realpath($name);
        $gitPath = realpath($name . DIRECTORY_SEPARATOR . '.git');
//        $filesystem = new Filesystem();
//        $success = chmod($path, 0777);
//        $filesystem->chmod($path, 0777);
//        $filesystem->remove($gitPath);
        if(!$gitPath)
            return $this;
        $command = $this->getDeleteCommand($gitPath);
        $this->executeCommand($command, getcwd(), $output);
//        $this->executeCommand($command, getcwd(), $output);
        return $this;
    }

    /**
     * Make sure the storage and bootstrap cache directories are writable.
     *
     * @param  string $appDirectory
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @return $this
     */
    protected function prepareWritableDirectories($appDirectory, OutputInterface $output)
    {
        $filesystem = new Filesystem;

        try {
            $filesystem->chmod($appDirectory . DIRECTORY_SEPARATOR . "bootstrap/cache", 0755, 0000, true);
            $filesystem->chmod($appDirectory . DIRECTORY_SEPARATOR . "storage", 0755, 0000, true);
        } catch (IOExceptionInterface $e) {
            $output->writeln('<comment>You should verify that the "storage" and "bootstrap/cache" directories are writable.</comment>');
        }

        return $this;
    }

    /**
     * Get the version that should be downloaded.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface $input
     * @return string
     */
    protected function getVersion(InputInterface $input)
    {
        if ($input->getOption('dev')) {
            return 'develop';
        }

        return 'master';
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposer()
    {
        if (file_exists(getcwd() . '/composer.phar')) {
            return '"' . PHP_BINARY . '" composer.phar';
        }

        return 'composer';
    }
}
