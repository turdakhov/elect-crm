<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlanSync;
use App\Models\Project;
use App\Services\Plan3d\EstimateSync;
use App\Services\Plan3d\JournalSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API для 3D-плана. Отправка ничего не применяет: журнал принимают и смету
 * выдают кнопками в CRM, посмотрев разницу. Ответ отправки — сводка этой
 * разницы и ссылка, где её смотреть.
 */
class Plan3dController extends Controller
{
    public function projects(): JsonResponse
    {
        return response()->json([
            'projects' => Project::query()->orderBy('name')->get(['id', 'name', 'address', 'status'])
                ->map(fn (Project $p) => ['id' => $p->id, 'name' => $p->name, 'address' => $p->address, 'status' => $p->status instanceof \BackedEnum ? $p->status->value : $p->status]),
        ]);
    }

    public function store(Project $project, Request $request): JsonResponse
    {
        $data = $request->validate([
            'source_project' => ['required', 'string', 'max:255'],
            'source_name' => ['nullable', 'string', 'max:255'],
            'journal' => ['present', 'array'],
            'journal.*.floor' => ['nullable', 'string', 'max:255'],
            'journal.*.room' => ['nullable', 'string', 'max:255'],
            'journal.*.name' => ['nullable', 'string', 'max:255'],
            'journal.*.code' => ['nullable', 'string', 'max:255'],
            'journal.*.cable' => ['nullable', 'string', 'max:255'],
            'journal.*.count' => ['required', 'integer', 'min:1', 'max:1000'],
            'journal.*.length' => ['required', 'numeric', 'min:0'],
            'journal.*.pipe' => ['nullable', 'string', 'max:255'],
            'journal.*.pipe_length' => ['required', 'numeric', 'min:0'],
            'needs' => ['present', 'array'],
            'needs.*.key' => ['required', 'string', 'max:255', 'distinct'],
            'needs.*.name' => ['required', 'string', 'max:255'],
            'needs.*.unit' => ['required', 'string', 'max:32'],
            'needs.*.qty' => ['required', 'numeric'],
            'needs.*.section' => ['nullable', 'string', 'max:255'],
            'warnings' => ['nullable', 'array'],
            'warnings.*' => ['string'],
        ]);

        $journal = array_map(fn ($r) => JournalSync::canonical($r), $data['journal']);
        $needs = array_map(fn ($n) => [
            'key' => $n['key'], 'name' => $n['name'], 'unit' => $n['unit'],
            'qty' => round((float) $n['qty'], 2), 'section' => $n['section'] ?? null,
        ], $data['needs']);
        // Отпечаток — по содержанию, а не по времени: та же модель, нажатая
        // дважды, — одна отправка, а не две одинаковых.
        $fingerprint = hash('sha256', json_encode([$journal, $needs], JSON_UNESCAPED_UNICODE));

        $latest = $project->planSyncs()->latest('id')->first();
        $unchanged = $latest && $latest->fingerprint === $fingerprint;
        $sync = $unchanged ? $latest : PlanSync::create([
            'project_id' => $project->id,
            'source_project' => $data['source_project'],
            'source_name' => $data['source_name'] ?? null,
            'fingerprint' => $fingerprint,
            'journal' => $journal,
            'needs' => $needs,
            'warnings' => $data['warnings'] ?? [],
        ]);
        EstimateSync::registerKeys($needs);

        $diff = JournalSync::diffFor($sync);
        $lines = EstimateSync::delta($sync);

        return response()->json([
            'id' => $sync->id,
            'unchanged' => $unchanged,
            'url' => route('plan-syncs.show', $sync),
            'project' => ['id' => $project->id, 'name' => $project->name],
            'journal' => [
                'accepted' => $sync->journal_accepted_at !== null,
                'added' => count($diff['added']),
                'removed' => count($diff['removed']),
                'changed' => count($diff['changed']),
                'errors' => JournalSync::errors($sync->journal),
            ],
            'estimate' => [
                'lines' => count($lines),
                'unmapped' => array_values(array_map(fn ($l) => $l['name'], array_filter($lines, fn ($l) => ! $l['product']))),
            ],
        ], $unchanged ? 200 : 201);
    }
}
