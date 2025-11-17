<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\{TwoFactorCode, User};
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\TwoFactorCodeMail;
use App\Jobs\SendOtpJob;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            // If 2FA is enabled, generate OTP and send email
            if ($user->two_fa_enabled && $user->two_fa_method === 'email') {
                $otp = rand(100000, 999999); // Generate OTP
                $expiryMinutes = env('OTP_EXPIRY_TIME', 10); // Get expiry time from env

                // Store OTP in the database
                TwoFactorCode::create([
                    'user_id' => $user->id,
                    'code' => $otp,
                    'method' => 'email',
                    'expires_at' => Carbon::now()->addMinutes($expiryMinutes),
                ]);

                // Send OTP to user's email
                // SendOtpJob::dispatch($user->email, $otp)->onQueue(config('queue.queues.high_priority_queue'));
                Mail::to($user->email)->send(new TwoFactorCodeMail($otp));

                // Store user details in session to use later for authentication
                session(['2fa_user_id' => $user->id]);
                // Store remember me preference for use after 2FA verification
                if ($request->has('remember') && $request->remember) {
                    session(['remember_me' => true]);
                }

                return redirect()->route('admin.2fa.verify'); // Redirect to OTP verification page
            }

            // If 2FA is not enabled, authenticate the user and create the session immediately
            // Handle "remember me" checkbox
            $remember = $request->has('remember') && $request->remember;
            Auth::login($user, $remember);
            return redirect()->route('index'); // Redirect to homepage
        }

        return back()->withErrors(['email' => 'Invalid credentials']);
    }

    public function show2faForm() {
        return view('auth.2fa');
    }

    public function verify2fa(Request $request)
    {
        $request->validate([
            'code' => 'required|digits:6', // OTP validation
        ]);

        // Get the stored user ID from session
        $userId = session('2fa_user_id');
        if (!$userId) {
            return redirect()->route('login')->withErrors(['email' => 'Session expired, please log in again.']);
        }

        $user = User::findOrFail($userId);

        // Check if the OTP is valid
        $otpRecord = TwoFactorCode::where('user_id', $userId)
            ->where('code', $request->code)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$otpRecord) {
            return back()->withErrors(['code' => 'Invalid or expired OTP']);
        }

        // If OTP is valid, authenticate the user
        // Check if remember me was set during initial login
        $remember = session('remember_me', false);
        Auth::login($user, $remember);

        // Optionally, you can remove the 2FA session key after successful login
        session()->forget('2fa_user_id');
        session()->forget('remember_me');

        return redirect()->route('index'); // Redirect to homepage
    }

    public function resend2faCode() {
        $user = User::find(session('2fa_user_id'));

        if (!$user) {
            return redirect()->route('login')->withErrors(['code' => 'Session expired. Please log in again.']);
        }

        // Generate new OTP
        $otp = rand(100000, 999999);
        $user->twoFactorCodes()->create([
            'code' => $otp,
            'method' => 'email',
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        // Send OTP via email
        // SendOtpJob::dispatch($user->email, $otp)->onQueue(config('queue.queues.high_priority_queue'));
        Mail::to($user->email)->send(new TwoFactorCodeMail($otp));

        return back()->with('success', 'A new verification code has been sent to your email.');
    }

    public function logout(Request $request) {
        Auth::logout(); // Logs out the user

        // Invalidate the session
        $request->session()->invalidate();

        // Regenerate the CSRF token to prevent session fixation attacks
        $request->session()->regenerateToken();

        return redirect()->route('login'); // Redirect to the login page
    }

    public function sendPasswordResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if($status === Password::RESET_LINK_SENT) {
            return redirect()->back()->with('success', __($status));
        } else {
            return redirect()->back()->with('error', __($status));
        }
    }

    public function showResetForm(Request $request, $token)
    {
        return view('auth.password-reset', ['token' => $token, 'email' => $request->email]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:8|confirmed',
            'token' => 'required',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __('Password reset successful!'));
        } else {
            return redirect()->back()->with('error', __($status));
        }
    }

    public function getProfilePage(Request $request) {
        $user = Auth::user();
        return view('pages.profile', ['user' => $user]);
    }

    public function generatePasswordResetToken(Request $request)
    {
        try {
            // Validate Password Fields
            $request->validate([
                'password' => 'required|string|min:8',
                'password_confirmation' => 'required|string|min:8|same:password',
            ]);

            // Get Authenticated User
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }

            // Generate OTP
            $otp = rand(100000, 999999);
            $expiryMinutes = env('OTP_EXPIRY_TIME', 10);

            // Store OTP in Database
            TwoFactorCode::updateOrCreate(
                ['user_id' => $user->id, 'method' => 'email'], // Unique OTP per user
                ['code' => $otp, 'expires_at' => Carbon::now()->addMinutes($expiryMinutes)]
            );

            // Send OTP to Email
            Mail::to($user->email)->send(new TwoFactorCodeMail($otp));

            return response()->json([
                'message' => 'OTP sent successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateAdminPassword(Request $request)
    {
        try {
            // Validate Input Fields
            $request->validate([
                'password' => 'required|string|min:8',
                'password_confirmation' => 'required|string|min:8|same:password',
                'code' => 'required|digits:6',
            ]);

            // Get Authenticated User
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }

            // Check if OTP is valid
            $otpRecord = TwoFactorCode::where('user_id', $user->id)
                ->where('code', $request->code)
                ->where('expires_at', '>', Carbon::now()) // Ensure OTP is not expired
                ->first();

            if (!$otpRecord) {
                return response()->json(['message' => 'Invalid or expired OTP'], 422);
            }

            // Update User Password
            $user->update(['password' => Hash::make($request->password)]);

            // Remove OTP after successful password reset
            $otpRecord->delete();

            // Log out user and invalidate session
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();

            return response()->json([
                'message' => 'Password updated successfully! Please log in again.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateAdminTwoFactor(Request $request)
    {
        try {
            // Validate input
            $request->validate([
                'two_fa_enabled' => 'required|boolean',
            ]);

            // Get Authenticated User
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }

            // Update Two-Factor Authentication status
            $user->update([
                'two_fa_enabled' => $request->two_fa_enabled,
            ]);

            return response()->json([
                'message' => $request->two_fa_enabled
                    ? 'Two-Factor Authentication enabled successfully.'
                    : 'Two-Factor Authentication disabled successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateProfile(Request $request) {
        $user = Auth::user();
        $request->validate([
            'name' => 'required|string|max:255',
            'profile_image' => 'nullable|image|mimes:jpeg,png,webp|max:2048', // Restrict to images
        ]);

        try {
            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                // Delete the old image if it exists
                if ($user->profile_image) {
                    Storage::disk('public')->delete($user->profile_image);
                }

                $imagePath = $request->file('profile_image')->store('profile_images', 'public');
                $user->profile_image = $imagePath;
            }

            $user->name = $request->name;
            $user->save();

            return redirect()->back()->with('success', 'Profile updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred while updating the profile. Please try again.');
        }
    }
}
