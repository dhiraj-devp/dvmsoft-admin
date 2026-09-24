<div class="card p-6">
    <form wire:submit="save" class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="label">Name</label>
                <input type="text" wire:model="form.name" class="input">
                @error('form.name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">Email</label>
                <input type="email" wire:model="form.email" class="input">
                @error('form.email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">Phone</label>
                <input type="text" wire:model="form.phone" class="input">
            </div>
            <div>
                <label class="label">Job title</label>
                <input type="text" wire:model="form.job_title" class="input">
            </div>
            <div>
                <label class="label">Department</label>
                <select wire:model="form.department_id" class="input">
                    <option value="">None</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Manager</label>
                <select wire:model="form.manager_id" class="input">
                    <option value="">None</option>
                    @foreach ($managers as $manager)
                        <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Joining date</label>
                <input type="date" wire:model="form.date_of_joining" class="input">
            </div>
            <div>
                <label class="label">Employee code</label>
                <input type="text" wire:model="form.employee_code" class="input uppercase" placeholder="Auto-generated if blank" @if($employee) readonly @endif>
                @error('form.employee_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">Profile photo</label>
                <input type="file" wire:model="photo" accept="image/*" class="input">
                @error('photo')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <label class="label">Employment type</label>
                <select wire:model="form.employment_type" class="input">
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Employment status</label>
                <select wire:model="form.employment_status" class="input">
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Probation status</label>
                <select wire:model="form.probation_status" class="input">
                    @foreach ($probationStatuses as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Probation days</label>
                <input type="number" wire:model="form.probation_days" class="input">
            </div>
            <div>
                <label class="label">Probation end</label>
                <input type="date" wire:model="form.probation_end_date" class="input">
            </div>
            <div>
                <label class="label">Confirmation date</label>
                <input type="date" wire:model="form.confirmation_date" class="input">
            </div>
            <div>
                <label class="label">Exit date</label>
                <input type="date" wire:model="form.exit_date" class="input">
            </div>
            <div class="md:col-span-2">
                <label class="label">Exit reason</label>
                <input type="text" wire:model="form.exit_reason" class="input">
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="label">Address</label>
                <textarea wire:model="form.address" rows="3" class="input"></textarea>
            </div>
            <div>
                <label class="label">Emergency contact</label>
                <input type="text" wire:model="form.emergency_contact_name" class="input mb-3" placeholder="Name">
                <input type="text" wire:model="form.emergency_contact_phone" class="input" placeholder="Phone">
            </div>
        </div>

        <div>
            <label class="label">Notes</label>
            <textarea wire:model="form.notes" rows="3" class="input"></textarea>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('employees.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Save employee</button>
        </div>
    </form>
</div>
