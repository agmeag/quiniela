<script lang="ts">
  import Nav from '../Components/Nav.svelte';
  import type { PredictionRow } from '../lib/types';
  import { formatDateShort } from '../lib/utils';

  let {
    participant,
    predictions,
  }: {
    participant: {
      id: number;
      name: string;
      slug: string;
      rank: number;
      total_points: number;
      exact_score_count: number;
    };
    predictions: PredictionRow[];
  } = $props();

  const finished = $derived(predictions.filter(p => p.status === 'finished'));
  const pending = $derived(predictions.filter(p => p.status !== 'finished'));

  const rankColors: Record<number, string> = {
    1: 'text-yellow-600 bg-yellow-50',
    2: 'text-slate-500 bg-slate-50',
    3: 'text-orange-700 bg-orange-50',
  };

  function rowClass(row: PredictionRow): string {
    if (row.status !== 'finished') return '';
    if (row.is_exact) return 'bg-emerald-50';
    if (row.correct_winner) return 'bg-blue-50';
    return '';
  }

  function pointsBadge(row: PredictionRow): { label: string; cls: string } | null {
    if (row.status !== 'finished' || row.points === 0) return null;
    if (row.is_exact) return { label: 'Exacto', cls: 'bg-emerald-100 text-emerald-700' };
    if (row.correct_winner) return { label: 'Ganador', cls: 'bg-blue-100 text-blue-700' };
    return null;
  }
</script>

<svelte:head>
  <title>{participant.name} — Quiniela 2026</title>
</svelte:head>

<div class="min-h-screen bg-[#F6F6F6]">
  <Nav />

  <!-- Header -->
  <div class="bg-white border-b border-[#E0E0E0]">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <a href="/leaderboard" class="text-xs font-bold text-[#9CA3AF] hover:text-[#3554FF] tracking-widest uppercase transition-colors">
        ← Clasificación
      </a>
      <div class="mt-3 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div class="flex items-center gap-4">
          <span class="inline-flex items-center justify-center w-12 h-12 text-sm font-black rounded-full
            {rankColors[participant.rank] ?? 'text-[#6B7280] bg-[#F6F6F6]'}">
            {participant.rank}°
          </span>
          <div>
            <h1 class="text-2xl sm:text-3xl font-black text-[#081B6A]">{participant.name}</h1>
            <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mt-0.5">
              {participant.exact_score_count} exactos · {finished.length} partidos jugados
            </p>
          </div>
        </div>
        <div class="text-right">
          <p class="text-4xl font-black text-[#3554FF]">{participant.total_points}</p>
          <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF]">puntos</p>
        </div>
      </div>
    </div>
  </div>

  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    <!-- Finished matches -->
    {#if finished.length > 0}
      <div>
        <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-3">
          Partidos jugados ({finished.length})
        </p>
        <div class="bg-white border border-[#E0E0E0] overflow-x-auto">
          <table class="w-full text-xs sm:text-sm">
            <thead>
              <tr class="border-b border-[#E0E0E0] text-[#9CA3AF]">
                <th class="px-4 py-3 text-left font-semibold tracking-widest uppercase w-8">#</th>
                <th class="px-4 py-3 text-left font-semibold tracking-widest uppercase">Partido</th>
                <th class="px-4 py-3 text-left font-semibold tracking-widest uppercase hidden sm:table-cell">Grupo</th>
                <th class="px-4 py-3 text-center font-semibold tracking-widest uppercase">Pronóstico</th>
                <th class="px-4 py-3 text-center font-semibold tracking-widest uppercase">Resultado</th>
                <th class="px-4 py-3 text-center font-semibold tracking-widest uppercase hidden sm:table-cell">Tipo</th>
                <th class="px-4 py-3 text-right font-semibold tracking-widest uppercase">Pts</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#F0F0F0]">
              {#each finished as row}
                {@const badge = pointsBadge(row)}
                <tr class={rowClass(row)}>
                  <td class="px-4 py-3 text-[#9CA3AF]">{row.correlativo}</td>
                  <td class="px-4 py-3">
                    <span class="font-semibold text-[#081B6A]">{row.home_team}</span>
                    <span class="text-[#9CA3AF] mx-1.5">vs</span>
                    <span class="font-semibold text-[#081B6A]">{row.away_team}</span>
                  </td>
                  <td class="px-4 py-3 text-[#9CA3AF] hidden sm:table-cell">{row.group_name ?? '—'}</td>
                  <td class="px-4 py-3 text-center font-mono font-semibold text-[#081B6A]">
                    {row.pred_home}–{row.pred_away}
                  </td>
                  <td class="px-4 py-3 text-center font-mono font-semibold text-[#6B7280]">
                    {row.actual_home}–{row.actual_away}
                  </td>
                  <td class="px-4 py-3 text-center hidden sm:table-cell">
                    {#if badge}
                      <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold tracking-wide uppercase {badge.cls}">
                        {badge.label}
                      </span>
                    {:else if row.status === 'finished'}
                      <span class="text-[#D1D5DB] text-[10px] font-bold tracking-wide uppercase">Fallo</span>
                    {/if}
                  </td>
                  <td class="px-4 py-3 text-right font-black {row.points > 0 ? 'text-[#3554FF]' : 'text-[#9CA3AF]'}">
                    {row.points > 0 ? `+${row.points}` : '—'}
                  </td>
                </tr>
              {/each}
            </tbody>
          </table>
        </div>
      </div>
    {/if}

    <!-- Pending matches -->
    {#if pending.length > 0}
      <div>
        <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-3">
          Por jugar ({pending.length})
        </p>
        <div class="bg-white border border-[#E0E0E0] overflow-x-auto">
          <table class="w-full text-xs sm:text-sm">
            <thead>
              <tr class="border-b border-[#E0E0E0] text-[#9CA3AF]">
                <th class="px-4 py-3 text-left font-semibold tracking-widest uppercase w-8">#</th>
                <th class="px-4 py-3 text-left font-semibold tracking-widest uppercase">Partido</th>
                <th class="px-4 py-3 text-left font-semibold tracking-widest uppercase hidden sm:table-cell">Grupo</th>
                <th class="px-4 py-3 text-center font-semibold tracking-widest uppercase">Pronóstico</th>
                <th class="px-4 py-3 text-right font-semibold tracking-widest uppercase">Fecha</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#F0F0F0]">
              {#each pending as row}
                <tr class="hover:bg-[#FAFAFA]">
                  <td class="px-4 py-3 text-[#9CA3AF]">{row.correlativo}</td>
                  <td class="px-4 py-3">
                    <span class="font-semibold text-[#081B6A]">{row.home_team}</span>
                    <span class="text-[#9CA3AF] mx-1.5">vs</span>
                    <span class="font-semibold text-[#081B6A]">{row.away_team}</span>
                  </td>
                  <td class="px-4 py-3 text-[#9CA3AF] hidden sm:table-cell">{row.group_name ?? '—'}</td>
                  <td class="px-4 py-3 text-center font-mono font-semibold text-[#3554FF]">
                    {row.pred_home}–{row.pred_away}
                  </td>
                  <td class="px-4 py-3 text-right text-[#9CA3AF]">{formatDateShort(row.match_date)}</td>
                </tr>
              {/each}
            </tbody>
          </table>
        </div>
      </div>
    {/if}

  </div>
</div>
