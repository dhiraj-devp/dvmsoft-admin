@extends('layouts.app')

@section('content')
    <x-page-header
        :title="$automation->name"
        :description="$automation->description"
        :breadcrumbs="['Automations' => route('automations.index'), $automation->name => null]"
    />
    <livewire:automations.show :automation="$automation" :key="$automation->id" />
@endsection
