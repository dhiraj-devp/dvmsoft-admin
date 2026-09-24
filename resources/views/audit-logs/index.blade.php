@extends('layouts.app')

@section('content')
    <x-page-header title="Audit logs" description="A chronological record of sensitive actions across the operating system." :breadcrumbs="['Administration' => route('audit-logs.index'), 'Audit logs' => null]" />
    <livewire:audit-logs.index />
@endsection
