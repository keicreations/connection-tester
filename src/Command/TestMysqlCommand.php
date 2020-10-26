<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TestMysqlCommand extends Command
{
    protected static $defaultName = 'test:mysql';
    /**
     * @var string
     */
    private $databaseUrl;

    private $pool = [];

    public function __construct(string $databaseUrl, string $name = null)
    {
        parent::__construct($name);
        $this->databaseUrl = $databaseUrl;
    }

    protected function configure()
    {
        $this
            ->setDescription('Connect to database')
            ->addArgument('pool-size', InputArgument::REQUIRED, 'Amount of connections')
            ->addArgument('delay', InputArgument::OPTIONAL, 'Delay in seconds before creating next connection')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $poolSize = intval($input->getArgument('pool-size'));
        $delay = intval($input->getArgument('delay'));

        $progress = $io->createProgressBar($poolSize);
        try {
            for ($i = 0; $i < $poolSize; $i++) {

                $this->createConnection();
                $progress->advance();
                $progress->display();
                sleep($delay);
            }


            $progress->finish();

            $io->success(sprintf('Created %s connections', $poolSize));

            for ($i = 0; $i < $poolSize; $i++) {
                $this->closeConnection($i);
            }

            $io->success('Cleared all connections');
        }
        catch (Exception $exception) {
            $maxPoolSize = array_key_last($this->pool);
            $io->error($exception->getMessage());
            $io->note(sprintf('Max pool size looks to be %s', $maxPoolSize));

            for ($i = 0; $i < $maxPoolSize; $i++) {
                $this->closeConnection($i);
            }

            $io->success('Cleared all connections');
        }


        return Command::SUCCESS;
    }

    private function createConnection(): int
    {
        $connectionParams = array(
            'url' => $this->databaseUrl,
        );
        $conn = DriverManager::getConnection($connectionParams);
        $pid = array_push($this->pool, $conn);
        $conn->connect();

        return $pid;
    }

    private function closeConnection(int $pid)
    {
        $this->pool[$pid] = null;
        unset($this->pool[$pid]);
    }
}
