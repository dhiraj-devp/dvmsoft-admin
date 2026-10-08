@extends('layouts.app')

@section('content')
    <x-page-header title="Office calendar" description="Mark weekly offs, holidays, and full weeks off. Staff growth uses this calendar." :breadcrumbs="['Administration' => route('settings.index'), 'Office calendar' => null]" />
    <livewire:office.calendar />
@endsection
