<?php
namespace Dtc\QueueBundle\Command;

use Dtc\QueueBundle\Model\Job;

use Asc\PlatformBundle\Documents\Profile\UserProfile;
use Asc\PlatformBundle\Documents\UserAuth;

use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class WorkCommand
    extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('dtc:queue_worker:work')
            ->addArgument('worker_name', InputArgument::OPTIONAL, 'Name of worker')
            ->addArgument('method', InputArgument::OPTIONAL, 'DI method of worker')
            ->addOption('total_jobs', 't', InputOption::VALUE_REQUIRED,
                    'Total number of job to work on before exiting', 1)
            ->addOption('timeout', 'to', InputOption::VALUE_REQUIRED,
                    'Process timeout in seconds', 3600)
            ->setDescription('Start up a job in queue')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $workerManager = $container->get('dtc_queue.worker_manager');
        $workerName = $input->getArgument('worker_name');
        $methodName = $input->getArgument('method');
        $totalJobs = $input->getOption('total_jobs', 1);
        $logger = $container->get('monolog.logger.dtc_queue');
        /* @var $logger Logger */
        $processTimeout = $input->getOption('timeout', 3600);

        // Check to see if there are other instances
        set_time_limit($processTimeout);    // Set an hour timeout
        gc_enable();

        $output->writeln(date('Y-m-d H:i:s - ')."<info>Starting up a new worker...</info>");
        $currentJob = 1;

        try {
            do {
                $job = $workerManager->run($workerName, $methodName);

                if ($job) {
                    $this->reportJob($job, $output);
                    gc_collect_cycles();
                    $currentJob++;
                }
                else {
                    $output->writeln(date('Y-m-d H:i:s - ')."<info>No job to run...</info>");
                    return;
                }
            } while ($currentJob <= $totalJobs);
        } catch (\Exception $e) {
            // Uncaught error: possibly with QueueBundle itself
            $output->writeln(date('Y-m-d H:i:s - ').'<error>[critical]</error> '.$e->getMessage());
            $logger->critical('[critical]: '.$e->getMessage()."\n".$e->getTraceAsString());
        }

        $output->writeln(date('Y-m-d H:i:s - ')."<info>Ending worker with job count of ".$currentJob."...</info>");
    }

    protected function reportJob(Job $job, OutputInterface $output) {
        if ($job->getStatus() == Job::STATUS_ERROR) {
            $output->writeln(date('Y-m-d H:i:s - ')."<error>[error]</error>  Error with job id: {$job->getId()}");
            $output->writeln(date('Y-m-d H:i:s - ').$job->getMessage());

            $logger = $this->getContainer()->get('monolog.logger.dtc_queue');
            /* @var $logger Logger */
            $logger->error('[error]: "'.$job->getWorkerName().'" with: '.$job->getMessage());
        }

        $message = "Finished job id: {$job->getId()} in {$job->getElapsed()} seconds\n";
        $output->writeln(date('Y-m-d H:i:s - ')."<info>{$message}</info>");
    }
}
