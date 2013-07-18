<?php
namespace Dtc\QueueBundle\Model;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class Job
{
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';
    const STATUS_NEW = 'new';

    protected $id;
    protected $workerName;
    protected $className;
    protected $method;
    protected $args;
    protected $batch;
    protected $status;
    protected $message;
    protected $priority;
    protected $crcHash;
    protected $locked;
    protected $lockedAt;
    protected $when;
    protected $expire;
    protected $createdAt;
    protected $updatedAt;
    protected $delay;
    protected $elapsed;
    protected $interval;
    protected $repeating;

    protected $jobManager;

    /**
     * @return the $message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param field_type $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return the $status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return the $locked
     */
    public function getLocked()
    {
        return $this->locked;
    }

    /**
     * @return the $lockedAt
     */
    public function getLockedAt()
    {
        return $this->lockedAt;
    }

    /**
     * @return the $expire
     */
    public function getExpire()
    {
        return $this->expire;
    }

    /**
     * @param field_type $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @param field_type $locked
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;
    }

    /**
     * @param field_type $lockedAt
     */
    public function setLockedAt($lockedAt)
    {
        $this->lockedAt = $lockedAt;
    }

    /**
     * @param field_type $expire
     */
    public function setExpire($expire)
    {
        $this->expire = $expire;
    }

    /**
     * @return the $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return the $workerName
     */
    public function getWorkerName()
    {
        return $this->workerName;
    }

    /**
     * @return the $className
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return the $method
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return the $args
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @return the $batch
     */
    public function getBatch()
    {
        return $this->batch;
    }

    /**
     * @return the $priority
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @return the $crcHash
     */
    public function getCrcHash()
    {
        return $this->crcHash;
    }

    /**
     * @return the $when
     */
    public function getWhen()
    {
        return $this->when;
    }

    /**
     * @return the $createdAt
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return the $updatedAt
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @return the $jobManager
     */
    public function getJobManager()
    {
        return $this->jobManager;
    }

    /**
     * @param field_type $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param field_type $workerName
     */
    public function setWorkerName($workerName)
    {
        $this->workerName = $workerName;
    }

    /**
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->className = $className;
    }

    /**
     * @param field_type $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @param field_type $args
     */
    public function setArgs($args)
    {
        if (!$this->recursiveValidArgs($args)) {
            throw new \Exception("Args must not contain object");
        }

        $this->args = $args;
    }

    protected function recursiveValidArgs($args) {
        if (is_array($args)) {
            foreach ($args as $key => $value) {
                if (!$this->recursiveValidArgs($value)) {
                    return false;
                }
            }

            return true;
        }
        else {
            return !is_object($args);
        }
    }

    /**
     * @param field_type $batch
     */
    public function setBatch($batch)
    {
        $this->batch = $batch;
    }

    /**
     * @param field_type $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    /**
     * @param field_type $crcHash
     */
    public function setCrcHash($crcHash)
    {
        $this->crcHash = $crcHash;
    }

    /**
     * @param field_type $when
     */
    public function setWhen($when)
    {
        $this->when = $when;
    }

    /**
     * @param field_type $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @param field_type $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @param field_type $jobManager
     */
    public function setJobManager($jobManager)
    {
        $this->jobManager = $jobManager;
    }

    protected $worker;
    public function __construct(Worker $worker = null, $batch = false, $priority = 10, \DateTime $when = null)
    {
        $this->worker = $worker;
        if ($worker) {
            $this->jobManager = $worker->getJobManager();
            $this->className = get_class($worker);
            $this->workerName = $worker->getName();
        }

        $this->when = $when;
        $this->batch = $batch ? true : false;
        $this->priority = $priority;
        $this->status = self::STATUS_NEW;
    }

    public function __call($method, $args)
    {
        $this->method = $method;
        $this->setArgs($args);
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();

        // Make sure the method exists - job should not be created
        if (!is_callable(array($this->worker, $method), true)) {
            throw new \Exception("{$this->className}->{$method}() is not callable");
        }

        $this->jobManager->save($this);
        return $this;
    }
    /**
     * @return the $delay
     */
    public function getDelay()
    {
        return $this->delay;
    }

    /**
     * @return the $worker
     */
    public function getWorker()
    {
        return $this->worker;
    }

    /**
     * @param field_type $delay
     */
    public function setDelay($delay)
    {
        $this->delay = $delay;
    }

    /**
     * @param Worker $worker
     */
    public function setWorker($worker)
    {
        $this->worker = $worker;
    }
	/**
	 * @return the $elapsed
	 */
	public function getElapsed()
	{
		return $this->elapsed;
	}

	/**
	 * @param field_type $elapsed
	 */
	public function setElapsed($elapsed)
	{
		$this->elapsed = $elapsed;
	}

	/**
	 * @return string
	 */
	public function getInterval()
	{
		return $this->interval;
	}

	/**
	 * @param string $interval
	 */
	public function setInterval($interval)
	{
		$this->interval = $interval;
	}

	/**
	 * @return boolean
	 */
	public function getRepeating()
	{
		return $this->repeating;
	}

	/**
	 * @param boolean $repeating
	 */
	public function setRepeating($repeating)
	{
		$this->repeating = $repeating;
	}
}
