<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MeetingMinute;
use App\Helpers\AuthHelper;

class MeetingMinuteController extends Controller
{
    /**
     * Get meeting minutes for the user's building.
     *
     * POST get-meeting-minutes
     * Body: { "count": 10, "page": 1 }  (optional pagination)
     */
    public function getMeetingMinutes(Request $request)
    {
        $request->validate([
            'count' => 'nullable|integer|min:1',
            'page'  => 'nullable|integer|min:1',
        ]);

        $flat = AuthHelper::flat();

        $query = MeetingMinute::where('building_id', $flat->building_id)
            ->orderBy('created_at', 'desc');

        $count = $request->input('count');
        $page  = $request->input('page', 1);

        if ($count) {
            $paginated = $query->paginate($count, ['*'], 'page', $page);

            return response()->json([
                'meeting_minutes' => $this->formatMinutes($paginated->items()),
                'total'           => $paginated->total(),
                'current_page'    => $paginated->currentPage(),
                'last_page'       => $paginated->lastPage(),
            ], 200);
        }

        $minutes = $query->get();

        return response()->json([
            'meeting_minutes' => $this->formatMinutes($minutes),
            'total'           => $minutes->count(),
        ], 200);
    }

    private function formatMinutes($items): array
    {
        return collect($items)->map(function (MeetingMinute $m) {
            return [
                'id'              => $m->id,
                'title'           => $m->title,
                'description'     => $m->description,
                'created_by_name' => $m->creator ? $m->creator->name : null,
                'created_by_role' => $m->created_by_role,
                'date'            => $m->created_at->format('d M Y'),
                'time'            => $m->created_at->format('h:i A'),
                'datetime'        => $m->created_at->toDateTimeString(),
            ];
        })->toArray();
    }
}
