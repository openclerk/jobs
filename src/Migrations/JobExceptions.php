<?php

namespace Openclerk\Jobs\Migrations;

/**
 * Represents exceptions that occur when running jobs.
 */
class JobException extends \Db\Migration {

  /**
   * Apply only the current migration.
   * @return true on success or false on failure
   */
  function apply(\Db\Connection $db) {
    $q = $db->prepare("CREATE TABLE job_exceptions (
      id int not null auto_increment primary key,
      created_at timestamp not null default current_timestamp,

      job_id int not null,
      class_name varchar(255) null,
      message varchar(255) null,
      filename varchar(255) null,
      line_number int null,

      INDEX(job_id),
      INDEX(class_name)
    );");
    return $q->execute();
  }

}
