<?php

namespace tests\unit\Espo\Modules\MoreDateFilters;

use DateTime;
use DateTimeZone;
use Espo\Core\Container;
use Espo\Core\InjectableFactory;
use Espo\Core\Select\Where\ConverterFactory;
use Espo\Core\Select\Where\Item;
use Espo\Core\Select\Where\Scanner;
use Espo\Core\Select\Helpers\RandomStringGenerator;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Entities\User;
use Espo\Modules\MoreDateFilters\Classes\Metadata\DateFilters;
use Espo\Modules\MoreDateFilters\Services\Core;
use Espo\ORM\Defs;
use Espo\ORM\Query\SelectBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/** Exercise Espo's real factories and converter dispatch without connecting a database. */
class PipelineTest extends TestCase
{
    public function testFactoryPipelinePreservesNumericDatetimeAndOptionalDateFilters(): void
    {
        $base = dirname(__DIR__, 5) . '/src/files/custom/Espo/Modules/MoreDateFilters/Resources/metadata/';
        $data = json_decode(json_encode([
            'app' => ['select' => json_decode(file_get_contents($base . 'app/select.json'), true)],
            'entityDefs' => ['Contact' => ['fields' => ['createdAt' => ['type' => 'datetime']]],
                'Task' => ['fields' => ['dateStart' => ['type' => 'datetimeOptional']]]],
            'selectDefs' => ['Task' => ['whereDateTimeItemTransformerClassName' =>
                \Espo\Modules\Crm\Classes\Select\Meeting\Where\DateTimeItemTransformer::class]],
        ]));
        (new DateFilters())->build($data);
        $metadata = $this->createMock(Metadata::class);
        $metadata->method('get')->willReturnCallback(static function ($path, $default = null) use ($data) {
            $node = json_decode(json_encode($data), true);
            foreach ($path as $key) {
                if (!is_array($node) || !array_key_exists($key, $node)) return $default;
                $node = $node[$key];
            }
            return $node;
        });
        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnCallback(fn ($key, $default = null) => $key === 'timeZone' ? 'UTC' : $default);
        $clock = $this->getMockBuilder(Core::class)->onlyMethods(['now'])->getMock();
        $clock->method('now')->willReturnCallback(fn ($zone) => (new DateTime('2025-09-09 12:22:22', new DateTimeZone('UTC')))->setTimezone($zone));
        $applicationConfig = $this->createMock(Config\ApplicationConfig::class);
        $applicationConfig->method('getTimeZone')->willReturn('UTC');
        $scanner = $this->createMock(Scanner::class);
        $scanner->expects($this->exactly(3))->method('apply');
        $services = [
            'metadata' => $metadata, 'config' => $config, 'core' => $clock,
            'applicationConfig' => $applicationConfig, 'scanner' => $scanner,
            'ormDefs' => $this->createMock(Defs::class),
            'randomStringGenerator' => $this->createMock(RandomStringGenerator::class),
        ];
        $container = $this->createMock(Container::class);
        $factory = new InjectableFactory($container);
        $services['injectableFactory'] = $factory;
        $container->method('has')->willReturnCallback(fn ($name) => isset($services[$name]));
        $container->method('get')->willReturnCallback(fn ($name) => $services[$name]);
        $container->method('getClass')->willReturnCallback(fn ($name) => new ReflectionClass($services[$name]));
        $converterFactory = new ConverterFactory($factory, $metadata);
        $user = $this->createMock(User::class);

        $contact = $converterFactory->create('Contact', $user);
        $where = $contact->convert(SelectBuilder::create()->from('Contact'), Item::fromRaw([
            'type' => 'lastXHoursExt', 'attribute' => 'createdAt', 'value' => '3',
            'dateTime' => true, 'timeZone' => 'Asia/Tokyo',
        ]))->getRaw();
        $this->assertSame(['AND' => ['createdAt>=' => '2025-09-09 09:00:00', 'createdAt<' => '2025-09-09 12:00:00']], $where);

        $task = $converterFactory->create('Task', $user);
        $where = $task->convert(SelectBuilder::create()->from('Task'), Item::fromRaw([
            'type' => 'lastXDaysExt', 'attribute' => 'dateStart', 'value' => 2,
            'dateTime' => true, 'timeZone' => 'Asia/Tokyo',
        ]))->getRaw();
        $this->assertSame(['dateStartDate>=' => '2025-09-07', 'dateStartDate<' => '2025-09-09'], $where['OR'][0]['AND']);
        $this->assertSame('2025-09-06 15:00:00', $where['OR'][1]['AND'][0]['AND']['dateStart>=']);

        // Built-in CRM conversion still expands dateStart into both storage representations.
        $where = $task->convert(SelectBuilder::create()->from('Task'), Item::fromRaw([
            'type' => 'today', 'attribute' => 'dateStart', 'dateTime' => true, 'timeZone' => 'UTC',
        ]))->getRaw();
        $this->assertArrayHasKey('OR', $where);
    }
}
