<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        return Inertia::render('Users/Index', [
            'status' => session('status'),
        ]);
    }

    public function list(Request $request)
    {
        // Retrieve search query from request
        $searchQuery = $request->search;

        // Query builder for teams with join on leagues and conferences tables
        $query = User::query()
            ->select('users.*');

        // Apply search filter if search query is provided
        if ($searchQuery) {
            $query->where('users.name', 'like', '%' . $searchQuery . '%')
                ->orWhere('users.email', 'like', '%' . $searchQuery . '%');
        }

        // Get total count of records before pagination
        $totalCount = $query->count();

        // Set the number of records to display per page
        $perPage = 10;

        // Calculate the total number of pages
        $totalPages = ceil($totalCount / $perPage);

        // Get the current page from the request, default to 1 if not provided
        $currentPage = $request->page_num ?? 1;

        // Calculate the offset for pagination
        $offset = ($currentPage - 1) * $perPage;

        // Retrieve teams data with pagination
        $users = $query->offset($offset)
            ->limit($perPage)
            ->get();

        return response()->json([
            'users' => $users,
            'total_pages' => $totalPages,
            'current_page' => $currentPage,
            'total_count' => $totalCount,
            'search' => $searchQuery,
        ]);
    }

    // Update the specified resource in storage.
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'name' => 'required',
            'email' => 'required',
            'password' => 'required',
        ]);

        $team = User::findOrFail($request->id);
        $team->update($request->all());

        return redirect()->route('users.index');
    }

    // Remove the specified resource from storage.
    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
        ]);

        $team = User::findOrFail($request->id);
        $team->delete();

        return redirect()->route('users.index');
    }
}
