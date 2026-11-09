@extends('layouts.app')

@section('page-title', 'ENTITIES')
@section('title', 'Entity Profile - Purchasing')

@section('header-actions')
@endsection

@section('content')

    @php
        // ------------------------------------------------------------------
        // This single view renders two profile layouts depending on $type:
        //   'procuring_entity' or 'supplier'.
        //
        // Records are provided by EntityController::show() from the real
        // database rows for the selected type.
        // ------------------------------------------------------------------

        $type = $type ?? 'procuring_entity';
    @endphp

    @if($type === 'procuring_entity')
        @php
            // Fetched by EntityController::show() from the real database row.
            $entity = $entity ?? [];
            $contactRows = [
                ['CONTACT PERSON', $entity['contact_person']],
                ['CONTACT NO.', $entity['contact_no']],
                ['EMAIL', $entity['email']],
            ];
            $relatedProjects = [
                ['name' => 'Pharma supplies Q3', 'reference' => 'RFQ-2026-0042', 'status' => 'In progress'],
                ['name' => 'PPE bulk order', 'reference' => 'RFQ-2026-0031', 'status' => 'Completed'],
            ];
        @endphp

        {{-- Type label --}}
        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-3">PROCURING ENTITY PROFILE</p>

        {{-- Header card --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <h2 class="text-xl font-bold text-gray-900">{{ $entity['name'] }}</h2>
            <p class="text-sm text-gray-500">{{ $entity['address'] }}</p>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 mt-2">{{ $entity['type'] }}</span>
        </div>

        {{-- Contact details --}}
        <div class="mt-6">
            <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-3">CONTACT DETAILS</p>
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm divide-y divide-gray-100">
                @foreach($contactRows as $row)
                    <div class="flex items-center justify-between px-5 py-3">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $row[0] }}</p>
                        <p class="text-sm font-medium text-gray-900">{{ $row[1] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    {{-- Related projects --}}
        <div class="mt-6">
            <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-3">RELATED PROJECTS</p>
            <div class="space-y-3">
                @foreach($relatedProjects as $project)
                    @php
                        $projectPill = $project['status'] === 'Completed' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700';
                    @endphp
                    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm font-bold text-gray-900">{{ $project['name'] }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $project['reference'] }}</p>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $projectPill }}">{{ $project['status'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3 mt-8">
            <form method="POST" action="{{ route('entities.destroy', ['type' => 'procuring_entity', 'id' => $id]) }}">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="flex items-center gap-2 px-4 py-2 rounded-md border border-red-300 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete Entity
                </button>
            </form>
            <a href="{{ route('entities.edit', ['type' => 'procuring_entity', 'id' => $id]) }}"
               class="flex items-center gap-2 px-4 py-2 rounded-md bg-[#2a7a94] text-white text-sm font-medium hover:bg-[#236b80] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit Entity
            </a>
        </div>
    @else
        @php
            // Fetched by EntityController::show() from the real database row.
            $supplier = $supplier ?? [];
            $contactRows = [
                ['CONTACT PERSON', $supplier['contact_person']],
                ['CONTACT NO.', $supplier['contact_no']],
                ['EMAIL', $supplier['email']],
            ];
            $quotationHistory = [
                ['name' => 'July Region VII batch', 'reference' => '#2026-0090', 'status' => 'Quoted'],
                ['name' => 'Lab reagents – PGH', 'reference' => '#2026-0089', 'status' => 'Awaiting quote'],
            ];
        @endphp

        {{-- Type label --}}
        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-3">SUPPLIER PROFILE</p>

        {{-- Header card --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <h2 class="text-xl font-bold text-gray-900">{{ $supplier['name'] }}</h2>
            <p class="text-sm text-gray-500">{{ $supplier['address'] }}</p>
        </div>

        {{-- Contact details --}}
        <div class="mt-6">
            <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-3">CONTACT DETAILS</p>
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm divide-y divide-gray-100">
                @foreach($contactRows as $row)
                    <div class="flex items-center justify-between px-5 py-3">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $row[0] }}</p>
                        <p class="text-sm font-medium text-gray-900">{{ $row[1] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    {{-- Quotation history --}}
        <div class="mt-6">
            <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-3">QUOTATION HISTORY</p>
            <div class="space-y-3">
                @foreach($quotationHistory as $quotation)
                    @php
                        $quotationPill = $quotation['status'] === 'Quoted' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600';
                    @endphp
                    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm font-bold text-gray-900">{{ $quotation['name'] }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $quotation['reference'] }}</p>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $quotationPill }}">{{ $quotation['status'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3 mt-8">
            <form method="POST" action="{{ route('entities.destroy', ['type' => 'supplier', 'id' => $id]) }}">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="flex items-center gap-2 px-4 py-2 rounded-md border border-red-300 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete Supplier
                </button>
            </form>
            <a href="{{ route('entities.edit', ['type' => 'supplier', 'id' => $id]) }}"
               class="flex items-center gap-2 px-4 py-2 rounded-md bg-[#2a7a94] text-white text-sm font-medium hover:bg-[#236b80] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit Supplier
            </a>
        </div>
    @endif

    {{-- Bottom spacer --}}
    <div class="pb-4"></div>

@endsection
