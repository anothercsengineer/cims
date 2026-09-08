<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'sku', 'name', 'description', 'category_id', 'unit_of_measure',
        'cost_price', 'sale_price', 'reorder_point', 'reorder_quantity',
        'image_url', 'is_archived'
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'is_archived' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function stockLevels()
    {
        return $this->hasMany(Stock::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function totalStock(): int
    {
        return $this->stockLevels()->sum('quantity');
    }

    public function isLowStock(): bool
    {
        return $this->totalStock() <= $this->reorder_point;
    }
}
