<?php

namespace Modules\Order\Domain\Order;

enum PaymentMethod: string
{
    case Cod = 'cod';
    case BankTransfer = 'bank_transfer';
    case Card = 'card';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Cod => 'Thanh toán khi nhận hàng (COD)',
            self::BankTransfer => 'Chuyển khoản ngân hàng',
            self::Card => 'Thẻ ATM / Visa / Mastercard',
        };
    }

    public function requiresOnlinePayment(): bool
    {
        return $this === self::BankTransfer || $this === self::Card;
    }
}
