<?php

namespace ObjectiveWP\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetOrganizationCommand extends Command
{

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('set-org')
            ->setDescription('Sets the Github organization to pull templates from')
            ->addArgument('organization', InputArgument::REQUIRED, 'The name of the organization to set');
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
        $organization = $input->getArgument('organization');
        file_put_contents(Config::getConfigPath(), $organization);
        $output->writeln("Saved {$organization} to the configuration!");
    }
}