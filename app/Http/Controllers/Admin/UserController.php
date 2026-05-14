<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Firms\Models\Firm;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->with(['firm', 'role']);

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->integer('role_id'));
        }

        if ($request->filled('firm_id')) {
            if ($request->string('firm_id')->toString() === 'none') {
                $query->whereNull('firm_id');
            } else {
                $query->where('firm_id', $request->integer('firm_id'));
            }
        }

        return view('admin.users', [
            'title' => 'Kullanıcılar',
            'users' => $query->latest()->paginate(40)->appends($request->query()),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'firms' => Firm::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['role_id', 'firm_id']),
        ]);
    }
}
