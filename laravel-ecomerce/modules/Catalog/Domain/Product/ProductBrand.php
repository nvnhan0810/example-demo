<?php

namespace Modules\Catalog\Domain\Product;

final class ProductBrand
{
    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            'NovaWear',
            'TechPulse',
            'GlowLab',
            'HomeNest',
            'Sportify',
            'PageTurner',
            'FreshBite',
            'TinyCare',
            'UrbanKit',
            'Aether',
        ];
    }
}
