openclerk/jobs
==============

A library for simple PHP job queueing, execution and management,
used by [Openclerk](http://openclerk.org) and live on [CryptFolio](https://cryptfolio.com).

While cron jobs are a simple approach to running regular tasks,
`openclerk/jobs` allows tasks to be defined, executed and managed in reliable ways.

## Installing

Include `openclerk/jobs` as a requirement in your project `composer.json`,
and run `composer update` to install it into your project:

```json
{
  "require": {
    "openclerk/jobs": "dev-master"
  }
}
```

## Features

1. Queue up jobs immediately for execution later
1. Jobs can return success or failure (by throwing exceptions)
1. Repeatedly failing jobs can be removed from the job execution queue
1. Define your own job selection algorithms
1. Any exceptions thrown during job execution are stored in the `job_exceptions` table

## Using

### Define a job class

```php
use \Openclerk\Jobs\Job;
use \Db\Connection;
use \Monolog\Logger;

class MyJob implements Job {

  function __construct($job) {
    $this->job = $job;
  }

  function run(Connection $db, Logger $logger) {
    // do something
    if (false) {
      throw new \Exception("Job failed");
    }
  }

  function passed(Connection $db, Logger $logger) {
    // the job passed
  }

  function failed(\Exception $runtime_exception, Connection $db, Logger $logger) {
    // the job failed
  }
}
```

### Define a job queuer

```php
use \Openclerk\Jobs\JobQueuer;
use \Openclerk\Jobs\Job;
use \Db\Connection;
use \Monolog\Logger;

class MyJobQueuer extends JobQueuer {

  /**
   * Get a list of all jobs that need to be queued, as an array of associative
   * arrays with (job_type, arg_id, [user_id]).
   */
  function findJobs(Connection $db, Logger $logger) {
    $result = array();

    $q = $db->prepare("SELECT * FROM table WHERE is_queued=0");
    $q->execute();
    while ($r = $q->fetch()) {
      $result[] = array(
        'job_type' => 'table',
        'arg_id' => $r['id'],
        // optional: user_id
      );
    }

    return $result;
  }

  /**
   * The given job has been queued up, so we can mark it as successfully queued.
   */
  function jobQueued(Connection $db, Logger $logger, $job) {
    $q = $db->prepare("UPDATE table SET is_queued=1 WHERE id=?");
    $q->execute(array($job['arg_id']));
  }
}
```

### Define a job runner

```php
use \Openclerk\Jobs\JobRunner;
use \Openclerk\Jobs\Job;
use \Db\Connection;
use \Monolog\Logger;

class MyJobRunner extends JobRunner {

  /**
   * Get the {@link Job} to run for this job type.
   */
  function createJob($job, Connection $db, Logger $logger) {
    switch ($job['job_type']) {
      case 'table':
        return new MyJob($job);
    }
  }

}
```

### Write batch scripts to execute the queuer and runner

For example, a batch script to queue up new jobs:

```php
$logger = new \Monolog\Logger("batch_queue");
$logger->pushHandler(new \Core\MyLogger());

$runner = new MyJobQueuer();
$runner->queue(db(), $logger);
```

Or, a batch script to run a single job:

```php
$logger = new \Monolog\Logger("batch_run");
$logger->pushHandler(new \Core\MyLogger());

$runner = new MyJobRunner();
$runner->runOne(db(), $logger);
```

These batch scripts can then be setup with cron, etc.

## Extensions

1. [Using `require()` for running jobs instead of classes](https://github.com/soundasleep/openclerk/blob/master/core/GenericOpenclerkJob.php)
1. [Run jobs only of a certain type](https://github.com/soundasleep/openclerk/blob/master/batch/batch_run_type.php)
1. [Run jobs only of users with particular properties](https://github.com/soundasleep/openclerk/blob/master/batch/batch_run_premium.php)
1. [Run jobs only with a particular job ID](https://github.com/soundasleep/openclerk/blob/master/core/OpenclerkJobRunner.php)
1. [Run jobs from a web admin interface](https://github.com/soundasleep/openclerk/blob/master/pages/admin_run_job.php)

## Donate

[Donations are appreciated](https://code.google.com/p/openclerk/wiki/Donating).

## TODO

1. Capture jobs that timeout
1. job_failed events through openclerk/events
1. More tests
