<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Poll;
use App\Models\PollQuestion;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Helpers\AuthHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PollController extends Controller
{
    // ─────────────────────────────────────────────────────────────
    // GET POLLS (active / expiring_soon for user's building)
    // ─────────────────────────────────────────────────────────────
    public function getPolls(Request $request)
    {
        $user = Auth::user();
        $flat = AuthHelper::flat();

        $polls = Poll::where('building_id', $flat->building_id)
            ->whereIn('status', ['active', 'closed', 'published'])
            ->whereNull('deleted_at')
            ->with(['questions', 'creator'])
            ->orderBy('created_at', 'desc')
            ->get();

        $data = $polls->map(function (Poll $poll) use ($user, $flat) {
            $hasVoted = $this->hasUserVoted($poll, $user->id, $flat->id);

            return [
                'id'                => $poll->id,
                'title'             => $poll->title,
                'description'       => $poll->description,
                'type'              => $poll->type,
                'structure'         => $poll->structure,
                'voting_type'       => $poll->voting_type,
                'status'            => $poll->display_status,
                'expiry_date'       => $poll->expiry_date ? $poll->expiry_date->toDateTimeString() : null,
                'created_by_name'   => $poll->creator ? $poll->creator->name : null,
                'created_by_role'   => $poll->created_by_role,
                'has_voted'         => $hasVoted,
                'total_voters'      => $poll->total_voters,
                'questions_count'   => $poll->questions->count(),
                'results_released'  => $poll->status === 'published',
            ];
        });

        return response()->json(['polls' => $data], 200);
    }

    // ─────────────────────────────────────────────────────────────
    // GET POLL DETAIL (questions + options)
    // ─────────────────────────────────────────────────────────────
    public function getPollDetail(Request $request)
    {
        $request->validate(['poll_id' => 'required|integer']);

        $user = Auth::user();
        $flat = AuthHelper::flat();

        $poll = Poll::where('id', $request->poll_id)
            ->where('building_id', $flat->building_id)
            ->whereIn('status', ['active', 'closed', 'published'])
            ->whereNull('deleted_at')
            ->with(['questions.options', 'creator'])
            ->first();

        if (! $poll) {
            return response()->json(['error' => 'Poll not found or not available.'], 404);
        }

        $hasVoted = $this->hasUserVoted($poll, $user->id, $flat->id);

        // Get user's existing votes so the UI can pre-select them
        $userVotes = PollVote::where('poll_id', $poll->id)
            ->where('user_id', $user->id)
            ->pluck('poll_option_id', 'poll_question_id')
            ->toArray();

        $questionsData = $poll->questions->map(function (PollQuestion $question) use ($userVotes) {
            return [
                'id'       => $question->id,
                'question' => $question->question,
                'order'    => $question->order,
                'options'  => $question->options->map(function (PollOption $option) use ($question, $userVotes) {
                    return [
                        'id'          => $option->id,
                        'option_text' => $option->option_text,
                        'order'       => $option->order,
                        'is_selected' => isset($userVotes[$question->id]) && $userVotes[$question->id] == $option->id,
                    ];
                }),
            ];
        });

        // Build participation percentage (total voters vs total eligible)
        $totalVotes   = $poll->total_voters;
        $totalFlats   = \App\Models\Flat::where('building_id', $flat->building_id)->where('status', 'Active')->count();
        $participation = $totalFlats > 0 ? round(($totalVotes / ($poll->voting_type === 'flat_based' ? $totalFlats : ($totalFlats * 2))) * 100, 1) : 0;
        if ($participation > 100) $participation = 100;

        return response()->json([
            'poll' => [
                'id'                => $poll->id,
                'title'             => $poll->title,
                'description'       => $poll->description,
                'type'              => $poll->type,
                'structure'         => $poll->structure,
                'voting_type'       => $poll->voting_type,
                'status'            => $poll->display_status,
                'expiry_date'       => $poll->expiry_date ? $poll->expiry_date->toDateTimeString() : null,
                'created_by_name'   => $poll->creator ? $poll->creator->name : null,
                'created_by_role'   => $poll->created_by_role,
                'has_voted'         => $hasVoted,
                'total_voters'      => $totalVotes,
                'participation_pct' => $participation,
                'results_released'  => $poll->status === 'published',
                'questions'         => $questionsData,
            ],
        ], 200);
    }

    // ─────────────────────────────────────────────────────────────
    // CAST VOTE
    // ─────────────────────────────────────────────────────────────
    public function castVote(Request $request)
    {
        $request->validate([
            'poll_id' => 'required|integer',
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => 'required|integer',
            'answers.*.option_id'   => 'required|integer',
        ]);

        $user = Auth::user();
        $flat = AuthHelper::flat();

        $poll = Poll::where('id', $request->poll_id)
            ->where('building_id', $flat->building_id)
            ->whereIn('status', ['active'])
            ->whereNull('deleted_at')
            ->with('questions')
            ->first();

        if (! $poll) {
            return response()->json(['error' => 'Poll not found or voting is not open.'], 404);
        }

        // Check expiry
        if ($poll->expiry_date && Carbon::now()->gt($poll->expiry_date)) {
            return response()->json(['error' => 'This poll has expired.'], 422);
        }

        // Check if already voted
        if ($this->hasUserVoted($poll, $user->id, $flat->id)) {
            return response()->json(['error' => 'You have already voted in this poll.'], 422);
        }

        // Validate submitted answers match poll questions
        $pollQuestionIds = $poll->questions->pluck('id')->toArray();
        $submittedQuestionIds = collect($request->answers)->pluck('question_id')->toArray();

        // For single-structure: only one question expected
        if ($poll->structure === 'single' && count($pollQuestionIds) > 0) {
            // Allow submitting just the first question
        }

        // For multiple: all questions must be answered
        if ($poll->structure === 'multiple') {
            $missing = array_diff($pollQuestionIds, $submittedQuestionIds);
            if (count($missing) > 0) {
                return response()->json(['error' => 'Please answer all questions.'], 422);
            }
        }

        DB::beginTransaction();
        try {
            foreach ($request->answers as $answer) {
                $questionId = $answer['question_id'];
                $optionId   = $answer['option_id'];

                // Verify question belongs to this poll
                if (! in_array($questionId, $pollQuestionIds)) {
                    DB::rollBack();
                    return response()->json(['error' => 'Invalid question.'], 422);
                }

                // Verify option belongs to this question
                $optionExists = PollOption::where('id', $optionId)
                    ->where('poll_question_id', $questionId)
                    ->exists();

                if (! $optionExists) {
                    DB::rollBack();
                    return response()->json(['error' => 'Invalid option for question.'], 422);
                }

                // Double-check duplicate (race condition safety)
                $existsQuery = PollVote::where('poll_question_id', $questionId);
                if ($poll->voting_type === 'flat_based') {
                    $existsQuery->where('flat_id', $flat->id);
                } else {
                    $existsQuery->where('user_id', $user->id);
                }

                if ($existsQuery->exists()) {
                    DB::rollBack();
                    return response()->json(['error' => 'You have already voted on this question.'], 422);
                }

                PollVote::create([
                    'poll_id'          => $poll->id,
                    'poll_question_id' => $questionId,
                    'poll_option_id'   => $optionId,
                    'user_id'          => $user->id,
                    'flat_id'          => $flat->id,
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Vote cast successfully.', 'has_voted' => true], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('castVote failed', ['poll_id' => $poll->id, 'user_id' => $user->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to cast vote. Please try again.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // GET POLL RESULTS (only if published)
    // ─────────────────────────────────────────────────────────────
    public function getPollResults(Request $request)
    {
        $request->validate(['poll_id' => 'required|integer']);

        $flat = AuthHelper::flat();

        $poll = Poll::where('id', $request->poll_id)
            ->where('building_id', $flat->building_id)
            ->whereNull('deleted_at')
            ->with(['questions.options', 'creator'])
            ->first();

        if (! $poll) {
            return response()->json(['error' => 'Poll not found.'], 404);
        }

        if ($poll->status !== 'published') {
            return response()->json(['error' => 'Results have not been released yet.'], 403);
        }

        $results = [];
        foreach ($poll->questions as $question) {
            $totalVotes = $question->votes()->count();
            $options = $question->options->map(function (PollOption $option) use ($question, $totalVotes) {
                $count = PollVote::where('poll_question_id', $question->id)
                    ->where('poll_option_id', $option->id)
                    ->count();
                return [
                    'id'         => $option->id,
                    'text'       => $option->option_text,
                    'votes'      => $count,
                    'percentage' => $totalVotes > 0 ? round(($count / $totalVotes) * 100, 1) : 0,
                ];
            });

            $results[] = [
                'question_id'  => $question->id,
                'question'     => $question->question,
                'total_votes'  => $totalVotes,
                'options'      => $options,
            ];
        }

        return response()->json([
            'poll' => [
                'id'                 => $poll->id,
                'title'              => $poll->title,
                'type'               => $poll->type,
                'voting_type'        => $poll->voting_type,
                'total_voters'       => $poll->total_voters,
                'result_released_at' => $poll->result_released_at ? $poll->result_released_at->toDateTimeString() : null,
                'created_by_name'    => $poll->creator ? $poll->creator->name : null,
                'created_by_role'    => $poll->created_by_role,
            ],
            'results' => $results,
        ], 200);
    }

    // ─────────────────────────────────────────────────────────────
    // HELPER
    // ─────────────────────────────────────────────────────────────
    private function hasUserVoted(Poll $poll, int $userId, int $flatId): bool
    {
        if ($poll->questions->isEmpty()) return false;

        // Check on the first question — if voted there, consider the poll voted
        $firstQuestion = $poll->questions->first();

        $query = PollVote::where('poll_question_id', $firstQuestion->id);
        if ($poll->voting_type === 'flat_based') {
            $query->where('flat_id', $flatId);
        } else {
            $query->where('user_id', $userId);
        }

        return $query->exists();
    }
}
