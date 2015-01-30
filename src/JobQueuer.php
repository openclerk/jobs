<?php

namespace Openclerk\Jobs;

use \Monolog\Logger;
use \Db\Connection;

/**
 * A helper class to find job instances that need to be run,
 * and queue them up in the database.
 * Job running is managed by {@link JobRunner}.
 *
 * TODO create more intelligent job queueing strategies
 */
abstract class JobQueuer {

  /**
   * Get a list of all jobs that need to be queued, as an array of associative
   * arrays with (job_type, arg_id, [user_id]).
   *
   * This could use e.g. {@link JobTypeFinder}
   */
  abstract function findJobs(Connection $db, Logger $logger);

  /**
   * The given job has been queued up, so we can mark it as successfully queued.
   */
  abstract function jobQueued(Connection $db, Logger $logger, $job);

  /**
   * Find all jobs that need to be queued through {@link #findJobs()}
   * and queue them up in the database.
   *
   * @throws Exception if the job failed
   */
  public function queue(Connection $db, Logger $logger) {
    $jobs = $this->findJobs($db, $logger);
    $logger->info("Found " . number_format(count($jobs)) . " job instances to queue");

    foreach ($jobs as $job) {
      if (!isset($job['job_type'])) {
        throw new JobQueuerException("Could not insert job " . print_r($job, true) . ": no job_type set");
      }

      // is there a job for this instance already?
      $query = "SELECT * FROM jobs WHERE is_executed=0 AND job_type=:job_type";
      $args = array(
        "job_type" => $job['job_type'],
      );
      if (isset($job['user_id'])) {
        $query .= " AND user_id=:user_id";
        $args["user_id"] = $job['user_id'];
      }
      if (isset($job['arg_id'])) {
        $query .= " AND arg_id=:arg_id";
        $args["arg_id"] = $job['arg_id'];
      }
      $q = $db->prepare($query);
      $q->execute($args);
      if ($existing = $q->fetch()) {
        $this->jobQueued($db, $logger, $existing);
        continue;
      }

      // insert in the new instance
      $query = "INSERT INTO jobs SET job_type=:job_type";
      $args = array(
        "job_type" => $job['job_type'],
      );
      if (isset($job['user_id'])) {
        $query .= ", user_id=:user_id";
        $args["user_id"] = $job['user_id'];
      }
      if (isset($job['arg_id'])) {
        $query .= ", arg_id=:arg_id";
        $args["arg_id"] = $job['arg_id'];
      }

      $q = $db->prepare($query);
      if (!$q->execute($args)) {
        throw new JobQueuerException("Could not insert job " . print_r($job, true));
      } else {
        $job['id'] = $db->lastInsertId();
        $this->jobQueued($db, $logger, $job);
      }
    }

    $logger->info("Inserted in " . number_format(count($jobs)) . " job instances");
  }

}
