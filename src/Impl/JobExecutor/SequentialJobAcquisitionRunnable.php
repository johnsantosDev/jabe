<?php

namespace Jabe\Impl\JobExecutor;

//use Jabe\Impl\Util\ClassLoaderUtil;
use Jabe\Impl\{
    ProcessEngineImpl,
    ProcessEngineLogger
};
use Concurrent\{
    ExecutorServiceInterface,
    ThreadInterface
};

class SequentialJobAcquisitionRunnable extends AcquireJobsRunnable
{
    //protected final JobExecutorLogger LOG = ProcessEngineLogger.JOB_EXECUTOR_LOGGER;

    protected $acquisitionContext;

    public function __construct(JobExecutor $jobExecutor, ...$args)
    {
        parent::__construct($jobExecutor, ...$args);
        $this->acquisitionContext = $this->initializeAcquisitionContext();
    }

    public function run(ThreadInterface $process = null, ...$args): void
    {
        //LOG.startingToAcquireJobs(jobExecutor.getName());
        $acquisitionStrategy = $this->initializeAcquisitionStrategy();

        while (!$this->isInterrupted) {
            $this->acquisitionContext->reset();
            $this->acquisitionContext->setAcquisitionTime(time() * 1000);

            $processEngines = $this->jobExecutor->engineIterator();

            try {
                foreach ($processEngines as $currentProcessEngine) {
                    if (!$this->jobExecutor->hasRegisteredEngine($currentProcessEngine)) {
                        // if engine has been unregistered meanwhile
                        continue;
                    }
                    $acquiredJobs = $this->acquireJobs($this->acquisitionContext, $acquisitionStrategy, $currentProcessEngine);
                    $this->executeJobs($this->acquisitionContext, $currentProcessEngine, $acquiredJobs, ...$args);
                }
            } catch (\Exception $e) {
                //LOG.exceptionDuringJobAcquisition(e);
                $this->acquisitionContext->setAcquisitionException($e);
            } finally {
            }
            $this->acquisitionContext->setJobAdded(isset($acquiredJobs) ? $acquiredJobs->size() > 0 : false);
            $this->configureNextAcquisitionCycle($this->acquisitionContext, $acquisitionStrategy);
            //The clear had to be done after the configuration, since a hint can be
            //appear in the suspend and the flag shouldn't be cleaned in this case.
            //The loop will restart after suspend with the isJobAdded flag and
            //reconfigure with this flag
            $this->clearJobAddedNotification();

            $waitTime = $acquisitionStrategy->getWaitTime();
            // wait the requested wait time minus the time that acquisition itself took
            // this makes the intervals of job acquisition more constant and therefore predictable
            $waitTime = max([0, ($this->acquisitionContext->getAcquisitionTime() + $waitTime) - time() * 1000]);
            $this->suspendAcquisition($waitTime);
        }
        //LOG.stoppedJobAcquisition(jobExecutor.getName());
    }

    protected function initializeAcquisitionContext(): JobAcquisitionContext
    {
        return new JobAcquisitionContext($this->state[1]->get());
    }

    /**
     * Reconfigure the acquisition strategy based on the current cycle's acquisition context.
     * A strategy implementation may update internal data structure to calculate a different wait time
     * before the next cycle of acquisition is performed.
     */
    protected function configureNextAcquisitionCycle(JobAcquisitionContext $acquisitionContext, JobAcquisitionStrategyInterface $acquisitionStrategy): void
    {
        $acquisitionStrategy->reconfigure($acquisitionContext);
    }

    protected function initializeAcquisitionStrategy(): JobAcquisitionStrategyInterface
    {
        return new BackoffJobAcquisitionStrategy($this->jobExecutor);
    }

    public function getAcquisitionContext(): JobAcquisitionContext
    {
        return $this->acquisitionContext;
    }

    protected function executeJobs(JobAcquisitionContext $context, ProcessEngineImpl $currentProcessEngine, AcquiredJobs $acquiredJobs, ...$args): void
    {
        // submit those jobs that were acquired in previous cycles but could not be scheduled for execution
        $additionalJobs = null;
        $jobs = $context->getAdditionalJobsByEngine();
        if (array_key_exists($currentProcessEngine->getName(), $jobs)) {
            $additionalJobs = $jobs[$currentProcessEngine->getName()];
        }

        if (!empty($additionalJobs)) {
            foreach ($additionalJobs as $jobBatch) {
                //LOG.executeJobs(currentProcessEngine.getName(), jobBatch);
                $this->jobExecutor->executeJobs($jobBatch, $currentProcessEngine, ...$args);
            }
        }

        // submit those jobs that were acquired in the current cycle
        foreach ($acquiredJobs->getJobIdBatches() as $jobIds) {
            //LOG.executeJobs(currentProcessEngine.getName(), jobIds);
            $this->jobExecutor->executeJobs($jobIds, $currentProcessEngine, ...$args);
        }
    }

    protected function acquireJobs(
        JobAcquisitionContext $context,
        JobAcquisitionStrategyInterface $acquisitionStrategy,
        ProcessEngineImpl $currentProcessEngine
    ): AcquiredJobs {
        $commandExecutor = $currentProcessEngine->getProcessEngineConfiguration()
            ->getCommandExecutorTxRequired();

        $numJobsToAcquire = $acquisitionStrategy->getNumJobsToAcquire($currentProcessEngine->getName());

        $acquiredJobs = null;

        if ($numJobsToAcquire > 0) {
            $this->jobExecutor->logAcquisitionAttempt($currentProcessEngine);
            $acquiredJobs = $commandExecutor->execute($this->jobExecutor->getAcquireJobsCmd($numJobsToAcquire));
        } else {
            $acquiredJobs = new AcquiredJobs($numJobsToAcquire);
        }

        $context->submitAcquiredJobs($currentProcessEngine->getName(), $acquiredJobs);

        $this->jobExecutor->logAcquiredJobs($currentProcessEngine, $acquiredJobs->size());
        $this->jobExecutor->logAcquisitionFailureJobs($currentProcessEngine, $acquiredJobs->getNumberOfJobsFailedToLock());

        //LOG.acquiredJobs(currentProcessEngine.getName(), acquiredJobs);

        return $acquiredJobs;
    }
}
