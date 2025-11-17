<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Product extends Model
{
    protected $connection = 'mongodb';
    protected $collection  = 'products';

    // Cho phép fill đúng theo JSON trong MongoDB
    protected $fillable = [
        'sku',
        'name',
        'description',
        'category',
        'brand',
        'status',
        'price',
        'images',
        'variants',
        'videoUrl',

        // Giữ lại nếu anh vẫn dùng các field cũ ở chỗ khác
        'unit',
        'list_price',
        'override_ln',
        'stock',
        'vtnn_name',
    ]; 

    protected $casts = [
        'price'      => 'float',
        'list_price' => 'float',
        'override_ln'=> 'float',
        'stock'      => 'int',
        // KHÔNG cast images / variants = 'json' hay 'array'
    ];

    protected $attributes = [
        'status' => 'active',
    ];

    /* -------- images -------- */

    public function getImagesAttribute($value)
    {
        if (is_array($value)) return $value;

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    public function setImagesAttribute($value)
    {
        if (!is_array($value)) $value = [];

        $this->attributes['images'] = array_values(
            array_filter($value, fn ($v) => $v !== null && $v !== '')
        );
    }

    /* -------- variants -------- */

    public function getVariantsAttribute($value)
    {
        if (is_array($value)) return $value;

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    public function setVariantsAttribute($value)
    {
        $variants = [];

        if (is_array($value)) {
            foreach ($value as $v) {
                if (!is_array($v)) continue;

                $name = trim($v['name'] ?? '');
                if ($name === '') continue;

                $variants[] = [
                    'name'     => $name,
                    'price'    => isset($v['price']) ? (float)$v['price'] : 0,
                    'quantity' => isset($v['quantity']) ? (int)$v['quantity'] : 0,
                ];
            }
        }

        $this->attributes['variants'] = $variants;
    }
}
