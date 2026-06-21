<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * IPホワイトリストモデル
 */
class IpWhitelist extends Model
{
    protected $table = 'ip_whitelist';

    protected $fillable = [
        'ip_address',
        'description',
    ];
}
