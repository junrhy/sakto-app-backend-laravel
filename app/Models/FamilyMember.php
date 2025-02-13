<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class FamilyMember extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'birth_date',
        'death_date',
        'gender',
        'photo',
        'notes',
        'client_identifier'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'death_date' => 'date'
    ];

    public function relationships()
    {
        return $this->hasMany(FamilyRelationship::class, 'from_member_id');
    }

    public function relatedTo()
    {
        return $this->hasMany(FamilyRelationship::class, 'to_member_id');
    }

    public function parents()
    {
        return $this->belongsToMany(FamilyMember::class, 'family_relationships', 'from_member_id', 'to_member_id')
            ->wherePivot('relationship_type', 'parent');
    }

    public function children()
    {
        return $this->belongsToMany(FamilyMember::class, 'family_relationships', 'from_member_id', 'to_member_id')
            ->wherePivot('relationship_type', 'child');
    }

    public function spouses()
    {
        return $this->belongsToMany(FamilyMember::class, 'family_relationships', 'from_member_id', 'to_member_id')
            ->wherePivot('relationship_type', 'spouse');
    }

    public function siblings()
    {
        return $this->belongsToMany(FamilyMember::class, 'family_relationships', 'from_member_id', 'to_member_id')
            ->wherePivot('relationship_type', 'sibling');
    }
}
