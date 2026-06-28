<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaderboardEntry;
use App\Models\MatchRanking;
use App\Models\Participant;
use App\Models\Prediction;
use App\Models\WorldCupMatch;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    private const STAGE_LABELS = [
        'group'       => 'Fase de grupos',
        'round_of_32' => 'Dieciseisavos de final',
        'round_of_16' => 'Octavos de final',
        'quarter'     => 'Cuartos de final',
        'semi'        => 'Semifinales',
        'third_place' => 'Tercer puesto',
        'final'       => 'Final',
    ];

    public function predictions(Request $request): StreamedResponse
    {
        $stage      = $request->query('stage', 'group');
        $stageLabel = self::STAGE_LABELS[$stage] ?? $stage;

        // ── Data loading ──────────────────────────────────────────
        $matches = WorldCupMatch::where('stage', $stage)
            ->orderBy('match_date')
            ->get();

        $matchIds = $matches->pluck('id');

        // predictions[$participantId][$matchId] = Prediction
        $predMap = Prediction::whereIn('match_id', $matchIds)
            ->get()
            ->groupBy('participant_id')
            ->map(fn ($rows) => $rows->keyBy('match_id'));

        // rankings[$participantId][$matchId] = MatchRanking
        $rankMap = MatchRanking::whereIn('match_id', $matchIds)
            ->get()
            ->groupBy('participant_id')
            ->map(fn ($rows) => $rows->keyBy('match_id'));

        $leaderboardRanks = LeaderboardEntry::pluck('rank', 'participant_id');

        // Phase totals: sum of points from match rankings
        $phaseTotals = $rankMap->map(fn ($rows) => $rows->sum('points'));

        // All participants sorted by phase points desc, then name
        $participants = Participant::orderBy('name')->get()
            ->sortBy([
                fn ($a, $b) => ($phaseTotals->get($b->id, 0) <=> $phaseTotals->get($a->id, 0)),
                fn ($a, $b) => $a->name <=> $b->name,
            ])->values();

        // ── Build spreadsheet ─────────────────────────────────────
        $spreadsheet = new Spreadsheet;
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr($stageLabel, 0, 31));

        $matchCount = $matches->count();
        $totalCols  = 2 + $matchCount + 1; // Name + Rank + N matches + Total
        $lastCol    = Coordinate::stringFromColumnIndex($totalCols);
        $totalCol   = Coordinate::stringFromColumnIndex(2 + $matchCount + 1);

        // ── Row 1 — title ─────────────────────────────────────────
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', "Reporte de Predicciones — {$stageLabel} — Quiniela 2026");
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '081B6A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // ── Row 2 — generated-at subtitle ─────────────────────────
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->setCellValue('A2', 'Generado: ' . now()->format('d/m/Y H:i') . ' (hora SV)');
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['size' => 9, 'color' => ['rgb' => '6B7280']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(16);

        // ── Row 4 — header ────────────────────────────────────────
        $sheet->setCellValue('A4', 'Participante');
        $sheet->setCellValue('B4', 'Pos.\nGeneral');
        $sheet->getStyle('B4')->getAlignment()->setWrapText(true);

        foreach ($matches as $idx => $match) {
            $col = Coordinate::stringFromColumnIndex(3 + $idx);

            $dateStr = $match->match_date
                ? $match->match_date->format('d/m H:i')
                : '';

            $header = "P{$match->correlativo}\n{$match->home_team}\nvs\n{$match->away_team}\n{$dateStr}";
            if ($match->status === 'finished' && $match->home_score !== null) {
                $header .= "\n✓ {$match->home_score}-{$match->away_score}";
            }

            $sheet->setCellValue("{$col}4", $header);
            $sheet->getStyle("{$col}4")->getAlignment()->setWrapText(true);
            $sheet->getColumnDimension($col)->setWidth(16);
        }

        $sheet->setCellValue("{$totalCol}4", "Total\nFase");
        $sheet->getStyle("{$totalCol}4")->getAlignment()->setWrapText(true);

        $sheet->getStyle("A4:{$lastCol}4")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3554FF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(70);

        // ── Rows 5+ — participants ─────────────────────────────────
        $colorExact  = 'C6EFCE'; // green
        $colorWinner = 'FFEB9C'; // yellow
        $colorWrong  = 'FFC7CE'; // red/pink
        $colorNoPred = 'E8E8E8'; // gray (no prediction)

        foreach ($participants as $pIdx => $participant) {
            $pid    = $participant->id;
            $row    = 5 + $pIdx;
            $rowBg  = ($pIdx % 2 === 0) ? 'FFFFFF' : 'F6F6F6';

            $sheet->setCellValue("A{$row}", $participant->name);
            $overallRank = $leaderboardRanks->get($pid);
            $sheet->setCellValue("B{$row}", $overallRank !== null ? "#{$overallRank}" : '-');

            $phaseTotal = 0;

            foreach ($matches as $idx => $match) {
                $col     = Coordinate::stringFromColumnIndex(3 + $idx);
                $pred    = $predMap->get($pid)?->get($match->id);
                $ranking = $rankMap->get($pid)?->get($match->id);

                if ($pred) {
                    $cellVal   = "{$pred->home_score}-{$pred->away_score}";
                    $fillColor = $rowBg;

                    if ($ranking) {
                        $pts         = $ranking->points;
                        $phaseTotal += $pts;
                        $cellVal    .= "\n{$pts}pts";
                        $fillColor   = $ranking->is_exact ? $colorExact
                            : ($ranking->correct_winner ? $colorWinner : $colorWrong);
                    } elseif ($match->status !== 'finished') {
                        // Not yet calculated — match still pending
                        $fillColor = $rowBg;
                    }
                } else {
                    $cellVal   = '-';
                    $fillColor = $colorNoPred;
                }

                $sheet->setCellValue("{$col}{$row}", $cellVal);
                $sheet->getStyle("{$col}{$row}")->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'wrapText'   => true,
                    ],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fillColor]],
                ]);
            }

            // Total column
            $displayTotal = $phaseTotal ?: $phaseTotals->get($pid, 0);
            $sheet->setCellValue("{$totalCol}{$row}", $displayTotal);
            $sheet->getStyle("{$totalCol}{$row}")->applyFromArray([
                'font'      => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $rowBg]],
            ]);

            // Name + rank cells
            $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $rowBg]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle("B{$row}")->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            $sheet->getRowDimension($row)->setRowHeight(28);
        }

        // ── Legend ────────────────────────────────────────────────
        $lastDataRow  = 5 + $participants->count();
        $legendRow    = $lastDataRow + 2;

        $sheet->setCellValue("A{$legendRow}", 'Leyenda:');
        $sheet->getStyle("A{$legendRow}")->getFont()->setBold(true);

        foreach ([
            [$colorExact,  'Pronóstico exacto'],
            [$colorWinner, 'Ganador/resultado correcto'],
            [$colorWrong,  'Incorrecto'],
            [$colorNoPred, 'Sin pronóstico'],
        ] as $li => [$color, $label]) {
            $r = $legendRow + 1 + $li;
            $sheet->setCellValue("A{$r}", $label);
            $sheet->getStyle("A{$r}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
            ]);
        }

        // ── Column widths + freeze panes ──────────────────────────
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(10);
        $sheet->getColumnDimension($totalCol)->setWidth(10);
        $sheet->freezePane('C5');

        // ── Stream to browser ─────────────────────────────────────
        $slug     = str_replace([' ', '/'], '_', mb_strtolower($stageLabel));
        $filename = "quiniela_{$slug}_" . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
