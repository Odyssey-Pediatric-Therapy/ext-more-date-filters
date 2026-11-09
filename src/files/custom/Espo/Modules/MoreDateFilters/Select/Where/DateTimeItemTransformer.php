<?php

namespace Espo\Modules\MoreDateFilters\Select\Where;

use Espo\Core\InjectableFactory;
use Espo\Core\Binding\Binder;
use Espo\Core\Binding\BindingData;
use Espo\Core\Binding\BindingContainer;
use Espo\Entities\User;
use Espo\Core\Select\Where\DateTimeItemTransformer as Transformer;
use Espo\Core\Select\Where\DefaultDateTimeItemTransformer;
use Espo\Core\Select\Where\Item;
use Espo\Core\Utils\Metadata;

/** Preserve extension values and timezone before Espo's built-in datetime conversion. */
class DateTimeItemTransformer implements Transformer
{
    public function __construct(
        private string $entityType,
        private Metadata $metadata,
        private InjectableFactory $injectableFactory,
        private User $user,
    ) {}

    public function transform(Item $item): Item
    {
        $class = $this->metadata->get(['app', 'select', 'whereItemConverterClassNameMap', $item->getType()]);
        if (is_string($class) && is_a($class, RangeConverter::class, true)) {
            $data = $item->getData();
            $timezone = $data instanceof Item\Data\DateTime || $data instanceof Item\Data\Date ?
                $data->getTimeZone() : null;
            return $item->withData(Item\Data\Date::create()->withTimeZone($timezone));
        }

        /** @var class-string<Transformer> $original */
        $original = $this->metadata->get(['selectDefs', $this->entityType, 'moreDateFiltersOriginalTransformer'])
            ?? DefaultDateTimeItemTransformer::class;
        $binding = new BindingData();
        $binder = new Binder($binding);
        $binder->bindInstance(User::class, $this->user);
        $binder->for($original)->bindValue('$entityType', $this->entityType);
        $binder->for(DefaultDateTimeItemTransformer::class)->bindValue('$entityType', $this->entityType);
        $transformer = $this->injectableFactory->createWithBinding($original, new BindingContainer($binding));
        return $transformer->transform($item);
    }
}
