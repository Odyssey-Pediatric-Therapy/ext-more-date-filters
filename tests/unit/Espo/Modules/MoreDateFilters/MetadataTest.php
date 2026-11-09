<?php

namespace tests\unit\Espo\Modules\MoreDateFilters;

use Espo\Core\InjectableFactory;
use Espo\Core\Select\Where\DateTimeItemTransformer as Transformer;
use Espo\Core\Select\Where\Item;
use Espo\Core\Utils\Metadata;
use Espo\Modules\MoreDateFilters\Classes\Metadata\DateFilters;
use Espo\Modules\MoreDateFilters\Select\Where\DateTimeItemTransformer;
use Espo\Modules\MoreDateFilters\Select\Where\ItemConverters\LastXHours;
use PHPUnit\Framework\TestCase;

class MetadataTest extends TestCase
{
    public function testExistingNativeViewsAreAdaptedAndCustomViewsArePreserved(): void
    {
        $data = json_decode(json_encode([
            'entityDefs' => ['Contact' => ['fields' => [
                'foreignDate' => ['type' => 'foreign', 'view' => 'views/fields/foreign-date'],
                'foreignTime' => ['type' => 'foreign', 'view' => 'views/fields/foreign-datetime'],
                'custom' => ['type' => 'datetime', 'view' => 'custom:views/fields/date'],
                'short' => ['type' => 'datetime', 'view' => 'views/fields/datetime-short'],
            ]], 'Meeting' => []],
            'selectDefs' => ['Meeting' => ['whereDateTimeItemTransformerClassName' => 'CrmTransformer']],
        ]));
        $builder = new DateFilters();
        $builder->build($data);
        $this->assertSame('more-date-filters:views/fields/foreign-date', $data->entityDefs->Contact->fields->foreignDate->view);
        $this->assertSame('more-date-filters:views/fields/foreign-datetime', $data->entityDefs->Contact->fields->foreignTime->view);
        $this->assertSame('more-date-filters:views/fields/datetime-short', $data->entityDefs->Contact->fields->short->view);
        $this->assertSame('custom:views/fields/date', $data->entityDefs->Contact->fields->custom->view);
        $this->assertSame(DateTimeItemTransformer::class, $data->selectDefs->Meeting->whereDateTimeItemTransformerClassName);
        $this->assertSame('CrmTransformer', $data->selectDefs->Meeting->moreDateFiltersOriginalTransformer);
        $before = json_encode($data);
        $builder->build($data);
        $this->assertSame($before, json_encode($data), 'Building twice must not wrap the adapter recursively.');
    }

    public function testExtensionDatetimeRetainsCountAndTimezone(): void
    {
        $metadata = $this->createMock(Metadata::class);
        $metadata->method('get')->willReturn(LastXHours::class);
        $factory = $this->createMock(InjectableFactory::class);
        $factory->expects($this->never())->method('createWithBinding');
        $transformer = new DateTimeItemTransformer('Contact', $metadata, $factory, $this->createMock(\Espo\Entities\User::class));
        $item = Item::fromRaw(['type' => 'lastXHoursExt', 'attribute' => 'createdAt', 'value' => '3', 'dateTime' => true, 'timeZone' => 'Asia/Tokyo']);
        $result = $transformer->transform($item);
        $this->assertSame('3', $result->getValue());
        $this->assertInstanceOf(Item\Data\Date::class, $result->getData());
        $this->assertSame('Asia/Tokyo', $result->getData()->getTimeZone());
        $this->assertInstanceOf(Item\Data\DateTime::class, $item->getData());
    }

    public function testBuiltInFiltersDelegateToOriginalTransformer(): void
    {
        $metadata = $this->createMock(Metadata::class);
        $metadata->method('get')->willReturnCallback(fn ($path) => $path[0] === 'selectDefs' ? 'OriginalTransformer' : null);
        $item = Item::fromRaw(['type' => 'today', 'attribute' => 'createdAt', 'dateTime' => true]);
        $expected = Item::fromRaw(['type' => 'equals', 'attribute' => 'createdAt', 'value' => 'preserved']);
        $original = $this->createMock(Transformer::class);
        $original->expects($this->once())->method('transform')->with($item)->willReturn($expected);
        $factory = $this->createMock(InjectableFactory::class);
        $factory->expects($this->once())->method('createWithBinding')->with('OriginalTransformer', $this->isInstanceOf(\Espo\Core\Binding\BindingContainer::class))->willReturn($original);
        $this->assertSame($expected, (new DateTimeItemTransformer('Meeting', $metadata, $factory, $this->createMock(\Espo\Entities\User::class)))->transform($item));
    }

    public function testFieldRegistrationsUseActualEspoTypes(): void
    {
        $base = dirname(__DIR__, 5) . '/src/files/custom/Espo/Modules/MoreDateFilters/Resources/metadata/';
        $foreign = json_decode(file_get_contents($base . 'fields/foreign.json'), true);
        $this->assertSame('more-date-filters:views/fields/foreign-date', $foreign['fieldTypeViewMap']['date']);
        $optional = json_decode(file_get_contents($base . 'fields/datetimeOptional.json'), true);
        $this->assertSame('more-date-filters:views/fields/datetime-optional', $optional['view']);
        $range = json_decode(file_get_contents($base . 'entityDefs/WorkingTimeRange.json'), true);
        $this->assertSame('more-date-filters:views/working-time-range/fields/date-end', $range['fields']['dateEnd']['view']);
    }
}
