<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Couriers\Models\Courier;
use Illuminate\View\View;

class CourierController extends Controller
{
    public function index(): View
    {
        return view('admin.couriers', [
            'title' => 'Kuryeler',
            'couriers' => Courier::query()->with(['firm', 'user'])->latest()->paginate(40),
        ]);
    }
}
