<?php

namespace App\Http\Controllers;

use App\Models\ProjectProductSet;
use App\Services\EstimateXlsx;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EstimateXlsxController extends Controller
{
    public function __invoke(ProjectProductSet $projectProductSet, EstimateXlsx $xlsx): BinaryFileResponse
    {
        $set = $projectProductSet;
        // Имя — как у PDF сметы, чтобы два файла одной сметы лежали рядом.
        $name = trim('smeta-'.Str::slug($set->name, '-').'-'.Str::slug($set->project->name, '-'), '-');
        $filename = ($name !== 'smeta' ? $name : 'smeta-'.$set->id).'.xlsx';

        $dir = storage_path('app/temp');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $path = $dir.'/'.Str::random(8).'-'.$filename;
        $xlsx->save($set, $path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }
}
