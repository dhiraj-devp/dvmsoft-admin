@extends('layouts.app')

@section('content')
    @php
        $meta = config('reports.sections.'.$section);
    @endphp
    <x-page-header
        :title="$meta['label'].' reports'"
        description="Operational analytics from existing Dvmsoft records. Nothing is duplicated."
        :breadcrumbs="['Reports' => route('reports.overview'), $meta['label'] => null]"
    />
    <livewire:reports.panel :section="$section" :key="$section" />
@endsection
