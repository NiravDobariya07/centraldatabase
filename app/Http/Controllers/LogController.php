<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use App\Models\LogAccessToken;
use App\Mail\LogAccessTokenMail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LogController extends Controller
{
    public function generateToken() {
        try {
            // Generate a random token
            $token = Str::random(32);
            $requestIp = request()->ip();
            
            // Get expiration time from logging config and cast to integer
            $expirationMinutes = (int) env('LOG_TOKEN_EXPIRATION');
            
            // Save the token in the database with the expiration time
            LogAccessToken::create([
                'request_ip' => $requestIp,
                'token' => $token,
                'expires_at' => Carbon::now()->addMinutes($expirationMinutes),
            ]);
            
            // Get developer emails from config
            $developerEmails = explode(',', env('DEVELOPER_EMAILS'));
            
            // Create the log viewing URL with the token
            $logUrl = route('view.logs', ['token' => $token]);
            // Send the email
            Mail::to($developerEmails)->send(new LogAccessTokenMail($logUrl, $expirationMinutes, $requestIp));
        } catch (\Exception $e) {
            // Log the error to the activity log
            Log::channel('activity_log')->error('Failed to send email: ' . $e->getMessage());
            abort(500);
        }

        return response()->json(['message' => 'Email sent with log access link.']);
    }

    public function viewLogs($token)
    {
        // Retrieve the token from the database, ordered by id in descending order
        $storedToken = LogAccessToken::where('token', $token)
            ->orderBy('id', 'DESC')
            ->first();

        if (empty($storedToken)) {
            abort(404);
        } else if (!empty($storedToken->isExpired())) {
            return view('token-expired');
        }

        return app(\Rap2hpoutre\LaravelLogViewer\LogViewerController::class)->index();
    }

    public function viewAdminLogs() {
        return app(\Rap2hpoutre\LaravelLogViewer\LogViewerController::class)->index();
    }
}
