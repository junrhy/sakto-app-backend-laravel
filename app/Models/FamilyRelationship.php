<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FamilyRelationship extends Model
{
    use HasFactory;

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
