<?php

namespace Openclerk\Jobs\Migrations;

/**
 * Represents a single Job that needs to be run.
 */
class Jobs extends \Db\Migration {

  /**
   * Apply only the current migration.
   * @return true on success or false on failure
   */
  function apply(\Db\Connection $db) {
    $q = $db->prepare("CREATE TABLE jobs (
      id int not null auto_increment primary key,
      created_at timestamp not null default current_timestamp,

      job_type varchar(32) not null,
      user_id int null,
      arg_id int null,

      is_executed tinyint not null default 0,
      is_executing tinyint not null default 0,
      is_error tinyint not null default 0,
      is_recent tinyint not null default 0,
      is_timeout tinyint not null default 0,

      execution_started timestamp null,
      executed_at timestamp null,
      execution_count tinyint not null default 0,

      INDEX(job_type),
      INDEX(user_id),
      INDEX(arg_id),

      INDEX(is_executed, is_executing, is_error),
      INDEX(is_executing),
      INDEX(is_error),
      INDEX(is_recent),
      INDEX(is_timeout)
    );");
    return $q->execute();
  }

  /**
   * Override the default function to check that a table exists.
   * @return true if this migration is applied
   */
  function isApplied(\Db\Connection $db) {
    return $this->tableExists($db, "jobs");
  }

}
