<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    protected $fillable = ['user_id', 'category_id', 'month', 'year', 'amount'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
