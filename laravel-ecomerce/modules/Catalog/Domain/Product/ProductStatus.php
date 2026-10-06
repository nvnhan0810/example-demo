<?php

namespace Modules\Catalog\Domain\Product;

enum ProductStatus: string
{
    case Active = 'active';
    case Draft = 'draft';
    case Archived = 'archived';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
