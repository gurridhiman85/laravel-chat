<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Chat extends Model
{
    use HasFactory;
    protected $fillable = ['senderId', 'receiverId', 'status', 'content'];

    public function senderDetails(){
        return $this->hasOne(User::class, 'id', 'senderId');
    }

    public function receriverDetails(){
        return $this->hasOne(User::class, 'id', 'receiverId');
    }
}
