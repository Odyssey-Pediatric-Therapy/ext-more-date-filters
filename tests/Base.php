<?php

namespace Espo\Modules\MoreDateFilters\Classes\Test;

use Espo\Core\Exceptions\Error;
use Espo\Core\Select\Where\Item;
use Espo\Core\Select\Where\ItemConverterFactory;
use Espo\Entities\User;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;

abstract class Base
{
  protected string $name;
  private bool $verbose = false;

  abstract protected function getTestList(): array;

  public function run(bool $verbose = false): array {
    $this->verbose = $verbose;

    if($this->verbose)
      $this->log->debug("  {$this->name}");

    $tests = $this->getTestList();

    $errors = [];
    $passed = 0;

    foreach ($this->getTestList() as $test) {
      try {
        $this->{$test}();
      } catch (Error $e) {
        $errors[] = "Test '$test' failed: " . $e->getMessage();
      }
      if(empty($errors))
        $passed += 1;

      if($this->verbose)
        $this->log->debug("    " . (empty($errors) ? "Passed" : "Failed") . ": $test");
    }

    if($this->verbose)
      $this->log->debug("    Total: $passed / " . count($tests),[]);

    return $errors;
  }

  ###
  # Boolean
  protected function assertTrue(bool $condition, string $message = ""): void {
    if(!$condition)
      throw new Error("Expected condition to be true. Received: false. Message: $message");
  }

  protected function assertFalse(bool $condition, string $message = ""): void {
    if($condition)
      throw new Error("Expected condition to be false. Received: true. Message: $message");
  }

  ###
  # Equality
  protected function assertEquals(mixed $expected, mixed $actual, string $message = ""): void {
    if($expected !== $actual)
      throw new Error("Expected value '" . json_encode($expected) . "' to equal '" . json_encode($actual) . "'. Message: $message");
  }

  protected function assertNotEquals($expected, $actual, string $message = ""): void {
    if($expected === $actual)
      throw new Error("Expected value '" . json_encode($expected) . "' not to equal '" . json_encode($actual) . "'. Message: $message");
  }

  ###
  # Iterable
  protected function assertArrayHasKey($key, $array, string $message = ""): void {
    if(!array_key_exists($key, $array))
      throw new Error("Expected key '$key' in array. Received: " . json_encode($array) . ". Message: $message");
  }

  protected function assertContains(mixed $needle, iterable $haystack, string $message = ""): void {
    if(!in_array($needle, $haystack, true))
      throw new Error("Expected value '$needle' to be in iterable. Received: " . json_encode($haystack) . ". Message: $message");
  }

  protected function assertInstanceOf($expected, $actual, string $message = ""): void {
    if(!($actual instanceof $expected))
      throw new Error("Expected instance of " . $expected . ". Received: " . gettype($actual) . ". Message: $message");
  }

  protected function assertIsArray( $value, string $message = ""): void {
    if(!is_array($value))
      throw new Error("Expected array. Received: " . gettype($value) . ". Message: $message");
  }

  ###
  # Error
  protected function assertCausedError(callable $function, string $expectedMessage = "", string $message = ""): void {
    try {
      $function();
    } catch (Error $e) {
      if ($expectedMessage !== "" && !str_contains($e->getMessage(), $expectedMessage)) {
        throw new Error("Expected error message to contain '$expectedMessage'. Received: '" . $e->getMessage() . "'. Message: $message");
      }
      return;
    }

    throw new Error("Expected an error to be thrown, but none was. Message: $message");
  }

  ###
  # Test Helpers
  protected function runConfigurations(array $configurations): void {
    foreach($configurations as $configuration)
      $this->runConfiguration($configuration['type'], $configuration['expected'], $configuration["value"] ?? null);
  }

  protected function runConfiguration(string $type, array $expected, ?int $value = null, string $entityType = "Session"): void {
    $params = array(
      "type" => $type,
      "value" => $value,
      "dateTime" => null,
      "date" => null,
      "timeZone" => null,
      "attribute" => "dateEnd",
      "field" => "dateEnd",
    );
    $item = Item::fromRaw($params);

    $converter = $this->itemConverterFactory->createForType($item->getType(), $entityType, $this->user);
    $where = $converter->convert($this->queryBuilder, $item);

    $this->assertEquals(json_encode($expected), json_encode($where->getRaw()));
  }
}
