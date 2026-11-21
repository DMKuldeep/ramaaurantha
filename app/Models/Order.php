<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'user_id', 'customer_name', 'email', 'phone', 'address',
        'landmark', 'city', 'state', 'pincode', 'note', 'subtotal', 'delivery',
        'total', 'payment_method', 'status', 'inventory_released_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'delivery' => 'integer',
            'total' => 'integer',
            'inventory_released_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
