<script lang="ts">
  import Nav from '../Components/Nav.svelte';
  import type { PodiumEntry, PredictionRow } from '../lib/types';
  import { formatDateShort } from '../lib/utils';

  let { entries }: { entries: PodiumEntry[] } = $props();

  let openIds = $state<Set<number>>(new Set());

  function toggle(id: number) {
    const next = new Set(openIds);
    if (next.has(id)) {
      next.delete(id);
    } else {
      next.add(id);
    }
    openIds = next;
  }

  function rankStyle(rank: number): string {
    if (rank === 1) return 'bg-yellow-400 text-yellow-900 border-yellow-300';
    if (rank === 2) return 'bg-slate-300 text-slate-800 border-slate-200';
    if (rank === 3) return 'bg-orange-400 text-orange-900 border-orange-300';
    return 'bg-[#F0F0F0] text-[#6B7280] border-[#E0E0E0]';
  }

  function rankLabel(rank: number): string {
    if (rank === 1) return '🥇';
    if (rank === 2) return '🥈';
    if (rank === 3) return '🥉';
    return `${rank}°`;
  }

  function rowClass(row: PredictionRow): string {
    if (row.status !== 'finished') return '';
    if (row.is_exact) return 'bg-emerald-50';
    return '';
  }

  function resultIcon(row: PredictionRow): string {
    if (row.status !== 'finished') return '';
    return row.is_exact ? '✓' : '✗';
  }

  function resultIconClass(row: PredictionRow): string {
    if (row.status !== 'finished') return 'text-[#9CA3AF]';
    if (row.is_exact) return 'text-emerald-600 font-black';
    return 'text-red-400';
  }
</script>

<svelte:head>
  <title>Podio — Quiniela 2026</title>
</svelte:head>

<div class="min-h-screen bg-[#F6F6F6]">
  <Nav />

  <div class="bg-[#081B6A] text-white py-10">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <p class="text-[10px] font-bold tracking-widest uppercase text-[#3554FF] mb-2">Copa del Mundo 2026</p>
      <h1 class="text-3xl font-black">Podio</h1>
      <p class="text-white/50 text-sm mt-1">Top 10 — pronósticos completos</p>
    </div>
  </div>

  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-3">
    {#each entries as entry}
      {@const open = openIds.has(entry.id)}
      {@const finished = entry.predictions.filter(p => p.status === 'finished')}
      {@const pending = entry.predictions.filter(p => p.status !== 'finished')}

      <div class="bg-white border border-[#E0E0E0] overflow-hidden">
        <!-- Accordion header -->
        <button
          class="w-full flex items-center gap-4 px-5 py-4 text-left hover:bg-[#FAFAFA] transition-colors"
          onclick={() => toggle(entry.id)}
        >
          <!-- Rank badge -->
          <span class="inline-flex items-center justify-center w-10 h-10 text-base font-black border {rankStyle(entry.rank)} shrink-0">
            {rankLabel(entry.rank)}
          </span>

          <!-- Name + stats -->
          <div class="flex-1 min-w-0">
            <p class="font-black text-[#081B6A] text-sm sm:text-base truncate">{entry.name}</p>
            <p class="text-[10px] text-[#9CA3AF] tracking-widest uppercase mt-0.5">
              {entry.exact_score_count} exactos · {finished.length} jugados
            </p>
          </div>

          <!-- Points -->
          <div class="text-right shrink-0">
            <p class="text-2xl font-black text-[#3554FF]">{entry.total_points}</p>
            <p class="text-[10px] text-[#9CA3AF] tracking-widest uppercase">pts</p>
          </div>

          <!-- Chevron -->
          <svg
            class="w-5 h-5 text-[#9CA3AF] shrink-0 transition-transform {open ? 'rotate-180' : ''}"
            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <!-- Expanded predictions -->
        {#if open}
          <div class="border-t border-[#E0E0E0]">
            <!-- Finished matches -->
            {#if finished.length > 0}
              <div class="px-5 py-3 border-b border-[#F0F0F0]">
                <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-2">Jugados ({finished.length})</p>
                <div class="overflow-x-auto">
                  <table class="w-full text-xs sm:text-sm">
                    <thead>
                      <tr class="text-[#9CA3AF] border-b border-[#F0F0F0]">
                        <th class="py-2 text-left font-semibold tracking-wide w-6">#</th>
                        <th class="py-2 text-left font-semibold tracking-wide">Partido</th>
                        <th class="py-2 text-center font-semibold tracking-wide">Pronóstico</th>
                        <th class="py-2 text-center font-semibold tracking-wide">Resultado</th>
                        <th class="py-2 text-center font-semibold tracking-wide w-8"></th>
                        <th class="py-2 text-right font-semibold tracking-wide">Pts</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F8F8F8]">
                      {#each finished as row}
                        <tr class={rowClass(row)}>
                          <td class="py-2 text-[#9CA3AF]">{row.correlativo}</td>
                          <td class="py-2">
                            <span class="font-semibold text-[#081B6A]">{row.home_team}</span>
                            <span class="text-[#9CA3AF] mx-1">vs</span>
                            <span class="font-semibold text-[#081B6A]">{row.away_team}</span>
                          </td>
                          <td class="py-2 text-center font-mono font-semibold text-[#081B6A]">
                            {row.pred_home}–{row.pred_away}
                          </td>
                          <td class="py-2 text-center font-mono font-semibold text-[#6B7280]">
                            {row.actual_home}–{row.actual_away}
                          </td>
                          <td class="py-2 text-center text-sm {resultIconClass(row)}">
                            {resultIcon(row)}
                          </td>
                          <td class="py-2 text-right font-black {row.points > 0 ? 'text-[#3554FF]' : 'text-[#9CA3AF]'}">
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
              <div class="px-5 py-3">
                <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-2">Por jugar ({pending.length})</p>
                <div class="overflow-x-auto">
                  <table class="w-full text-xs sm:text-sm">
                    <thead>
                      <tr class="text-[#9CA3AF] border-b border-[#F0F0F0]">
                        <th class="py-2 text-left font-semibold tracking-wide w-6">#</th>
                        <th class="py-2 text-left font-semibold tracking-wide">Partido</th>
                        <th class="py-2 text-center font-semibold tracking-wide">Pronóstico</th>
                        <th class="py-2 text-left font-semibold tracking-wide text-right">Fecha</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F8F8F8]">
                      {#each pending as row}
                        <tr>
                          <td class="py-2 text-[#9CA3AF]">{row.correlativo}</td>
                          <td class="py-2">
                            <span class="font-semibold text-[#081B6A]">{row.home_team}</span>
                            <span class="text-[#9CA3AF] mx-1">vs</span>
                            <span class="font-semibold text-[#081B6A]">{row.away_team}</span>
                          </td>
                          <td class="py-2 text-center font-mono font-semibold text-[#081B6A]">
                            {row.pred_home}–{row.pred_away}
                          </td>
                          <td class="py-2 text-right text-[#9CA3AF]">{formatDateShort(row.match_date)}</td>
                        </tr>
                      {/each}
                    </tbody>
                  </table>
                </div>
              </div>
            {/if}
          </div>
        {/if}
      </div>
    {/each}
  </div>
</div>
