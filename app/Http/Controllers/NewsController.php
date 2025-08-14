<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NewsController extends Controller
{
    /**
     * Get news by game_id.
     *
     * @param int $gameId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNewsByGameId(Request $request)
    {
        $gameId = $request->input('game_id', 0);
        // Validate game_id
        if (!is_numeric($gameId) || $gameId <= 0) {
            return response()->json([
                'error' => 'Invalid game ID',
            ], 400);
        }

        // Fetch news record
        $news = DB::table('game_news')
            ->select('id', 'game_id', 'season_id', 'round', 'winner_id', 'title', 'content', 'created_at', 'updated_at')
            ->where('game_id', $gameId)
            ->first();

        if (!$news) {
            return response()->json([
                'error' => 'No news found for game ID ' . $gameId,
            ], 404);
        }

        return response()->json([
            'data' => $news,
        ], 200);
    }

    /**
     * Get all news with dynamic pagination and search.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllNews(Request $request)
    {
        // Get pagination parameters with defaults
        $currentPage = $request->input('current_page', 1);
        $itemsPerPage = $request->input('itemsperpage', 10);
        $search = $request->input('search', '');

        // Validate inputs
        if (!is_numeric($currentPage) || $currentPage < 1) {
            $currentPage = 1;
        }
        if (!is_numeric($itemsPerPage) || $itemsPerPage < 1 || $itemsPerPage > 100) {
            $itemsPerPage = 10;
        }

        // Build query with optional search
        $query = DB::table('game_news')
            ->select('id', 'game_id', 'season_id', 'round', 'winner_id', 'title', 'content', 'created_at', 'updated_at');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('content', 'like', '%' . $search . '%');
            });
        }

        // Calculate total items and pages
        $totalItems = $query->count();
        $totalPages = ceil($totalItems / $itemsPerPage);

        // Apply pagination
        $news = $query->orderBy('created_at', 'desc')
            ->skip(($currentPage - 1) * $itemsPerPage)
            ->take($itemsPerPage)
            ->get();

        return response()->json([
            'data' => $news,
            'pagination' => [
                'current_page' => (int) $currentPage,
                'total_pages' => (int) $totalPages,
                'total_items' => (int) $totalItems,
                'itemsperpage' => (int) $itemsPerPage,
            ],
        ], 200);
    }

}