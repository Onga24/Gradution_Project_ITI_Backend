<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\CreateProjectRequest;
use App\Http\Requests\JoinProjectRequest;
use App\Models\Project;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProjectController extends Controller
{

    private function generateUniqueInvite()
{
    do {
        $code = Str::random(8);
    } while (Project::where('invite_code', $code)->exists());

    return $code;
}


public function myProjects(Request $request)
{
    $user = $request->user();
    $projects = $user->projects()->with('members')->get();

    return response()->json([
        'success' => true,
        'projects' => $projects,
    ]);
}


        public function store(CreateProjectRequest $request)
    {
        $user = $request->user();

        return DB::transaction(function () use ($request, $user) {
            $invite = $request->input('invite_code') ?? $this->generateUniqueInvite();

            $project = Project::create([
                'name' => $request->input('name'),
                'owner_id' => $user->id,
                'invite_code' => $invite,
            ]);

            $project->members()->attach($user->id, ['role' => 'owner']);

            $project->load('owner', 'members');

            return response()->json([
                'success' => true,
                'project' => $project
            ], 201);
        });
    }

        public function joinByInvite(JoinProjectRequest $request)
    {
        $user = $request->user();
        $invite = $request->input('invite_code');

        $project = Project::where('invite_code', $invite)->firstOrFail();

        $exists = $project->members()->where('user_id', $user->id)->exists();
        if ($exists) {
            return response()->json(['message' => 'Already joined'], 409);
        }

        try {
            $project->members()->attach($user->id, ['role' => 'member']);
        } catch (QueryException $e) {
            return response()->json(['message' => 'Already joined'], 409);
        }

        $project->load('members');


        return response()->json([
            'success' => true,
            'project' => $project,
        ], 200);
    }

    public function saveCode(Request $request, Project $project)
{
    $request->validate([
        'code' => 'required|string',
    ]);

    if (!$project->members()->where('user_id', $request->user()->id)->exists()) {
        return response()->json(['message' => 'Not authorized'], 403);
    }

    $project->update([
        'code' => $request->input('code')
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Code saved successfully'
    ]);
}

public function show(Project $project, Request $request)
{
    if (!$project->members()->where('user_id', $request->user()->id)->exists()) {
        return response()->json(['message' => 'Not authorized'], 403);
    }

    $project->load('members', 'owner'); 
    return response()->json([
        'success' => true,
        'project' => $project
    ]);
}


public function update(Request $request, Project $project)
{
    $user = $request->user();

    // Only owner can edit
    if ($project->owner_id !== $user->id) {
        return response()->json(['message' => 'Not authorized'], 403);
    }

    $request->validate([
        'name' => 'required|string|max:255',
    ]);

    $project->update([
        'name' => $request->input('name'),
    ]);

    return response()->json([
        'success' => true,
        'project' => $project
    ]);
}


public function destroy(Request $request, Project $project)
{
    $user = $request->user();

    // Only owner can delete
    if ($project->owner_id !== $user->id) {
        return response()->json(['message' => 'Not authorized'], 403);
    }

    $project->delete(); 

    return response()->json([
        'success' => true,
        'message' => 'Project deleted successfully'
    ]);
}



}