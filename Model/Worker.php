<?php
namespace Dtc\QueueBundle\Model;

abstract class Worker
{
    protected $jobManager;
    protected $jobClass;
    protected $job;

    /**
     * @return the $jobClass
     */
    public function getJobClass()
    {
        return $this->jobClass;
    }

    /**
     * @param field_type $jobClass
     */
    public function setJobClass($jobClass)
    {
        $this->jobClass = $jobClass;
    }

    public function setJobManager(JobManagerInterface $jobManager)
    {
        $this->jobManager = $jobManager;
    }

    /**
     * @return the $jobManager
     */
    public function getJobManager()
    {
        return $this->jobManager;
    }

    public function at($time = null, $batch = false, $priority = null)
    {
        if ($time === null) {
            $time = time();
        }

        if ($time) {
            $dateTime = new \DateTime();
            $dateTime->setTimestamp($time);
        }
        else {
            $dateTime = null;
        }

        return new $this->jobClass($this, $batch, $priority, $dateTime);
    }

    public function later($delay = 0, $priority = null)
    {
        $job = $this->at(time() + $delay, false, $priority);
        $job->setDelay($delay);
        return $job;
    }

    public function batchLater($delay = 0, $priority = null)
    {
        $job = $this->at($delay, true, $priority);
        $job->setDelay($delay);
        return $job;
    }

    public function batchAt($time = null, $priority = null)
    {
        return $this->at($time, true, $priority);
    }

    /**
     * @param int $delay seconds delay before first job
     * @param string $interval The interval to repeat after ready for a \DateInterval constructor
     * @param mixed $priority
     * @return Job
     */
    public function repeatLater($delay = 0, $interval = "PT1M", $priority = null)
    {
        $job = $this->at(time() + $delay, false, $priority);
        /* @var $job Job */
        $job->setDelay($delay);
        $job->setRepeating(true);
        $job->setInterval($interval);
        return $job;
    }

    /**
     * @param int $time The time to start first job
     * @param string $interval The interval to repeat after ready for a \DateInterval constructor
     * @param mixed $priority
     * @return Job
     */
    public function repeatAt($time = null, $interval = "PT1M", $priority = null)
    {
        $job = $this->at($time, false, $priority);
        /* @var $job Job */
        $job->setRepeating(true);
        $job->setInterval($interval);
        return $job;
    }

    abstract function getName();
}
