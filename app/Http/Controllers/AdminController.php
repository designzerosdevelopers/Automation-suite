<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        // Only Super Admin can access
        if (auth()->id() !== 1) abort(403, 'Unauthorized');

        $users = User::where('id', '!=', 1)->get();

        return view('admin.approvals', compact('users'));
    }


    public function approveUser(User $user)
    {
        if (auth()->id() !== 1) abort(403, 'Unauthorized');

        $user->update(['is_approved' => true]);

        return back()->with('success', "{$user->name} has been approved ✅");
    }

    public function unApproveUser(User $user)
    {
        if (auth()->id() !== 1) abort(403, 'Unauthorized');

        $user->update(['is_approved' => false]);

        return back()->with('success', "{$user->name} has been unapproved ❌");
    }
}
