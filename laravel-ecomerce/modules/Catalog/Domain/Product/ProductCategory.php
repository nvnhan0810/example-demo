<?php

namespace Modules\Catalog\Domain\Product;

final class ProductCategory
{
    public const FASHION = 'Thời trang';
    public const ELECTRONICS = 'Điện tử';
    public const BEAUTY = 'Làm đẹp';
    public const HOME = 'Nhà cửa';
    public const SPORTS = 'Thể thao';
    public const BOOKS = 'Sách';
    public const FOOD = 'Thực phẩm';
    public const BABY = 'Mẹ & Bé';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::FASHION,
            self::ELECTRONICS,
            self::BEAUTY,
            self::HOME,
            self::SPORTS,
            self::BOOKS,
            self::FOOD,
            self::BABY,
        ];
    }
}
