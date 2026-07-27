<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'is_active'];

    protected static function booted(): void
    {
        static::creating(function (Organization $organization) {
            $organization->slug = $organization->slug ?: Str::slug($organization->name) . '-' . Str::random(5);
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function groupAdmin(): ?User
    {
        return $this->users()->role('group-admin')->first();
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
