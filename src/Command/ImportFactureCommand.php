<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\GlobalService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;


class ImportFactureCommand extends Command
{
    private $global_s;
    private $mailer;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'facture:import';

    public function __construct(GlobalService $global_s, MailerInterface $mailer)
    {
        parent::__construct();
        $this->global_s = $global_s;
        $this->mailer = $mailer;
    }

    protected function configure(): void
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        //$this->global_s->cronOcrImportDocument();
        $output->writeln('import reussit effectué');
        //$this->global_s->cronOcrIa();
        $output->writeln('IA effectué');

        return 0;

    }
}