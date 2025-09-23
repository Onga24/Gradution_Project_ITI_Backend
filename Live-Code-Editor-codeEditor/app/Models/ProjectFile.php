<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class ProjectFile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'project_id',
        'file_path',
        'original_name',
        'content',
    ];

    /**
     * Get the project that owns the file.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
