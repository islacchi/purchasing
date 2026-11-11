<?php

namespace App\Http\Controllers;

use App\Models\PreplistItem;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Support\QuotationWorkbookParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class QuotationController extends Controller
{
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
                $total = $quotation->preplist->items->count();
                $quoted = $quotation->items
                    ->filter(fn ($item) => $item->unit_price !== null)
                    ->count();

                return [
                    'id' => $quotation->id,
                    'prep_list' => $quotation->preplist->name,
                    'ref' => $quotation->preplist->reference_no,
                    'supplier' => $quotation->supplier->name,
                    'quoted' => $quoted,
                    'total' => $total,
                    'status' => $quoted > 0 ? 'Quoted' : 'Awaiting quote',
                ];
            });

        return view('Quotation.index', compact('quotations'));
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

        $items = $quotation->preplist->items
            ->map(function (PreplistItem $preplistItem) {
                $unit = $preplistItem->item?->unit ?? '';
                $requiredShelfLife = (int) $preplistItem->min_shelf_life_months;
                $requested = $preplistItem->sources->sum('qty');
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
                        'qty' => $source->qty,
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
            ->values();

        $quotationItems = $quotation->items->keyBy('preplist_item_id');

        return view('Quotation.show', [
            'quotation' => $quotation,
            'items' => $items,
            'quotationItems' => $quotationItems,
        ]);
    }

    /**
     * Store the supplier's completed quotation workbook and read the
     * unit prices into the quotation's line items.
     */
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

            $updates[] = [
                'preplist_item_id' => $preplistItem->id,
                'min_remaining_months' => (int) $preplistItem->min_shelf_life_months,
                'brand' => $row['brand'],
                'expiry' => $row['expiry'],
                'unit_price' => $row['unit_price'],
            ];
        }

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
        });

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
        });

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

        $quotation->delete();

        return redirect()
            ->route('quotation')
            ->with('status', 'Quotation deleted successfully.');
    }
}
