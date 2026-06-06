<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MeetingMinute;
use App\Helpers\AuthHelper;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MeetingController extends Controller
{
    // GET /meetings
    public function index(Request $request)
    {
        try {
            $limit     = (int) ($request->query('limit', 10));
            $page      = (int) ($request->query('page', 1));
            $search    = $request->query('search');
            $fromDate  = $request->query('fromDate');
            $toDate    = $request->query('toDate');
            $sortField = $request->query('sortField', 'datetime');
            $sortOrder = strtolower($request->query('sortOrder', 'desc')) === 'asc' ? 'asc' : 'desc';

            // Map sortField names to DB columns
            $columnMap = [
                'dateTime'   => 'datetime',
                'date'       => 'datetime',
                'title'      => 'title',
                'created_at' => 'created_at',
            ];
            $sortColumn = $columnMap[$sortField] ?? 'datetime';

            // Scope to the authenticated user's building so a user never sees
            // other buildings' meeting minutes.
            $flat = AuthHelper::flat();
            if (! $flat) {
                return response()->json([
                    'success' => false,
                    'message' => 'No flat/building context found.',
                ], 422);
            }

            // Build query - MeetingMinute model
            $query = MeetingMinute::where('building_id', $flat->building_id);

            // Apply filters if provided
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($fromDate) {
                $query->whereDate('datetime', '>=', $fromDate);
            }

            if ($toDate) {
                $query->whereDate('datetime', '<=', $toDate);
            }

            // Get total count
            $total = $query->count();

            // Get data with sorting and pagination
            $minutes = $query->orderBy($sortColumn, $sortOrder)
                             ->skip(($page - 1) * $limit)
                             ->take($limit)
                             ->get();

            $totalPages = $limit > 0 ? (int) ceil($total / $limit) : 1;

            return response()->json([
                'success'     => true,
                'data'        => $minutes,
                'page'        => $page,
                'limit'       => $limit,
                'total'       => $total,
                'totalPages'  => $totalPages,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching meetings',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // POST /meetings
    public function store(Request $request)
    {
        $request->validate([
            'title'           => 'required|string|max:255',
            'description'     => 'required|string',
            'datetime'        => 'nullable|date_format:Y-m-d\TH:i',
            'created_by'      => 'required|integer',
            'created_by_role' => 'nullable|string',
        ]);

        try {
            $flat = AuthHelper::flat();
            if (! $flat) {
                return response()->json([
                    'success' => false,
                    'message' => 'No flat/building context found.',
                ], 422);
            }

            // Set default datetime to current if not provided
            $datetime = $request->datetime ?? Carbon::now()->format('Y-m-d\TH:i:s');

            $minute = MeetingMinute::create([
                'building_id'     => $flat->building_id,
                'title'           => $request->title,
                'description'     => $request->description,
                'datetime'        => $datetime,
                'created_by'      => $request->created_by,
                'created_by_role' => $request->created_by_role ?? 'User',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Meeting minute created successfully',
                'data'    => $minute,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating meeting minute',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // GET /meetings/{id}
    public function show($id)
    {
        try {
            $flat = AuthHelper::flat();
            $minute = MeetingMinute::where('id', $id)
                ->when($flat, fn($q) => $q->where('building_id', $flat->building_id))
                ->first();

            if (!$minute) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meeting minute not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => $minute
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching meeting minute',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
