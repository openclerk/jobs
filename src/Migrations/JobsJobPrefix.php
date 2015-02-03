<?php

namespace Openclerk\Jobs\Migrations;

/**
 * Adds job_prefix to the jobs table.
 */
class JobsJobPrefix extends \Db\Migration {

  function getParents() {
    return array(new Jobs());
  }

  /**
   * Apply only the current migration.
   * @return true on success or false on failure
   */
  function apply(\Db\Connection $db) {
    $q = $db->prepare("ALTER TABLE jobs
      ADD job_prefix varchar(32) not null,
      ADD INDEX(job_prefix)
    ;");
    return $q->execute();
  }

}
