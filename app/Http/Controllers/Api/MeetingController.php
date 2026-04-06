<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    // GET /otherfun/meetings
    public function index(Request $request)
    {
        $limit     = (int) ($request->query('limit', 10));
        $page      = (int) ($request->query('page', 1));
        $search    = $request->query('search');
        $fromDate  = $request->query('fromDate');
        $toDate    = $request->query('toDate');
        $sortField = $request->query('sortField', 'created_at');
        $sortOrder = strtolower($request->query('sortOrder', 'desc')) === 'asc' ? 'asc' : 'desc';

        // Map Postman sortField names to DB columns
        $columnMap = [
            'dateTime'   => 'date',
            'date'       => 'date',
            'title'      => 'title',
            'created_at' => 'created_at',
        ];
        $sortColumn = $columnMap[$sortField] ?? 'created_at';

        $query = Meeting::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($fromDate) {
            $query->whereDate('date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('date', '<=', $toDate);
        }

        $total    = $query->count();
        $meetings = $query->orderBy($sortColumn, $sortOrder)
                          ->skip(($page - 1) * $limit)
                          ->take($limit)
                          ->get();

        return response()->json([
            'success' => true,
            'data'    => $meetings,
            'meta'    => [
                'total'        => $total,
                'per_page'     => $limit,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $limit),
            ],
        ]);
    }

    // POST /otherfun/meetings
    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'date'        => 'nullable|date_format:Y-m-d',
            'time'        => 'nullable|date_format:H:i',
            'createdBy'   => 'nullable|string',
        ]);

        $meeting = Meeting::create([
            'title'       => $request->title,
            'description' => $request->description,
            'date'        => $request->date,
            'time'        => $request->time,
            'created_by'  => $request->createdBy,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Meeting created successfully',
            'data'    => $meeting,
        ], 201);
    }

    // GET /otherfun/meetings/{id}
    public function show($id)
    {
        $meeting = Meeting::find($id);

        if (!$meeting) {
            return response()->json(['success' => false, 'message' => 'Meeting not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $meeting]);
    }
}
