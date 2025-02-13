<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class FamilyRelationship extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'from_member_id',
        'to_member_id',
        'relationship_type'
    ];

    public function fromMember()
    {
        return $this->belongsTo(FamilyMember::class, 'from_member_id');
    }

    public function toMember()
    {
        return $this->belongsTo(FamilyMember::class, 'to_member_id');
    }
}
