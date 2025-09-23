<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Project;

use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
        public function index(Request $request)
    {
        $query = User::query();

        if ($request->boolean('trashed')) {
            $query = $query->withTrashed();
        }

        if ($q = $request->input('q')) {
            $query->where(function($qq) use ($q) {
                $qq->where('name', 'like', "%$q%")
                   ->orWhere('email', 'like', "%$q%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);

        return response()->json($users);
    }

    public function show(User $user)
    {
        $user->load('projects'); 
        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => 'nullable|in:member,admin',
            'force_deactivate' => 'nullable|boolean',
        ]);

        // Prevent self role change
        if ($request->user()->id === $user->id && isset($data['role']) && $data['role'] !== $user->role) {
            return response()->json(['message' => 'You cannot change your own role.'], 403);
        }

        DB::transaction(function() use($user, $data) {
            if (isset($data['role'])) {
                $user->role = $data['role'];
            }
            if (isset($data['force_deactivate'])) {
                $user->is_active = !$data['force_deactivate'] ? 1 : 0; // if you implement is_active
            }
            $user->save();
        });

        return response()->json(['message'=>'User updated','user'=>$user]);
    }

    public function destroy(Request $request, User $user)
    {
        // Prevent deleting yourself
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'You cannot delete yourself.'], 403);
        }

        // Prevent deleting last admin
        if ($user->role === 'admin') {
            $admins = User::where('role','admin')->whereNull('deleted_at')->count();
            if ($admins <= 1) {
                return response()->json(['message' => 'Cannot delete the last admin.'], 403);
            }
        }

        $user->delete(); // soft delete
        return response()->json(['message' => 'User soft-deleted.']);
    }

    public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();
        return response()->json(['message' => 'User restored', 'user'=>$user]);
    }

    public function forceDelete($id)
    {
        $user = User::withTrashed()->findOrFail($id);

        if ($user->role === 'admin') {
            $admins = User::where('role','admin')->whereNull('deleted_at')->count();
            if ($admins <= 1) {
                return response()->json(['message' => 'Cannot force-delete the last admin.'], 403);
            }
        }

        $user->forceDelete();
        return response()->json(['message' => 'User permanently deleted.']);
    }


        public function stats()
    {
        $users = User::count();      
        $projects = Project::count(); 

        return response()->json([
            'users' => $users,
            'projects' => $projects,
        ]);
    }
















    

}
