<?php

namespace App\Services;

use App\Enums\ClientStatus;
use App\Enums\ClientType;
use App\Enums\LeadStatus;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeadConversionService
{
    public function __construct(protected CrmActivityLogger $activities) {}

    public function convert(Lead $lead, User $actor): Client
    {
        if ($lead->status === LeadStatus::Lost) {
            throw ValidationException::withMessages([
                'status' => 'A lost lead cannot be converted into a client.',
            ]);
        }

        if ($lead->convertedClient) {
            return $lead->convertedClient;
        }

        return DB::transaction(function () use ($lead, $actor) {
            $lead->refresh();

            if ($lead->convertedClient) {
                return $lead->convertedClient;
            }

            $client = $this->findExistingClient($lead) ?? $this->createClient($lead);
            $this->ensurePrimaryContact($client, $lead);

            $lead->forceFill([
                'status' => LeadStatus::Won,
                'converted_client_id' => $client->id,
                'converted_at' => now(),
            ])->save();

            if (! $client->converted_from_lead_id) {
                $client->forceFill(['converted_from_lead_id' => $lead->id])->save();
            }

            $this->activities->log($lead, 'converted', 'Lead converted to client', $client->name, [
                'client_id' => $client->id,
            ], $actor);

            $this->activities->log($client, 'converted', 'Client created from lead', $lead->name, [
                'lead_id' => $lead->id,
            ], $actor);

            return $client->fresh(['contacts']);
        });
    }

    protected function findExistingClient(Lead $lead): ?Client
    {
        if (blank($lead->email)) {
            return null;
        }

        return Client::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($lead->email)])
            ->first();
    }

    protected function createClient(Lead $lead): Client
    {
        $isCompany = filled($lead->company);

        return Client::query()->create([
            'account_manager_id' => $lead->assigned_user_id,
            'converted_from_lead_id' => $lead->id,
            'type' => $isCompany ? ClientType::Company : ClientType::Individual,
            'name' => $isCompany ? $lead->company : $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'status' => ClientStatus::Active,
            'notes' => $lead->notes,
        ]);
    }

    protected function ensurePrimaryContact(Client $client, Lead $lead): void
    {
        $existing = $client->contacts()
            ->when($lead->email, fn ($query) => $query->whereRaw('LOWER(email) = ?', [mb_strtolower((string) $lead->email)]))
            ->first();

        if ($existing) {
            if (! $client->contacts()->where('is_primary', true)->exists()) {
                $existing->update(['is_primary' => true]);
            }

            return;
        }

        if ($client->contacts()->where('is_primary', true)->exists() === false) {
            Contact::query()->create([
                'client_id' => $client->id,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'is_primary' => true,
                'notes' => $lead->requirement,
            ]);
        }
    }
}
