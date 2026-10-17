<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\SendTestSmsRequest;
use App\Http\Requests\Settings\UpdateSmsSettingsRequest;
use App\Http\Requests\Settings\UpdateSmsTemplatesRequest;
use App\Models\SmsMessage;
use App\Models\SmsTemplate;
use App\Services\AuditService;
use App\Services\SettingsService;
use App\Services\SmsService;
use Illuminate\Http\Request;

class SmsSettingController extends Controller
{
    public function index(SmsService $sms)
    {
        $this->authorize('manage-sms');

        $provider = $sms->providerName();
        $credentials = $sms->hasCredentials();
        $templates = SmsTemplate::orderBy('key')->get();
        $stats = $sms->stats();
        $recent = SmsMessage::latest()->limit(8)->get();

        return view('super_admin.settings.sms', compact(
            'provider', 'credentials', 'templates', 'stats', 'recent'
        ));
    }

    public function update(UpdateSmsSettingsRequest $request, SettingsService $settings)
    {
        $changed = $settings->update([
            'sms.provider' => $request->input('provider'),
            'sms.from' => $request->input('from') ?: null,
            'sms.workflow_enabled' => $request->boolean('workflow_enabled'),
        ], $request->user(), 'sms');

        return redirect()->route('super_admin.sms.index')
            ->with('success', $changed === []
                ? 'SMS settings saved. No values were changed.'
                : 'SMS settings saved. '.count($changed).' value(s) updated.');
    }

    public function updateTemplates(UpdateSmsTemplatesRequest $request, AuditService $audit)
    {
        $changed = [];

        foreach ($request->input('templates') as $key => $body) {
            $template = SmsTemplate::where('key', $key)->first();

            if ($template === null || $template->body === $body) {
                continue;
            }

            $template->update(['body' => $body]);
            $changed[$key] = ['old' => $template->getOriginal('body'), 'new' => $body];
        }

        if ($changed !== []) {
            $audit->record(
                'sms.templates_updated',
                'Updated SMS templates: '.implode(', ', array_keys($changed)),
                array_map(fn ($diff) => $diff['old'], $changed),
                array_map(fn ($diff) => $diff['new'], $changed),
            );
        }

        return redirect(route('super_admin.sms.index').'#templates')
            ->with('success', $changed === []
                ? 'SMS templates saved. No changes were made.'
                : 'SMS templates saved. '.count($changed).' updated.');
    }

    public function sendTest(SendTestSmsRequest $request, SmsService $sms)
    {
        $message = $sms->send(
            $request->input('recipient'),
            $request->input('message'),
            ['template' => 'test', 'sent_by' => $request->user()->id],
        );

        if ($message->status === SmsMessage::STATUS_SENT) {
            return back()->with('success', "Test message dispatched to {$message->recipient} (provider: {$message->provider}).");
        }

        return back()->with('error', 'Test message not sent: '.($message->error ?? 'unknown error'));
    }

    public function log(Request $request)
    {
        $this->authorize('manage-sms');

        $status = (string) $request->query('status', '');

        $messages = SmsMessage::query()
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($request->filled('q'), function ($query) use ($request) {
                $like = '%'.$request->query('q').'%';
                $query->where(fn ($q) => $q
                    ->where('recipient', 'like', $like)
                    ->orWhere('body', 'like', $like)
                    ->orWhere('template', 'like', $like));
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('super_admin.settings.sms-log', compact('messages', 'status'));
    }
}
