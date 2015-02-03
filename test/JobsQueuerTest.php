<?php

namespace Openclerk\Jobs\Tests;

use \Openclerk\Jobs\JobQueuer;

class JobsQueuerTest extends \PHPUnit_Framework_TestCase {

  function testJobPrefix() {
    $this->assertEquals("ticker", JobQueuer::getJobPrefix("ticker_foo"));
    $this->assertEquals("ticker", JobQueuer::getJobPrefix("ticker_foo_bar"));
    $this->assertEquals("ticker", JobQueuer::getJobPrefix("ticker_"));
    $this->assertEquals("ticker", JobQueuer::getJobPrefix("ticker"));
    $this->assertEquals("ticker", JobQueuer::getJobPrefix("ticker-foo"));
    $this->assertEquals("ticker", JobQueuer::getJobPrefix("ticker-foo_bar"));
    $this->assertEquals("ticker", JobQueuer::getJobPrefix("ticker_foo-bar"));
    $this->assertEquals("ticker", JobQueuer::getJobPrefix("ticker-"));
    $this->assertEquals("ticker", JobQueuer::getJobPrefix("ticker"));
    $this->assertEquals("", JobQueuer::getJobPrefix(""));
  }

}
