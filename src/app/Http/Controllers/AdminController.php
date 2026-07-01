<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function showLoginForm(): View
    {
        return view('admin.auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = [
            'email' => $request->input('email'),
            'password' => $request->input('password'),
            'admin_status' => true,
        ];

        if (!Auth::attempt($credentials)) {
            return redirect()
                ->back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'ログイン情報が登録されていません',
                ]);
        }

        $request->session()->regenerate();

        return redirect('/admin/attendance/list');
    }
}