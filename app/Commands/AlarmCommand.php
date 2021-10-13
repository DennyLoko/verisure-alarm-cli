<?php

namespace App\Commands;

use App\Exceptions\UnknownAlarmStateException;
use App\Repositories\Verisure\InstallationRepository;
use App\Repositories\Verisure\JobStatusRepository;
use App\Repositories\Verisure\LoginRepository;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class AlarmCommand extends Command
{
    protected const STATE_DISABLE = 'disable';
    protected const STATE_ENABLE = 'enable';

    protected static $defaultName = 'alarm';

    protected InstallationRepository $installation;
    protected JobStatusRepository $jobStatus;
    protected LoginRepository $login;

    public function __construct(InstallationRepository $installation, JobStatusRepository $jobStatus, LoginRepository $login)
    {
        $this->installation = $installation;
        $this->jobStatus = $jobStatus;
        $this->login = $login;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Manipulate installation alarm')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('state', InputArgument::REQUIRED),
                    new InputArgument('id', InputArgument::REQUIRED),
                ])
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Starting alarm procedure.');

        $cookieJar = new CookieJar();
        $csrf = $this->login($output, $cookieJar);
        $jobId = $this->toggleAlarmState($output, $input, $cookieJar);

        $this->progress($output, $cookieJar, $csrf, $jobId);

        return Command::SUCCESS;
    }

    private function login(OutputInterface $output, CookieJar &$cookieJar): string
    {
        $output->writeln('-> Executing login procedure.');

        $csrf = $this->login->login($cookieJar, $_ENV['VERISURE_USERNAME'], $_ENV['VERISURE_PASSWORD']);

        $output->writeln('-> Logged in with success.');

        return $csrf;
    }

    private function toggleAlarmState(OutputInterface $output, InputInterface $input, CookieJar &$cookieJar): string
    {
        $state = $input->getArgument('state');

        if ($state === self::STATE_DISABLE) {
            return $this->unlock($output, $input, $cookieJar);
        }

        if ($state === self::STATE_ENABLE) {
            return $this->lock($output, $input, $cookieJar);
        }

        throw new UnknownAlarmStateException($state);
    }

    private function unlock(OutputInterface $output, InputInterface $input, CookieJar &$cookieJar): string
    {
        $output->writeln('-> Executing unlock procedure.');

        $jobId = $this->installation->unlock($cookieJar, $input->getArgument('id'));

        $output->writeln('-> Alarm has been unlocked.');

        return $jobId;
    }

    private function lock(OutputInterface $output, InputInterface $input, CookieJar &$cookieJar): string
    {
        $output->writeln('-> Executing lock procedure.');

        $jobId = $this->installation->lock($cookieJar, $input->getArgument('id'));

        $output->writeln('-> Alarm has been locked.');

        return $jobId;
    }

    private function progress(OutputInterface $output, CookieJar $cookieJar, string $csrf, string $jobId): void
    {
        $output->writeln('Waiting till job get ready.');

        $progressBar = new ProgressBar($output, 10);
        $progressBar->start();

        while (! $this->jobStatus->isJobReady($cookieJar, $jobId, $csrf)) {
            sleep(1);
            $progressBar->advance();
        }

        $progressBar->finish();

        $output->writeln('Alarm has been deactivated!');
    }
}
