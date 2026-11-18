{{-- Published override of Laravel's Tailwind paginator, themed to the app.
     Every {{ $paginator->links() }} in the application renders with these
     classes automatically. --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex flex-wrap items-center justify-between gap-4">
        <div class="text-xs text-gray-500">
            @if ($paginator->firstItem())
                <span>Showing</span>
                <span class="font-semibold text-gray-700">{{ $paginator->firstItem() }}</span>
                <span>to</span>
                <span class="font-semibold text-gray-700">{{ $paginator->lastItem() }}</span>
                <span>of</span>
                <span class="font-semibold text-gray-700">{{ $paginator->total() }}</span>
                <span>results</span>
            @else
                <span>{{ $paginator->count() }} results</span>
            @endif
        </div>

        <div class="inline-flex items-center gap-1">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="Previous page"
                      class="cursor-default rounded-lg border border-gray-200 bg-white p-2 text-gray-300">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 19.5 8.25 12l7.5-7.5"/>
                    </svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="rounded-lg border border-gray-200 bg-white p-2 text-gray-600 transition-colors hover:border-[#2a7a94] hover:text-[#2a7a94]"
                   aria-label="Previous page">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 19.5 8.25 12l7.5-7.5"/>
                    </svg>
                </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span aria-hidden="true"
                          class="cursor-default select-none rounded-lg border border-transparent px-2 py-1.5 text-sm text-gray-400">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"
                                  class="rounded-lg border border-[#2a7a94] bg-[#2a7a94] px-3 py-1.5 text-sm font-semibold text-white shadow-sm">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}"
                               class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition-colors hover:border-[#2a7a94] hover:text-[#2a7a94]"
                               aria-label="Go to page {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="rounded-lg border border-gray-200 bg-white p-2 text-gray-600 transition-colors hover:border-[#2a7a94] hover:text-[#2a7a94]"
                   aria-label="Next page">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                    </svg>
                </a>
            @else
                <span aria-disabled="true" aria-label="Next page"
                      class="cursor-default rounded-lg border border-gray-200 bg-white p-2 text-gray-300">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
