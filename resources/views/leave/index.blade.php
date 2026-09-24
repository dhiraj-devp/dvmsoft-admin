@extends('layouts.app')

@section('content')
    <x-page-header title="Leave" description="Requests, balances, and approvals." :breadcrumbs="['HR' => route('hr.dashboard'), 'Leave' => null]" />
    <livewire:leave.index />
@endsection
