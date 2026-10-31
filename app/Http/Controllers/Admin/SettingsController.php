<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    private const NOTIFICATIONS = [
        'new_reports'       => 'New & awaiting-verification reports',
        'priority_orders'   => 'High-priority & nearing-due orders',
        'completed_repairs' => 'Completed repairs for review',
    ];

    private const TARGET_DAYS = [1 => '1 working day', 2 => '2 working days', 3 => '3 working days', 5 => '5 working days', 7 => '7 working days'];

    public function index(Request $request)
    {
        $user  = $request->user();
        $parts = preg_split('/\s+/', trim((string) $user->name)) ?: [];
        $last  = count($parts) > 1 ? array_pop($parts) : '';

        $saved = json_decode((string) ($user->notification_preferences ?? ''), true) ?: [];

        return view('admin.settings.index', [
            'user'          => $user,
            'firstName'     => implode(' ', $parts),
            'lastName'      => $last,
            'campus'        => [
                'institution' => Setting::get('campus.institution'),
                'extension'   => Setting::get('campus.helpdesk_extension'),
                'timezone'    => config('app.timezone'),
            ],
            'system'        => [
                'target_days' => (int) Setting::get('system.target_days'),
                'evidence'    => Setting::get('system.evidence_limit'),
            ],
            'notifications' => collect(self::NOTIFICATIONS)->map(fn ($label, $key) => ['label' => $label, 'on' => (bool) ($saved[$key] ?? true)])->all(),
            'targetOptions' => self::TARGET_DAYS,
            'counts'        => [
                'maintenance' => Technician::count(),
                'admins'      => User::where('role', 'admin')->count(),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'first_name'        => ['required', 'string', 'max:60'],
            'last_name'         => ['nullable', 'string', 'max:60'],
            'institution'       => ['required', 'string', 'max:120'],
            'helpdesk_extension' => ['nullable', 'string', 'max:20'],
            'target_days'       => ['required', Rule::in(array_keys(self::TARGET_DAYS))],
            'evidence_limit'    => ['nullable', 'string', 'max:80'],
        ], ['first_name.required' => 'Enter your first name.', 'institution.required' => 'Enter the institution name.']);

        $user = $request->user();
        $user->name = trim($data['first_name'] . ' ' . ($data['last_name'] ?? ''));
        $user->notification_preferences = json_encode(
            collect(self::NOTIFICATIONS)->mapWithKeys(fn ($label, $key) => [$key => $request->boolean("notify.$key")])->all()
        );
        $user->save();

        Setting::put([
            'campus.institution'        => $data['institution'],
            'campus.helpdesk_extension' => $data['helpdesk_extension'] ?? '',
            'system.target_days'        => (string) $data['target_days'],
            'system.evidence_limit'     => $data['evidence_limit'] ?? '',
        ]);

        ActivityLog::record('settings.updated', 'Updated portal settings');

        return redirect()->route('admin.settings')->with('status', 'Settings saved.');
    }
}
