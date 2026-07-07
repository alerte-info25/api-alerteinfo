<?php

namespace App\Http\Controllers\API\V1\Logs;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\UserLogs\UserLogModel;

class LogController extends Controller
{

    public function ctrl_getLogs()
    {
        try {

            $logs = DB::table('user_log_models')
            ->join('admin_account_manager_models', 'user_log_models.account_code_unique', '=', 'admin_account_manager_models.account_code_unique')
            ->select([
                'user_log_models.id',
                'admin_account_manager_models.first_name',
                'admin_account_manager_models.last_name',
                'user_log_models.action',
                'user_log_models.description',
                'user_log_models.created_at'
            ])
            ->orderBy('user_log_models.created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'code' => 200,
            'logs' => $logs
        ], 200);

        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return response()->json([
                'status' => false,
                'code' => 500,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
