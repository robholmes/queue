<?php
namespace Dtc\QueueBundle\Command;

use Dtc\QueueBundle\Model\Job;

use Asc\PlatformBundle\Documents\Profile\UserProfile;
use Asc\PlatformBundle\Documents\UserAuth;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class RunJobCommand
    extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setName('dtc:queue_worker:run-job')
        ->addArgument('job_id', InputArgument::REQUIRED, 'Id of job to run')
        ->setDescription('Start up a job by job id (useful for debug)')
        ;
    }

    /**
     * Note: If exit was called, then we  can't decrement the number of running processes correctly
     *
     * (non-PHPdoc)
     * @see Symfony\Component\Console\Command.Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $jobId = $input->getArgument('job_id');
        $jobManager = $container->get('dtc_queue.job_manager');
        $logger = $container->get('monolog.logger.dtc_queue');
        $processTimeout = 3600;
        $workerManager = $container->get('dtc_queue.worker_manager');

        set_time_limit($processTimeout);    // Set an hour timeout
        $job = $jobManager->getRepository()->find($jobId);

        if (!$job) {
            $logger->debug("Job not found: {$jobId}");
        }

        try {
            $logger->debug("Staring up job: {$job->getId()}");
            $job = $workerManager->runJob($job);

            if ($job) {
                $output->writeln("Finished job id: {$job->getId()}");

                if ($job->getStatus() == Job::STATUS_ERROR) {
	                $output->writeln("Error:");
	                $output->writeln("<error>{$job->getMessage()}</error>");
	                $output->writeln("\n");
                    $logger->error('[error]: '.$job->getMessage());
                }
            }
        } catch (\Exception $e) {
            if ($msg = $e->getMessage()) {
                $output->writeln('<error>[error]</error> '.$msg);
                $logger->error('[error]: '.$msg."\n".$e->getTraceAsString());
            }
        }

        $logger->debug("Finished job: {$job->getId()}");
    }
}
