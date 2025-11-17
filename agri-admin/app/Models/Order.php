<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\BSON\ObjectId;

class Order extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'orders';

    protected $primaryKey = '_id';
    public $incrementing = false;
    protected $keyType = 'string';

    // Dùng createdAt/updatedAt của Mongo, không để Laravel tự ghi created_at/updated_at
    public $timestamps = false;

    protected $fillable = [
        'user',
        'manager',
        'store',
        'orderItems',
        'shippingAddress',
        'paymentMethod',
        'shippingLocation',
        'subtotal',
        'shippingFee',
        'vatFee',
        'totalPrice',
        'commission',
        'amountPayableToStore',
        'status',
        'managerPaymentStatus',
        'pickupPhotoUrl',
        'deliveryPhotoUrl',
        'createdAt',
        'updatedAt',
    ];

   protected $casts = [
    'subtotal'             => 'float',
    'shippingFee'          => 'float',
    'vatFee'               => 'float',
    'totalPrice'           => 'float',
    'commission'           => 'float',
    'amountPayableToStore' => 'float',
];


    /**
     * Mapping trạng thái theo JSON (status = awaiting_payment, ...)
     */
    public const STATUSES = [
        'awaiting_payment' => [
            'label'       => 'Chờ thanh toán',
            'value'       => 1,
            'badge_class' => 'bg-amber-100 text-amber-700',
        ],
        'processing' => [
            'label'       => 'Đang xử lý',
            'value'       => 2,
            'badge_class' => 'bg-blue-100 text-blue-700',
        ],
        'shipping' => [
            'label'       => 'Đang giao hàng',
            'value'       => 3,
            'badge_class' => 'bg-indigo-100 text-indigo-700',
        ],
        'completed' => [
            'label'       => 'Hoàn thành',
            'value'       => 4,
            'badge_class' => 'bg-emerald-100 text-emerald-700',
        ],
        'cancelled' => [
            'label'       => 'Đã hủy',
            'value'       => 5,
            'badge_class' => 'bg-rose-100 text-rose-700',
        ],
    ];

    /* ================== QUAN HỆ ================== */

    // orderItems là embedded document trong 1 order
    public function items()
    {
        // Field trong Mongo là "orderItems"
        return $this->embedsMany(OrderItem::class, 'orderItems');
    }

    // Nếu sau này muốn gọi $order->orderItems thì có thể alias
    public function orderItemsRelation()
    {
        return $this->items();
    }

    /* ========== STATIC HELPER CHO CONTROLLER ========== */

    public static function getStatusOptions(): array
    {
        return self::STATUSES;
    }

    // Dùng cho timeline (key là số bước 1,2,3,...)
    public static function getTimelineSteps(): array
    {
        $steps = [];

        foreach (self::STATUSES as $key => $config) {
            $steps[$config['value']] = [
                'key'   => $key,
                'label' => $config['label'],
            ];
        }

        ksort($steps);

        return $steps;
    }

    /* ========== ACCESSOR ĐỂ GIỮ NGUYÊN BLADE CŨ ========== */

    // Mã đơn -> rút gọn từ _id
    public function getCodeAttribute(): string
    {
        return $this->shortId($this->_id ?? null);
    }

    // "Nông dân" -> tạm thời hiển thị ID user rút gọn
    public function getFarmerNameAttribute(): string
    {
        return 'User #' . $this->shortId($this->user ?? null);
    }

    // "Khu vực" -> lấy từ shippingAddress.address
    // "Khu vực" -> lấy từ shippingAddress.address (Mongo trả về array)
public function getRegionAttribute(): string
{
    // Lấy raw value từ attributes, không đi qua cast JSON
    $raw = $this->attributes['shippingAddress'] ?? null;

    // Trường hợp phổ biến: Mongo trả về array
    if (is_array($raw)) {
        return $raw['address'] ?? '-';
    }

    // Nếu lỡ lưu dạng JSON string
    if (is_string($raw)) {
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded['address'] ?? '-';
        }
        // Nếu decode lỗi thì trả luôn string
        return $raw;
    }

    return '-';
}


    // LN/TN/Store -> map từ manager/store ID cho nhìn tạm ổn
    public function getLaoNongCodeAttribute(): string
    {
        return $this->shortId($this->manager ?? null);
    }

    public function getTrangNongCodeAttribute(): string
    {
        // JSON chưa có TN riêng nên để placeholder
        return '-';
    }

    public function getStoreCodeAttribute(): string
    {
        return $this->shortId($this->store ?? null);
    }

    // Tổng tiền trong view -> map sang totalPrice trong Mongo
    public function getTotalAmountAttribute(): float
    {
        return (float) ($this->totalPrice ?? 0);
    }

    // Nhãn trạng thái + CSS class
    public function getStatusLabelAttribute(): string
    {
        $status = $this->status ?? null;

        if ($status && isset(self::STATUSES[$status])) {
            return self::STATUSES[$status]['label'];
        }

        return (string) $status;
    }

    public function getStatusBadgeClassAttribute(): string
    {
        $status = $this->status ?? null;

        if ($status && isset(self::STATUSES[$status])) {
            return self::STATUSES[$status]['badge_class'];
        }

        return 'bg-slate-100 text-slate-700';
    }

    // Bước hiện tại trên timeline
    public function getCurrentStepAttribute(): int
    {
        $status = $this->status ?? null;

        if ($status && isset(self::STATUSES[$status])) {
            return (int) (self::STATUSES[$status]['value'] ?? 1);
        }

        return 1;
    }

    // Alias createdAt trong Mongo thành created_at cho Blade dùng
    public function getCreatedAtAttribute()
    {
        if (! isset($this->attributes['createdAt'])) {
            return null;
        }

        $value = $this->attributes['createdAt'];

        if ($value instanceof \Carbon\Carbon) {
            return $value;
        }

        return \Carbon\Carbon::parse($value);
    }

    /* ================== PRIVATE HELPER ================== */

    protected function shortId($value): string
    {
        $str = '';

        if ($value instanceof ObjectId) {
            $str = (string) $value;
        } elseif (is_array($value) && isset($value['$oid'])) {
            // trường hợp bạn đọc trực tiếp JSON chưa import
            $str = $value['$oid'];
        } elseif (! is_null($value)) {
            $str = (string) $value;
        }

        if ($str === '') {
            return '-';
        }

        // Lấy 6 ký tự cuối cho gọn
        return substr($str, -6);
    }
}
