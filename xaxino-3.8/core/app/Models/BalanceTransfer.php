<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceTransfer extends Model
{
    public function sendUser()
    {
        return $this->belongsTo(User::class, 'sender_id', 'id');
    }

    public function receiveUser()
    {
        return $this->belongsTo(User::class, 'receiver_id', 'id');
    }
}
