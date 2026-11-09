@php
    // Small helper for the avatar initials circle. The function_exists()
    // guard prevents redeclaration when the full page and live-search partial
    // render in the same process.
    if (!function_exists('initialsFromName')) {
        function initialsFromName($name)
        {
            $initials = '';
            foreach (preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) as $part) {
                $initials .= strtoupper(substr($part, 0, 1));
                if (strlen($initials) >= 2) break;
            }
            return $initials;
        }
    }

    $rolePill = $rolePill ?? function ($role) {
        return match ($role) {
            'Administrator' => 'bg-blue-100 text-blue-700',
            'Procurement Officer' => 'bg-teal-100 text-teal-700',
            'Viewer' => 'bg-purple-100 text-purple-700',
            default => 'bg-gray-100 text-gray-700',
        };
    };
@endphp

@forelse ($users as $user)
    @php
        $statusClass = $user->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600';
    @endphp
    <tr class="hover:bg-gray-50 transition-colors cursor-pointer"
        data-href="{{ route('settings.privileges.edit', $user->id) }}">
        <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-9 h-9 rounded-full bg-[#2a7a94]/10 text-[#2a7a94] text-xs font-semibold shrink-0">
                    {{ initialsFromName($user->name) }}
                </div>
                <div>
                    <p class="text-sm font-bold text-gray-900">{{ $user->name }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $user->email }}</p>
                </div>
            </div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $rolePill($user->role?->name) }}">{{ $user->role?->name ?? 'Unassigned' }}</span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $statusClass }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->created_at?->format('M j, Y') ?? '—' }}</td>
        <td class="px-6 py-4 text-center whitespace-nowrap">
            <div class="relative inline-block">
                <button type="button" class="dropdown-trigger p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors" title="Actions">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/>
                    </svg>
                </button>
                <div class="dropdown-menu hidden absolute right-0 top-full mt-2 w-40 bg-white rounded-lg border border-gray-200 shadow-lg z-10 py-1">
                    <a href="{{ route('settings.privileges.edit', $user->id) }}"
                       class="dropdown-action w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50" title="Edit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit
                    </a>
                    <form method="POST" action="{{ route('settings.privileges.toggle-status', $user->id) }}">
                        @csrf
                        <button type="submit" class="dropdown-action w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                title="{{ $user->is_active ? 'Deactivate' : 'Activate' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                            {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('settings.privileges.destroy', $user->id) }}"
                          onsubmit="return confirm('Delete this user account? This cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="dropdown-action w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50" title="Delete">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </td>
    </tr>
@empty
    <tr class="no-results-row">
        <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">
            {{ ! empty($search) ? 'No users match your search.' : 'No user accounts yet.' }}
        </td>
    </tr>
@endforelse
