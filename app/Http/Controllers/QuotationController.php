<?php

namespace App\Http\Controllers;

use App\Models\PreplistItem;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Support\ActivityLogger;
use App\Support\QuotationWorkbookParser;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class QuotationController extends Controller
{
    /**
     * Convert a unique-violation into a validation error for the user.
     */
    private function throwIfUniqueViolation(QueryException $exception): never
    {
        if (str_contains($exception->getMessage(), 'UNIQUE') || str_contains($exception->getMessage(), 'unique')) {
            throw ValidationException::withMessages([
                'document' => 'This quotation was already submitted in another request. Please refresh the page.',
            ]);
        }

        throw $exception;
    }

    /**
     * Show the quotation tracker using real database records.
     */
    public function index()
    {
        $quotations = Quotation::query()
            ->with(['preplist.items', 'supplier', 'items'])
            ->orderByDesc('id')
            ->get()
            ->map(function (Quotation $quotation) {
                $total = $quotation->preplist?->items->count() ?? 0;
                $quoted = $quotation->items
                    ->filter(fn ($item) => $item->unit_price !== null)
                    ->count();

                return [
                    'id' => $quotation->id,
                    'preplist_id' => (int) $quotation->preplist_id,
                    'prep_list' => $quotation->preplist?->name ?? 'Prep list removed',
                    'ref' => $quotation->preplist?->reference_no ?? 'Prep list removed',
                    'supplier' => $quotation->supplier?->name ?? 'Supplier removed',
                    'rfq_number' => $quotation->rfq_number,
                    'quoted' => $quoted,
                    'total' => $total,
                    'status' => $quoted > 0 ? 'Quoted' : 'Awaiting quote',
                ];
            })->values();

        // Group the RFQ tracker by prep list so the quotations sent to
        // different suppliers against the same batch stay together.
        $preplistGroups = [];

        foreach ($quotations as $quotation) {
            $preplistGroups[$quotation['preplist_id']][] = $quotation;
        }

        $groups = collect($preplistGroups)
            ->map(function (array $groupQuotations, $preplistId) {
                return [
                    'preplist_id' => (int) $preplistId,
                    'name' => $groupQuotations[0]['prep_list'],
                    'ref' => $groupQuotations[0]['ref'],
                    'quotations' => $groupQuotations,
                ];
            })
            ->sortByDesc(fn (array $group) => max(array_column($group['quotations'], 'id')))
            ->values();

        return view('Quotation.index', [
            'preplistGroups' => $groups,
        ]);
    }

    /**
     * Show one quotation and the per-item quote breakdown.
     */
    public function show(Quotation $quotation)
    {
        $quotation->load([
            'preplist.items.item',
            'preplist.items.sources.projectItem.project',
            'supplier',
            'items.preplistItem',
        ]);

        $preplist = $quotation->preplist;
        $items = $preplist?->items
            ? $preplist->items
                ->map(function (PreplistItem $preplistItem) {
                    $unit = $preplistItem->item?->unit ?? '';
                    $requiredShelfLife = (int) $preplistItem->min_shelf_life_months;
                    $requested = $preplistItem->remainingQuantity();
                    $allocations = $preplistItem->sources->map(function ($source) use ($unit, $requiredShelfLife) {
                        $project = $source->projectItem?->project;

                        // Fall back to the award date plus the project's delivery
                        // period when the due date was never persisted.
                        $deliveryDueDate = $project?->delivery_due_date
                            ?? ($project?->date_awarded && $project?->delivery_days
                                ? $project->date_awarded->copy()->addDays($project->delivery_days)
                                : null);

                        return [
                            'project' => $project?->title ?? 'Project removed',
                            'reference' => $project?->reference_no ?? '—',
                            'qty' => $source->projectItem?->remainingQuantity() ?? 0,
                            'unit' => $unit,
                            'delivery_due_date' => $deliveryDueDate?->toDateString(),
                            'required_expiry_date' => $deliveryDueDate
                                ? $deliveryDueDate->copy()->addMonths($requiredShelfLife)->toDateString()
                                : null,
                        ];
                    });

                    return [
                        'key' => (string) $preplistItem->id,
                        'name' => $preplistItem->item?->name ?? 'Item',
                        'requested' => (int) $requested,
                        'unit' => $unit,
                        'min_remaining_months' => $requiredShelfLife,
                        'allocations' => $allocations,
                    ];
                })
                ->values()
            : collect();

        $quotationItems = $quotation->items->keyBy('preplist_item_id');

        // Why removing the supplier's quotation would be refused — shown by
        // the Remove quotation modal when non-null.
        $removeBlockedReason = session('documentRemoveBlocked') ?? $this->blockedRemoveReason($quotation);

        return view('Quotation.show', [
            'quotation' => $quotation,
            'items' => $items,
            'quotationItems' => $quotationItems,
            'removeBlockedReason' => $removeBlockedReason,
        ]);
    }

    /**
     * The reason removing the supplier's quotation document would be refused,
     * or null when removal is allowed.
     *
     * Removing the document deletes the quoted line items together with it.
     * Once a purchase order was created from this quotation, its lines
     * reference those items, so deleting them violates a foreign key.
     */
    private function blockedRemoveReason(Quotation $quotation): ?string
    {
        $itemIds = $quotation->items()->pluck('id');

        if ($itemIds->isEmpty()) {
            return null;
        }

        $poNumbers = PurchaseOrder::query()
            ->whereHas('items', fn ($query) => $query->whereIn('quotation_item_id', $itemIds))
            ->pluck('po_number')
            ->unique()
            ->values();

        if ($poNumbers->isEmpty()) {
            return null;
        }

        return 'A purchase order was already created from this quotation ('
            .$poNumbers->join(', ')
            .'). Its quoted prices and expiry data are already in use, so the quotation file cannot be removed until the purchase order is deleted first.';
    }

    public function storeDocument(Request $request, Quotation $quotation)
    {
        $request->validate([
            'document' => ['required', 'file', 'extensions:xlsx', 'max:10240'],
        ], [
            'document.required' => 'Choose the supplier’s completed quotation file to upload.',
            'document.extensions' => 'Upload the completed quotation as an .xlsx file.',
        ]);

        $file = $request->file('document');

        try {
            $rows = QuotationWorkbookParser::parse($file->getRealPath());
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'document' => $exception->getMessage(),
            ]);
        }

        $quotation->load(['preplist.items.item']);

        if ($quotation->preplist === null) {
            throw ValidationException::withMessages([
                'document' => 'This quotation’s prep list no longer exists; the file cannot be processed.',
            ]);
        }

        $itemsByName = $quotation->preplist->items
            ->keyBy(fn (PreplistItem $preplistItem) => mb_strtolower(trim($preplistItem->item?->name ?? 'Item')));

        $updates = [];

        foreach ($rows as $row) {
            $key = mb_strtolower(trim($row['description']));
            $preplistItem = $itemsByName->get($key);

            if ($preplistItem === null) {
                throw ValidationException::withMessages([
                    'document' => 'The uploaded file does not match this prep list: "'.$row['description'].'".',
                ]);
            }

            if ($row['unit_price'] !== null && $row['unit_price'] <= 0) {
                throw ValidationException::withMessages([
                    'document' => 'The unit price for "'.$row['description'].'" must be greater than zero.',
                ]);
            }

            $updates[] = [
                'preplist_item_id' => $preplistItem->id,
                'min_remaining_months' => (int) $preplistItem->min_shelf_life_months,
                'brand' => $row['brand'],
                'expiry' => $row['expiry'],
                'unit_price' => $row['unit_price'],
            ];
        }

        try {
            DB::transaction(function () use ($quotation, $updates) {
                foreach ($updates as $data) {
                    // A blank price means the supplier did not quote that item;
                    // make sure no stale "quoted" row remains for it.
                    if ($data['unit_price'] === null) {
                        QuotationItem::query()
                            ->where('quotation_id', $quotation->id)
                            ->where('preplist_item_id', $data['preplist_item_id'])
                            ->delete();

                        continue;
                    }

                    QuotationItem::query()->updateOrCreate(
                        [
                            'quotation_id' => $quotation->id,
                            'preplist_item_id' => $data['preplist_item_id'],
                        ],
                        [
                            'min_remaining_months' => $data['min_remaining_months'],
                            'brand' => $data['brand'],
                            'expiry' => $data['expiry'],
                            'unit_price' => $data['unit_price'],
                            'quoted_at' => now(),
                        ],
                    );
                }

                ActivityLogger::log('Edited', 'Quotation', $quotation->rfq_number ?: 'Quotation #'.$quotation->id, $quotation);
            });
        } catch (QueryException $exception) {
            $this->throwIfUniqueViolation($exception);
        }

        $path = 'quotations/'.($quotation->rfq_number ?: 'quotation-'.$quotation->id).'.xlsx';
        $oldPath = $quotation->document_path;

        Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));

        $quotation->update([
            'document_path' => $path,
            'document_name' => $file->getClientOriginalName(),
            'document_size' => $file->getSize(),
        ]);

        if ($oldPath && $oldPath !== $path) {
            Storage::disk('local')->delete($oldPath);
        }

        return redirect()
            ->route('quotation.show', ['quotation' => $quotation->id])
            ->with('status', 'Quotation submitted successfully.');
    }

    /**
     * Download the supplier's quotation document.
     */
    public function download(Quotation $quotation)
    {
        abort_unless(
            $quotation->document_path && Storage::disk('local')->exists($quotation->document_path),
            404,
        );

        return Storage::disk('local')->download(
            $quotation->document_path,
            $quotation->document_name ?: 'quotation.xlsx',
        );
    }

    /**
     * Remove the supplier's uploaded quotation document and clear the
     * quoted prices so the quotation returns to "Awaiting quote".
     */
    public function destroyDocument(Quotation $quotation)
    {
        // Refuse to remove the supplier's quotation when its prices are
        // already referenced by a purchase order — that is the FK the
        // delete would otherwise hit with a 500.
        $blocked = $this->blockedRemoveReason($quotation);

        if ($blocked !== null) {
            return redirect()
                ->route('quotation.show', ['quotation' => $quotation->id])
                ->with('documentRemoveBlocked', $blocked);
        }

        try {
            DB::transaction(function () use ($quotation) {
                if ($quotation->document_path) {
                    Storage::disk('local')->delete($quotation->document_path);
                }

                $quotation->items()->delete();

                $quotation->update([
                    'document_path' => null,
                    'document_name' => null,
                    'document_size' => null,
                ]);

                ActivityLogger::log('Deleted', 'Quotation', ($quotation->rfq_number ?: 'Quotation #'.$quotation->id).' (document)', $quotation);
            });
        } catch (QueryException $exception) {
            // Defensive net for an unexpected reference we did not predict —
            // surface the same friendly reason instead of a 500.
            if (str_contains($exception->getMessage(), '23000') || str_contains($exception->getMessage(), 'Integrity constraint')) {
                return redirect()
                    ->route('quotation.show', ['quotation' => $quotation->id])
                    ->with('documentRemoveBlocked', $this->blockedRemoveReason($quotation)
                        ?? 'This quotation is already in use by another record and cannot be removed.');
            }

            throw $exception;
        }

        return redirect()
            ->route('quotation.show', ['quotation' => $quotation->id])
            ->with('status', 'Quotation removed. The supplier can now submit a new file.');
    }

    /**
     * Delete a quotation record. Only quotations that are still awaiting a
     * quote can be deleted; quoted entries must have their uploaded file
     * removed first.
     */
    public function destroy(Quotation $quotation)
    {
        if ($quotation->quotedItems()->exists()) {
            return redirect()
                ->route('quotation')
                ->withErrors([
                    'quotation' => 'This quotation has already been answered. Remove the supplier’s quotation first before deleting the entry.',
                ]);
        }

        if ($quotation->document_path) {
            Storage::disk('local')->delete($quotation->document_path);
        }

        DB::transaction(function () use ($quotation) {
            $quotation->delete();

            ActivityLogger::log('Deleted', 'Quotation', $quotation->rfq_number ?: 'Quotation #'.$quotation->id, $quotation);
        });

        return redirect()
            ->route('quotation')
            ->with('status', 'Quotation deleted successfully.');
    }
}
