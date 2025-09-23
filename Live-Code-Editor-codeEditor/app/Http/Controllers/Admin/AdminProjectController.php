<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class AdminProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::with('owner','members');

        if ($request->boolean('trashed')) {
            $query = $query->withTrashed();
        }

        if ($q = $request->input('q')) {
            $query->where('name', 'like', "%$q%");
        }

        $projects = $query->paginate($request->get('per_page', 20));
        return response()->json($projects);
    }

    public function show(Project $project)
    {
        $project->load('owner','members');
        return response()->json($project);
    }

    public function update(Request $request, Project $project)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $project->update(['name' => $data['name']]);
        return response()->json(['message'=>'Project updated','project'=>$project]);
    }

    public function destroy(Project $project)
    {
        $project->delete(); // soft delete
        return response()->json(['message' => 'Project soft-deleted']);
    }

    public function restore($id)
    {
        $project = Project::withTrashed()->findOrFail($id);
        $project->restore();
        return response()->json(['message' => 'Project restored', 'project'=>$project]);
    }

    public function forceDelete($id)
    {
        $project = Project::withTrashed()->findOrFail($id);
        $project->forceDelete();
        return response()->json(['message' => 'Project permanently deleted.']);
    }






}
