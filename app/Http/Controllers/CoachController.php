<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Log;

class CoachController extends Controller
{
    // Create a new coach
    public function create(Request $request)
    {
        // Validation
        $request->validate([
            'name' => 'required|string|max:100',
            'team_id' => 'nullable|exists:teams,id',
            'coach_iq' => 'nullable|integer|min:0|max:100',
            'age' => 'required|integer|min:18|max:100',
            'retirement_age' => 'nullable|integer|min:35|max:75',
            'experience_years' => 'nullable|integer|min:0',
            'contract_years' => 'nullable|integer|min:1',
        ]);

        // Get the current season ID
        $currentSeasonId = get_current_season_id();

        // Insert the new coach into the database
        $coachId = DB::table('coaches')->insertGetId([
            'name' => $request->name,
            'team_id' => $request->team_id,
            'coach_iq' => $request->coach_iq ?? 70,
            'age' => $request->age,
            'retirement_age' => $request->retirement_age ?? 65,
            'experience_years' => $request->experience_years ?? 0,
            'contract_years' => $request->contract_years ?? 0,
            'is_active' => 1,  // Default active
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Log the creation
        Log::info('New Coach Created', ['coach_id' => $coachId, 'name' => $request->name]);

        // Insert a transaction log with the current season ID
        DB::table('transactions')->insert([
            'player_id' => 0, // No player involved here, can be adjusted if needed
            'season_id' => $currentSeasonId, // Use the current season ID here
            'details' => $request->name . ' has been appointed as the new coach.',
            'from_team_id' => 0,
            'to_team_id' => $request->team_id ?? 0,
            'status' => 'appointed',
        ]);

        return response()->json(['message' => 'Coach created successfully!'], 201);
    }

    // Read all coaches
    public function index()
    {
        $coaches = DB::table('coaches')->get();
        return response()->json($coaches);
    }

    // Read single coach
    public function show($id)
    {
        $coach = DB::table('coaches')->find($id);
        if (!$coach) {
            return response()->json(['message' => 'Coach not found'], 404);
        }
        return response()->json($coach);
    }

    // Update coach information
    public function update(Request $request, $id)
    {
        // Validation
        $request->validate([
            'name' => 'required|string|max:100',
            'team_id' => 'nullable|exists:teams,id',
            'coach_iq' => 'nullable|integer|min:0|max:100',
            'age' => 'required|integer|min:18|max:100',
            'retirement_age' => 'nullable|integer|min:35|max:75',
            'experience_years' => 'nullable|integer|min:0',
            'contract_years' => 'nullable|integer|min:1',
        ]);

        // Get the current season ID
        $currentSeasonId = get_current_season_id();

        // Update the coach in the database
        $updated = DB::table('coaches')->where('id', $id)->update([
            'name' => $request->name,
            'team_id' => $request->team_id,
            'coach_iq' => $request->coach_iq ?? 70,
            'age' => $request->age,
            'retirement_age' => $request->retirement_age ?? 65,
            'experience_years' => $request->experience_years ?? 0,
            'contract_years' => $request->contract_years ?? 0,
            'updated_at' => now(),
        ]);

        if ($updated) {
            // Log the update
            Log::info('Coach Updated', ['coach_id' => $id, 'name' => $request->name]);

            // Insert a transaction log with the current season ID
            DB::table('transactions')->insert([
                'player_id' => 0,
                'season_id' => $currentSeasonId, // Use the current season ID here
                'details' => $request->name . ' has had their coaching details updated.',
                'from_team_id' => 0,
                'to_team_id' => $request->team_id ?? 0,
                'status' => 'updated',
            ]);

            return response()->json(['message' => 'Coach updated successfully!']);
        } else {
            return response()->json(['message' => 'No changes made to coach'], 400);
        }
    }

    // Delete a coach
    public function destroy($id)
    {
        $coach = DB::table('coaches')->find($id);
        if (!$coach) {
            return response()->json(['message' => 'Coach not found'], 404);
        }

        DB::table('coaches')->where('id', $id)->delete();

        // Log the deletion
        Log::info('Coach Deleted', ['coach_id' => $id, 'name' => $coach->name]);

        // Insert a transaction log with the current season ID
        DB::table('transactions')->insert([
            'player_id' => 0,
            'season_id' => get_current_season_id(), // Use the current season ID here
            'details' => $coach->name . ' has been removed from coaching duties.',
            'from_team_id' => $coach->team_id ?? 0,
            'to_team_id' => 0,
            'status' => 'removed',
        ]);

        return response()->json(['message' => 'Coach deleted successfully!']);
    }
}
