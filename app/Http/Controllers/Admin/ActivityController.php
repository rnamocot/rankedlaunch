<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function __invoke(Request $request)
    {
        $type = $request->query('type');

        return view('admin.activity', [
            'type' => $type,
            'logs' => ActivityLog::with('user', 'product')
                ->when(array_key_exists((string) $type, ActivityLog::TYPES), fn ($q) => $q->where('type', $type))
                ->latest('created_at')
                ->paginate(50)
                ->withQueryString(),
        ]);
    }
}
