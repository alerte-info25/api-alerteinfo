<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProblemReportImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'problem_report_id',
        'image_path',
    ];
    public function problemReport()
    {
        return $this->belongsTo(ProblemReport::class);
    }
}
