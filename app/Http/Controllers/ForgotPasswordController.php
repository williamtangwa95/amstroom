<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    // Step 1: Show enter email form
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    // Step 1 Submit: Generate 6-digit recovery code & send email
    public function sendResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'No account found with this email address.',
        ]);

        $code = sprintf('%06d', random_int(100000, 999999));

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token'      => Hash::make($code),
                'created_at' => now(),
            ]
        );

        // Try sending email notification
        try {
            Mail::raw("Your AmstRoom Password Recovery Code is: {$code}\n\nThis code will expire in 60 minutes. If you did not request a password reset, please ignore this email.", function ($message) use ($request) {
                $message->to($request->email)
                        ->subject('AmstRoom — Password Recovery Code');
            });
        } catch (\Exception $e) {
            // Mail sending failover fallback (logs to laravel.log)
            \Illuminate\Support\Facades\Log::info("Password recovery code for {$request->email}: {$code}");
        }

        session(['reset_email' => $request->email]);

        return redirect()->route('password.verify-form')
            ->with('success', "A 6-digit recovery code has been sent to {$request->email}.");
    }

    // Step 2: Show verify code form
    public function showVerifyForm(Request $request)
    {
        $email = session('reset_email', $request->query('email'));
        return view('auth.verify-code', compact('email'));
    }

    // Step 2 Submit: Validate code
    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'code'  => 'required|digits:6',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$record) {
            return back()->withErrors(['code' => 'Invalid or expired recovery request. Please request a new code.']);
        }

        // Check expiration (60 mins)
        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return redirect()->route('password.request')->withErrors(['email' => 'Recovery code has expired. Please request a new one.']);
        }

        if (!Hash::check($request->code, $record->token)) {
            return back()->withInput()->withErrors(['code' => 'The recovery code you entered is incorrect.']);
        }

        // Mark verified in session
        session([
            'code_verified' => true,
            'reset_email'   => $request->email,
        ]);

        return redirect()->route('password.reset-form');
    }

    // Step 3: Show new password form
    public function showResetForm()
    {
        if (!session('code_verified') || !session('reset_email')) {
            return redirect()->route('password.request')->withErrors(['email' => 'Please verify your recovery code first.']);
        }

        $email = session('reset_email');
        return view('auth.reset-password', compact('email'));
    }

    // Step 3 Submit: Reset password
    public function resetPassword(Request $request)
    {
        if (!session('code_verified') || !session('reset_email')) {
            return redirect()->route('password.request')->withErrors(['email' => 'Session expired. Please request a new code.']);
        }

        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $email = session('reset_email');
        $user = User::where('email', $email)->firstOrFail();

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Clean up
        DB::table('password_reset_tokens')->where('email', $email)->delete();
        session()->forget(['code_verified', 'reset_email']);

        return redirect()->route('login')->with('success', 'Your password has been reset successfully! You can now log in with your new password.');
    }
}
