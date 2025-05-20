<?php

namespace App\Helpers;

use App\Models\LogActivity;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;

class LogHelper
{
    /**
     * Catat aktivitas ke dalam database.
     */
    public static function log(string $logName, string $description, $subject = null, array $properties = [],  string $status = 'success')
    {
        $startTime = microtime(true);

        $responseTime = round((microtime(true) - LARAVEL_START) * 1000, 2); 

            LogActivity::create([
                'log_name' => $logName,
                'description' => $description,
                'subject_type' => $subject ? get_class($subject) : null,
                'subject_id' => $subject->id ?? null,
                'causer_type' => Auth::check() ? get_class(Auth::user()) : null,
                'causer_id' => Auth::id(),
                'properties' => json_encode($properties),
                'ip_address' => Request::ip(),
                'user_agent' => Request::header('User-Agent'),
                'http_method' => Request::method(),
                'response_time_ms' => $responseTime ?? null,
                'url' => Request::fullUrl(),
                'status' => $status
            ]);
      
    }
}
