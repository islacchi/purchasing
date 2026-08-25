@extends('layouts.app')

@section('page-title', 'PREPLIST')
@section('title', 'Preplist - Purchasing')

@section('content')
    {{-- Search and actions bar --}}
    <div class="mb-6 flex items-center justify-between gap-4">
        <div class="flex items-center gap-4 flex-1">
            <div class="max-w-xl w-full">
                <input type="text" placeholder="Search by prep list name / created by..."
                       class="w-full px-4 py-2 rounded-lg bg-white text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300 border border-gray-200">
            </div>

            <button class="p-2 bg-[#2a7a94] text-white rounded hover:bg-[#236b80] transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>
        </div>

        {{-- + Compile prep list button (top right, below the nav bar) --}}
        <button class="flex items-center gap-2 px-4 py-2 bg-[#2a7a94] text-white rounded-lg hover:bg-[#236b80] transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span class="font-medium">Compile prep list</span>
        </button>
    </div>
    {{-- Hard-coded prep list data for frontend development --}}
    @php
        $preplists = [
            [
                'name'        => 'Q3 Medical Supplies',
                'created_by'  => 'Juan Dela Cruz',
                'date_created'=> '2026-07-15',
                'projects'    => ['DOH Region VII', 'PGH Manila'],
                'items'       => 24,
            ],
            [
                'name'        => 'Laboratory Reagents Batch 1',
                'created_by'  => 'Maria Santos',
                'date_created'=> '2026-07-12',
                'projects'    => ['PGH Manila'],
                'items'       => 58,
            ],
            [
                'name'        => 'Office Equipment FY2026',
                'created_by'  => 'Carlos Reyes',
                'date_created'=> '2026-07-08',
                'projects'    => ['DOH Region VII', 'Dept of Finance'],
                'items'       => 12,
            ],
            [
                'name'        => 'Pharma Supplies Q4',
                'created_by'  => 'Juan Dela Cruz',
                'date_created'=> '2026-07-03',
                'projects'    => ['DOH Region VII'],
                'items'       => 40,
            ],
            [
                'name'        => 'IT Hardware Refresh',
                'created_by'  => 'Ana Cruz',
                'date_created'=> '2026-06-28',
                'projects'    => ['PGH Manila', 'Dept of Finance'],
                'items'       => 17,
            ],
        ];
    @endphp

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Created by</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date Created</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Projects</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Items</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach($preplists as $preplist)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $preplist['name'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                {{ $preplist['created_by'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ \Carbon\Carbon::parse($preplist['date_created'])->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <div class="relative group inline-block">
                                    <span class="cursor-pointer font-medium text-gray-900 underline decoration-dotted decoration-gray-300 underline-offset-2">
                                        {{ count($preplist['projects']) }}
                                    </span>
                                    <div class="hidden group-hover:block pointer-events-none absolute left-0 bottom-full mb-2 w-max max-w-xs bg-gray-900 text-white text-xs rounded-md shadow-lg px-3 py-2 z-20">
                                        @foreach($preplist['projects'] as $project)
                                            <div class="whitespace-nowrap py-0.5">{{ $project }}</div>
                                        @endforeach
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-center">
                                {{ $preplist['items'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="relative inline-block text-left">
                                    <button type="button"
                                            class="dropdown-trigger p-2 text-gray-400 hover:text-[#2a7a94] hover:bg-gray-100 rounded-lg transition-colors"
                                            title="Actions">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 8a2 2 0 100-4 2 2 0 000 4zm0 6a2 2 0 100-4 2 2 0 000 4zm0 6a2 2 0 100-4 2 2 0 000 4z"/>
                                        </svg>
                                    </button>
                                    <div class="dropdown-menu hidden absolute right-0 mt-2 w-36 bg-white rounded-lg shadow-lg border border-gray-200 z-10 py-1">
                                        <button class="dropdown-action w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            <span>Edit</span>
                                        </button>
                                        <button class="dropdown-action w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            <span>Delete</span>
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Three-dots action dropdown toggling
        document.querySelectorAll('.dropdown-trigger').forEach(function (trigger) {
            trigger.addEventListener('click', function (e) {
                e.stopPropagation();
                const menu = this.nextElementSibling;
                const isOpen = !menu.classList.contains('hidden');

                // Close all open dropdowns first
                document.querySelectorAll('.dropdown-menu').forEach(function (m) {
                    m.classList.add('hidden');
                });

                if (!isOpen) {
                    menu.classList.remove('hidden');
                }
            });
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.dropdown-trigger')) {
                document.querySelectorAll('.dropdown-menu').forEach(function (m) {
                    m.classList.add('hidden');
                });
            }
        });

        // Prevent dropdown menu clicks from closing before action runs
        document.querySelectorAll('.dropdown-menu').forEach(function (menu) {
            menu.addEventListener('click', function (e) {
                e.stopPropagation();
            });
        });
    </script>
@endsection