<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Noticeboard;
use Carbon\Carbon;

class DiagnoseNoticeboards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Usage:
     *  php artisan noticeboard:diagnose --building=1 --since=60 --fix
     */
    protected $signature = 'noticeboard:diagnose {--building=} {--since=60} {--fix}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose noticeboard issues: missing pivot rows, is_all inconsistent flags, immediate notifications.';

    public function handle()
    {
        $buildingId = $this->option('building');
        $minutes = (int)$this->option('since');
        $doFix = $this->option('fix');

        $this->info("Looking for notices in the last {$minutes} minutes" . ($buildingId ? " for building {$buildingId}" : ''));

        $since = Carbon::now()->subMinutes($minutes);

        $query = Noticeboard::where('created_at', '>=', $since);
        if ($buildingId) {
            $query->where('building_id', $buildingId);
        }

        $notices = $query->with('blocks', 'building')->get();

        if ($notices->isEmpty()) {
            $this->info('No recent notices found.');
            return 0;
        }

        $this->table(
            ['ID', 'Title', 'Building', 'Blocks Count', 'Block IDs (pivot)', 'Block IDs (raw)', 'is_all DB', 'from_notified_at', 'created_at', 'Issues'],
            $notices->map(function ($n) {
                $pivotIds = $n->blocks ? $n->blocks->pluck('id')->implode(',') : '';
                $rawIds = '';
                if (isset($n->block_ids)) {
                    try { $raw = json_decode($n->block_ids, true); if (is_array($raw)) $rawIds = implode(',', $raw); } catch (\Exception $e) {}
                }
                $issues = [];
                // Check pivot row count vs raw selection
                if (empty($pivotIds) && !empty($rawIds)) {
                    $issues[] = 'Raw block_ids present, pivot empty';
                }
                // Check building_id presence
                if (empty($n->building_id)) {
                    $issues[] = 'building_id is empty';
                }
                // Check is_all consistency (if column exists)
                if (in_array('is_all_blocks', $n->getFillable())) {
                    if ($n->is_all_blocks && empty($pivotIds)) {
                        $issues[] = 'is_all true but pivot empty';
                    }
                }
                // Check for immediate notification (from_notified_at set too near created_at)
                if ($n->from_notified_at) {
                    $created = Carbon::parse($n->created_at);
                    $notified = Carbon::parse($n->from_notified_at);
                    if ($notified->diffInSeconds($created) < 10) {
                        $issues[] = 'from_notified_at set close to created_at (possible immediate send)';
                    }
                }
                return [
                    $n->id,
                    $n->title,
                    optional($n->building)->name,
                    $n->blocks ? $n->blocks->count() : 0,
                    $pivotIds,
                    $rawIds,
                    isset($n->is_all_blocks) ? ($n->is_all_blocks ? '1' : '0') : '-',
                    $n->from_notified_at ? $n->from_notified_at->toDateTimeString() : '-',
                    $n->created_at->toDateTimeString(),
                    implode('; ', $issues),
                ];
            })->toArray()
        );

        if ($doFix) {
            $this->warn('Fix mode enabled. This will attempt non-destructive fixes (sync pivots from raw block_ids if present).');
            foreach ($notices as $n) {
                $rawIds = [];
                if (isset($n->block_ids) && !empty($n->block_ids)) {
                    try { $raw = json_decode($n->block_ids, true); if (is_array($raw)) $rawIds = $raw; } catch (\Exception $e) {}
                }
                if (empty($rawIds) || (is_array($rawIds) && count($rawIds) == 0)) {
                    continue; // nothing to do
                }
                $blockIds = $rawIds;
                if (in_array('all', $rawIds)) {
                    $blockIds = $n->building->blocks()->where('status','Active')->pluck('id')->toArray();
                }
                if (is_array($blockIds) && count($blockIds) > 0) {
                    $n->blocks()->sync($blockIds);
                    $this->info("Synced pivot for notice {$n->id} (blocks: " . implode(',', $blockIds) . ")");
                }
            }
        }
        return 0;
    }
}
