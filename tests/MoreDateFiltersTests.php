<?php

namespace Espo\Modules\MoreDateFilters\Jobs;

use Espo\Core\Job\JobDataLess;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Log;
use Espo\Entities\User;
use Espo\ORM\EntityManager;

use MoreDateFiltersTest;
use Espo\Modules\Odyssey\Entities\CMessage;

class MoreDateFiltersTests implements JobDataLess
{
  private const VERBOSE = true;

  public function __construct(
    private EntityManager $entityManager,
    private InjectableFactory $injectableFactory,
    private Log $log,
  ) {
    $this->path = "custom/Espo/Modules/MoreDateFilters/Tests";
  }

  public function run(): void {
    $tests = $this->findTests($this->path);
    $this->log->debug("Running MoreDateFilterTests", [count($tests)]);

    $errors = array();

    foreach($tests as $testPath) {
      $class = "MoreDateFiltersTest";
      $class .= str_replace("/", "\\", substr($testPath, strlen($this->path), -4));

      $test = $this->injectableFactory->create($class);
      $result = $test->run(verbose: self::VERBOSE);
      if(!empty($result))
        $errors[] = $result;
    }

    foreach($errors as $error)
      $this->log->debug("Test Error",[$error]);

    $this->log->debug("MoreDateFilter tests completed: " . (empty($errors) ? "No errors" : "Errors found"));
  }

  private function findTests($path): array {
    $files = array();

    foreach(scandir($path) as $value) {
      if($value === '.' || $value === '..')
        continue;

      if(is_file("$path/$value")) {
        $files[] = "$path/$value";
        continue;
      }

      foreach($this->findTests("$path/$value") as $file)
        $files[] = $file;
    }

    return $files;
  }
}