<?php

namespace Openclerk\Jobs;

use \Monolog\Logger;
use \Db\Connection;

/**
 * Represents a single Job that needs to be run.
 */
interface Job {

  /**
   * Run this job.
   * If this job throws an exception, the job will be considered failed.
   * If this job does not, the job will be considered passed.
   * @throws Exception if the job failed
   */
  public function run(Connection $db, Logger $logger);

  /**
   * Callback function for when the job passed. Can do nothing.
   */
  public function passed(Connection $db, Logger $logger);

  /**
   * Callback function for when the job failed. Can do nothing.
   */
  public function failed(Connection $db, Logger $logger);

}
