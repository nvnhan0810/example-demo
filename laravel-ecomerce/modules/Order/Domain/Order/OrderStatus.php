<?php

namespace Modules\Order\Domain\Order;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

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
            self::PendingPayment => 'Chờ thanh toán',
            self::Confirmed => 'Đã xác nhận',
            self::Processing => 'Đang xử lý',
            self::Completed => 'Hoàn tất',
            self::Cancelled => 'Đã hủy',
        };
    }
}
