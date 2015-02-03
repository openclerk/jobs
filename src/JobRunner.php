<?php

namespace Openclerk\Jobs;

use \Monolog\Logger;
use \Db\Connection;
use \Openclerk\Config;

/**
 * A helper class to select job instances, run them, and
 * manage their pass/fail statuses.
 * Job queueing is managed by {@link JobQueuer}.
 *
 * TODO create more intelligent job selection methods
 */
abstract class JobRunner {

  function __construct() {
    // empty
  }

  /**
   * Find the next job that should be executed.
   * By default, just selects any job instance that is in the database that isn't
   * already executing, hasn't already been finished, and hasn't errored out.
   * @return a job array (id, job_type, [user_id], [arg_id]) or {@code false} if there is none
   */
  function findJob(Connection $db, Logger $logger) {
    // TODO timeout jobs

    // mark all repeatedly failing jobs as failing
    $execution_limit = Config::get("job_execution_limit", 5);
    $q = $db->prepare("SELECT * FROM jobs WHERE is_executed=0 AND is_executing=0 AND is_error=0 AND execution_count >= ?");
    $q->execute(array($execution_limit));
    if ($failed = $q->fetchAll()) {
      $logger->info("Found " . number_format(count($failed)) . " jobs that have executed too many times ($execution_limit)");
      foreach ($failed as $f) {
        $q = $db->prepare("UPDATE jobs SET is_error=1 WHERE id=?");
        $q->execute(array($f['id']));
        $logger->info("Marked job " . $f['id'] . " as failed");
      }
    }

    $q = $db->prepare("SELECT * FROM jobs WHERE is_executed=0 AND is_executing=0 AND is_error=0 LIMIT 1");
    $q->execute();
    return $q->fetch();
  }

  /**
   * From the given $job arguments, create a new {@link Job} instance
   * that can be run.
   */
  abstract function createJob($job, Connection $db, Logger $logger);

  /**
   * Select and run a {@link Job}.
   * If the job throws an exception, the job runner will capture this
   * exception and mark the job as failed as necessary.
   *
   * @throws Exception if the job failed
   */
  public function runOne(Connection $db, Logger $logger) {
    $job = $this->findJob($db, $logger);

    if ($job) {
      $logger->info("Running job " . print_r($job, true));
    } else {
      $logger->info("No job to run");
      return;
    }

    try {
      $instance = $this->createJob($job, $db, $logger);
      if (!($instance instanceof Job)) {

      }
      $logger->info("Running job class " . get_class($instance));

      // mark it as executing
      $q = $db->prepare("UPDATE jobs SET is_executing=1, execution_count=execution_count + 1 WHERE id=? LIMIT 1");
      $q->execute(array($job['id']));

      $instance->run($db, $logger);

      // it passed
      $q = $db->prepare("UPDATE jobs SET is_executing=0, is_executed=1, executed_at=NOW() WHERE id=? LIMIT 1");
      $q->execute(array($job['id']));

      $instance->passed($db, $logger);

      $logger->info("Complete");
    } catch (\Exception $e) {
      $logger->error($e->getMessage());

      // it failed
      $q = $db->prepare("UPDATE jobs SET is_executing=0, is_error=1, executed_at=NOW() WHERE id=? LIMIT 1");
      $q->execute(array($job['id']));

      $instance->failed($e, $db, $logger);

      // log exception
      $q = $db->prepare("INSERT INTO job_exceptions SET job_id=:job_id,
        class_name=:class_name,
        message=:message,
        filename=:filename,
        line_number=:line_number");
      $q->execute(array(
        "job_id" => $job['id'],
        "class_name" => get_class($e),
        "message" => $e->getMessage(),
        "filename" => $e->getFile(),
        "line_number" => $e->getLine(),
      ));

    }
  }

}
