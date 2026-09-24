<?php

namespace App\Http\Controllers\Portal;

use App\Services\AuditLogger;
use App\Services\ClientPortal\ClientChangeRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChangeRequestController extends PortalController
{
    public function index(Request $request): View
    {
        $user = $this->portalUser();
        $status = $request->string('status')->toString();

        $changeRequests = $this->access()->changeRequests($user)
            ->with('project')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('portal.change-requests.index', compact('changeRequests', 'status'));
    }

    public function create(): View
    {
        $user = $this->portalUser();

        return view('portal.change-requests.create', [
            'projects' => $this->access()->projects($user)->orderBy('name')->get(['id', 'name', 'number']),
        ]);
    }

    public function store(Request $request, ClientChangeRequestService $changeRequests, AuditLogger $audit): RedirectResponse
    {
        $user = $this->portalUser();

        $validated = $request->validate([
            'project_id' => ['required', 'ulid', Rule::exists('projects', 'id')->where('client_id', $user->client_id)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:8000'],
            'impact_on_timeline_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
        ]);

        $changeRequest = $changeRequests->create($user, $validated);

        $audit->record(
            action: 'created',
            module: 'client_portal',
            auditable: $changeRequest,
            newValues: array_merge($user->auditActorValues(), [
                'change_request_id' => $changeRequest->id,
                'number' => $changeRequest->number,
            ]),
            user: $user,
        );

        return redirect()
            ->route('client.change-requests.show', $changeRequest)
            ->with('status', 'Change request submitted.');
    }

    public function show(string $changeRequest): View
    {
        $user = $this->portalUser();
        $changeRequest = $this->access()->changeRequest($user, $changeRequest)->load('project');

        return view('portal.change-requests.show', compact('changeRequest'));
    }
}
