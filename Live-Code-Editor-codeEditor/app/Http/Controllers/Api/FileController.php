<?php

namespace App\Http\Controllers\Api;
use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth; // This was missing!
use Illuminate\Support\Facades\Log;  // This was missing!
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FileController extends Controller
{
     public function getProjectFiles($projectId)
    {
        try {
            // Check if user has access to this project
            $project = Project::where('id', $projectId)
                ->whereHas('members', function($query) {
                    $query->where('user_id', Auth::id());
                })
                ->first();

            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project not found or access denied'
                ], 404);
            }

            $files = ProjectFile::where('project_id', $projectId)
                               ->orderBy('created_at', 'asc')
                               ->get();

            return response()->json([
                'success' => true,
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                ],
                'files' => $files->map(function($file) {
                    return [
                        'id' => $file->id,
                        'name' => $file->original_name,
                        'content' => $file->content,
                    ];
                })
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading project files: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Server error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function saveProjectFiles(Request $request, $projectId)
    {
        try {
            // Validate request
            $request->validate([
                'files' => 'required|array',
                'files.*.name' => 'required|string',
                'files.*.content' => 'required|string',
                'files.*.id' => 'nullable', // Can be string or null for new files
            ]);

            // Check project access
            $project = Project::where('id', $projectId)
                ->whereHas('members', function($query) {
                    $query->where('user_id', Auth::id());
                })
                ->first();

            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project not found or access denied'
                ], 404);
            }

            $savedFiles = [];
            $existingFileIds = [];

            foreach ($request->input('files') as $fileData) {
                // Check if this is an existing file (has numeric ID)
                $existingFile = null;
                if (isset($fileData['id']) && is_numeric($fileData['id'])) {
                    $existingFile = ProjectFile::where('id', $fileData['id'])
                        ->where('project_id', $projectId)
                        ->first();
                    
                    if ($existingFile) {
                        $existingFileIds[] = $existingFile->id;
                    }
                }

                if ($existingFile) {
                    // Update existing file
                    $existingFile->update([
                        'original_name' => $fileData['name'],
                        'content' => $fileData['content'],
                    ]);
                    $savedFiles[] = $existingFile->fresh();
                } else {
                    // Create new file
                    $filePath = "project_files/{$projectId}/" . strtolower(str_replace(' ', '_', $fileData['name']));
                    
                    $file = ProjectFile::create([
                        'project_id' => $projectId,
                        'original_name' => $fileData['name'],
                        'content' => $fileData['content'],
                        'file_path' => $filePath,
                    ]);
                    $savedFiles[] = $file;
                    $existingFileIds[] = $file->id;
                }
            }

            // Delete files that are no longer in the request (were removed from frontend)
            ProjectFile::where('project_id', $projectId)
                      ->whereNotIn('id', $existingFileIds)
                      ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Files saved successfully',
                'files' => collect($savedFiles)->map(function($file) {
                    return [
                        'id' => $file->id,
                        'name' => $file->original_name,
                    ];
                })
            ]);

        } catch (\Exception $e) {
            Log::error('Error saving project files: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Server error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request, Project $project)
    {
        try {
            // Check if user has access to this project
            // $hasAccess = $project->members()->where('user_id', Auth::id())->exists();
            
            // if (!$hasAccess) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Access denied'
            //     ], 403);
            // }

            $validator = Validator::make($request->all(), [
                'files.*' => 'required|file|max:5120', // 5MB max per file
                'files' => 'required|array|max:10', // Maximum 10 files at once
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $savedFiles = [];

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $originalName = $file->getClientOriginalName();
                    
                    // Check if file already exists
                    $existingFile = ProjectFile::where('project_id', $project->id)
                                              ->where('original_name', $originalName)
                                              ->first();
                    
                    if ($existingFile) {
                        continue; // Skip duplicate files
                    }

                    $content = file_get_contents($file->getPathname());
                    $filePath = "project_files/{$project->id}/{$originalName}";

                    // Create project directory if it doesn't exist
                    $projectDir = "project_files/{$project->id}";
                    if (!Storage::disk('public')->exists($projectDir)) {
                        Storage::disk('public')->makeDirectory($projectDir);
                    }

                    // Store file on disk
                    Storage::disk('public')->put($filePath, $content);

                    // Save file in database
                    $projectFile = ProjectFile::create([
                        'project_id' => $project->id,
                        'original_name' => $originalName,
                        'content' => $content,
                        'file_path' => $filePath,
                    ]);

                    $savedFiles[] = [
                        'id' => $projectFile->id,
                        'name' => $projectFile->original_name,
                        'path' => $projectFile->file_path,
                        'size' => strlen($content)
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => count($savedFiles) . ' file(s) uploaded successfully!',
                'files' => $savedFiles,
                'project' => $project
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error uploading files: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Server error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Project $project)
    {
        try {
            // Check if user has access to this project
            // $hasAccess = $project->members()->where('user_id', Auth::id())->exists();
            
            // if (!$hasAccess) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Access denied'
            //     ], 403);
            // }

            $files = $project->files()->get();
            
            return response()->json([
                'success' => true,
                'project' => $project,
                'files' => $files
            ]);

        } catch (\Exception $e) {
            Log::error('Error showing project: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Server error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Project $project, ProjectFile $file)
    {
        try {
            // Check if user has access to this project
            // $hasAccess = $project->members()->where('user_id', Auth::id())->exists();
            
            // if (!$hasAccess) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Access denied'
            //     ], 403);
            // }

            // Ensure the file belongs to the project
            if ($file->project_id !== $project->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found in this project'
                ], 404);
            }

            $fileName = $file->original_name;

            // Delete file from storage if it exists
            if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
                Storage::disk('public')->delete($file->file_path);
            }

            // Delete from database
            $file->delete();

            return response()->json([
                'success' => true,
                'message' => "File '{$fileName}' deleted successfully"
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting file: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Server error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    // Delete multiple files
    public function destroyMultiple(Request $request, Project $project)
    {
        try {
            // Check if user has access to this project
            // $hasAccess = $project->members()->where('user_id', Auth::id())->exists();
            
            // if (!$hasAccess) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Access denied'
            //     ], 403);
            // }

            $request->validate([
                'file_ids' => 'required|array',
                'file_ids.*' => 'required|integer|exists:project_files,id'
            ]);

            $fileIds = $request->input('file_ids');
            $deletedFiles = [];

            // Get files that belong to this project
            $files = ProjectFile::where('project_id', $project->id)
                               ->whereIn('id', $fileIds)
                               ->get();

            foreach ($files as $file) {
                $fileName = $file->original_name;

                // Delete from storage
                if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
                    Storage::disk('public')->delete($file->file_path);
                }

                // Delete from database
                $file->delete();
                
                $deletedFiles[] = $fileName;
            }

            return response()->json([
                'success' => true,
                'message' => count($deletedFiles) . ' file(s) deleted successfully',
                'deleted_files' => $deletedFiles
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting multiple files: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Server error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

}
