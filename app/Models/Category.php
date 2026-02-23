<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['user_id', 'name', 'type', 'color', 'icon', 'loan_amount', 'emi_amount', 'monthly_amount', 'target_amount'];

    protected function casts(): array
    {
        return [
            'loan_amount' => 'decimal:2',
            'emi_amount' => 'decimal:2',
            'monthly_amount' => 'decimal:2',
            'target_amount' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }
}
