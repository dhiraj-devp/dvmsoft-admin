@props(['text' => ''])

@if ($text !== '')
    <button
        type="button"
        class="btn-secondary text-xs"
        x-data="{ copied: false }"
        x-on:click="
            navigator.clipboard.writeText({{ \Illuminate\Support\Js::from($text) }});
            copied = true;
            setTimeout(() => copied = false, 1500);
            $wire.copyNotice('Copied to clipboard');
        "
        x-text="copied ? 'Copied' : 'Copy'"
    ></button>
@endif
