<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class OrderItem extends Model
{
    // Đây là embedded document, không cần timestamps
    public $timestamps = false;

    protected $connection = 'mongodb';

    // Không set $collection vì nó được embed trong Order.orderItems

    protected $fillable = [
        'name',
        'quantity',
        'image',
        'price',
        'variantName',
        'productId',
    ];

    protected $casts = [
        'quantity' => 'int',
        'price'    => 'float',
    ];

    /* ========== ACCESSOR ĐỂ GIỮ NGUYÊN BLADE CŨ ========== */

    // Bên Blade đang dùng $item->product_code
    public function getProductCodeAttribute(): string
    {
        $value = $this->productId ?? null;
        $str   = '';

        if ($value instanceof \MongoDB\BSON\ObjectId) {
            $str = (string) $value;
        } elseif (is_array($value) && isset($value['$oid'])) {
            $str = $value['$oid'];
        } elseif (! is_null($value)) {
            $str = (string) $value;
        }

        return $str ? substr($str, -6) : '-';
    }

    // Bên Blade dùng $item->product_name
    public function getProductNameAttribute(): string
    {
        return $this->name ?? '';
    }

    // Bên Blade dùng $item->qty
    public function getQtyAttribute(): int
    {
        return (int) ($this->quantity ?? 0);
    }

    // Bên Blade dùng $item->subtotal
    public function getSubtotalAttribute(): float
    {
        return (float) (($this->price ?? 0) * ($this->quantity ?? 0));
    }
}
