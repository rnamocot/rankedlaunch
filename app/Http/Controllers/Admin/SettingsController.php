<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\Settings;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit()
    {
        return view('admin.settings', ['fields' => Settings::FIELDS]);
    }

    public function update(Request $request, Settings $settings)
    {
        $rules = [];
        foreach (Settings::FIELDS as $group) {
            foreach ($group as $key => [$label, $type]) {
                $field = str_replace('.', '__', $key);
                $rules[$field] = match ($type) {
                    'bool' => ['boolean'],
                    'money' => ['required', 'numeric', 'min:0', 'max:10000'],
                    'int' => ['required', 'integer', 'min:0', 'max:1000'],
                    default => ['required', 'string', 'max:40'],
                };
            }
        }
        $input = $request->validate($rules);

        $values = [];
        $changed = [];
        foreach (Settings::FIELDS as $group) {
            foreach ($group as $key => [$label, $type]) {
                $field = str_replace('.', '__', $key);
                $value = match ($type) {
                    'bool' => $request->boolean($field),
                    'money' => (int) round($input[$field] * 100), // stored in cents
                    'int' => (int) $input[$field],
                    default => $input[$field],
                };
                if (config("rankedlaunch.$key") !== $value) {
                    $changed[] = $label;
                }
                $values[$key] = $value;
            }
        }

        $settings->save($values);

        if ($changed) {
            ActivityLog::record('settings', 'Changed settings: '.implode(', ', $changed), user: $request->user());
        }

        return back()->with('status', $changed ? 'Settings saved. They apply right away.' : 'No changes.');
    }
}
