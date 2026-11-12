<?php

namespace App\Support;

use App\Models\Project;
use Illuminate\Support\Collection;

/**
 * Builds the "delivery deadlines approaching" notifications shown in the
 * top bar's bell icon.
 *
 * The rule is deliberately simple and entirely data-driven:
 *
 *   - Projects without a delivery due date are ignored.
 *   - Projects that are already fully fulfilled are ignored.
 *   - Red   – overdue, or due within 2 days
 *   - Amber – due within 7 days (or within 14 days with nothing fulfilled yet)
 *
 * Each notification therefore reflects both the delivery due date and the
 * project's fulfillment progress.
 */
class DeliveryNotifications
{
    /**
     * The projects that currently warrant a notification, closest deadline
     * first.
     *
     * @return Collection<int, array{project_id: int, title: string, entity: string, due: string, days_left: int, fulfilled_items: int, total_items: int, progress_percent: int, severity: string, label: string, href: string}>
     */
    public static function projects(): Collection
    {
        $projects = Project::query()
            ->with('procuringEntity')
            ->withCount([
                'items',
                'items as fulfilled_items_count' => fn ($query) => $query->where(function ($query) {
                    $query->where('fulfillment_status', 'fulfilled')
                        ->orWhereColumn('in_stock', '>=', 'quantity');
                }),
            ])
            ->whereNotNull('delivery_due_date')
            ->get();

        return $projects
            ->map(fn (Project $project) => self::notification($project))
            ->filter(fn (?array $notification) => $notification !== null)
            ->sortBy(fn (array $notification) => [$notification['days_left'], $notification['progress_percent']])
            ->take(8)
            ->values();
    }

    /**
     * The count of open delivery notifications.
     */
    public static function count(): int
    {
        return self::projects()->count();
    }

    /**
     * Build one notification, or null when the project is already complete
     * or too far out to matter.
     *
     * @return array{project_id: int, title: string, entity: string, due: string, days_left: int, fulfilled_items: int, total_items: int, progress_percent: int, severity: string, label: string, href: string}|null
     */
    private static function notification(Project $project): ?array
    {
        $totalItems = (int) ($project->items_count ?? 0);
        $fulfilledItems = (int) ($project->fulfilled_items_count ?? 0);
        $progressPercent = $totalItems > 0 ? (int) floor($fulfilledItems / $totalItems * 100) : 0;

        if ($totalItems > 0 && $progressPercent >= 100) {
            // Nothing to complete — this project can no longer produce a
            // delivery risk.
            return null;
        }

        $dueDate = $project->delivery_due_date->copy()->startOfDay();
        $daysLeft = (int) now()->startOfDay()->diffInDays($dueDate, false);

        $severity = match (true) {
            $daysLeft < 0 => 'red',
            $daysLeft <= 2 => 'red',
            $progressPercent === 0 && $daysLeft <= 14 => 'amber',
            $daysLeft <= 7 => 'amber',
            default => null,
        };

        if ($severity === null) {
            return null;
        }

        $label = $daysLeft < 0
            ? 'Delivery overdue by '.abs($daysLeft).' '.(abs($daysLeft) === 1 ? 'day' : 'days')
            : 'Delivery due in '.$daysLeft.' '.($daysLeft === 1 ? 'day' : 'days');

        return [
            'project_id' => $project->id,
            'title' => $project->title,
            'entity' => $project->procuringEntity?->name ?? '—',
            'due' => $dueDate->format('M j, Y'),
            'days_left' => $daysLeft,
            'fulfilled_items' => $fulfilledItems,
            'total_items' => $totalItems,
            'progress_percent' => $progressPercent,
            'severity' => $severity,
            'label' => $label,
            'href' => route('projects.show', ['project' => $project->id]),
        ];
    }
}
