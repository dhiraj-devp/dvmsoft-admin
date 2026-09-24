@props([
    'generating' => false,
    'generated' => false,
    'error' => null,
    'applied' => false,
])

<div class="space-y-2 text-sm">
    @if ($generating)
        <p class="text-ink-500">Generating…</p>
    @endif
    @if ($error)
        <p class="rounded-xl bg-red-50 px-3 py-2 text-red-700 dark:bg-red-950/40 dark:text-red-200">{{ $error }}</p>
    @endif
    @if ($generated && ! $error)
        <p class="rounded-xl bg-emerald-50 px-3 py-2 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">Suggestion ready. Nothing was saved or sent automatically.</p>
    @endif
    @if ($applied)
        <p class="text-xs text-ink-500">Applied to the form only. Review before you save or send.</p>
    @endif
</div>
