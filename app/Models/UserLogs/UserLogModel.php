<?php

namespace App\Models\UserLogs;

use App\Models\UsersAccountManager\AdminAccountManagerModels;
use Illuminate\Database\Eloquent\Model;

class UserLogModel extends Model
{
    protected $table = 'user_log_models';

    protected $fillable = [
        'account_code_unique',
        'action',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(AdminAccountManagerModels::class, 'account_code_unique', 'account_code_unique');
    }
}
