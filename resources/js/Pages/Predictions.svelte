<script lang="ts">
  import { router } from '@inertiajs/svelte';
  import Nav from '../Components/Nav.svelte';
  import type { MatchWithPrediction } from '../lib/types';
  import { STAGE_LABELS, STAGE_ORDER, formatDate } from '../lib/utils';

  let {
    matches,
    participant,
  }: {
    matches: MatchWithPrediction[];
    participant: { name: string } | null;
  } = $props();

  // Refresh live scores every 10 seconds
  $effect(() => {
    const interval = setInterval(() => {
      router.reload({ only: ['matches'], preserveState: true });
    }, 10_000);
    return () => clearInterval(interval);
  });

  // Group matches by stage in tournament order, skip empty stages
  const stageGroups = $derived(
    STAGE_ORDER
      .map(s => ({ stage: s, label: STAGE_LABELS[s], matches: matches.filter(m => m.stage === s) }))
      .filter(g => g.matches.length > 0)
  );

  // Within a stage, sub-group by group letter (group stage only)
  function subGroups(stageMatches: MatchWithPrediction[], stage: string) {
    if (stage !== 'group') return [{ key: '', matches: stageMatches }];
    const keys = [...new Set(stageMatches.map(m => m.group_name ?? ''))].sort();
    return keys.map(k => ({ key: k, matches: stageMatches.filter(m => (m.group_name ?? '') === k) }));
  }
</script>

<svelte:head>
  <title>Mis predicciones — Quiniela 2026</title>
</svelte:head>

<div class="min-h-screen bg-[#F6F6F6]">
  <Nav />

  <!-- Page header -->
  <div class="bg-white border-b border-[#E0E0E0]">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
      <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1">Copa del Mundo 2026</p>
      <div>
        <h1 class="text-3xl font-black text-[#081B6A]">Mis predicciones</h1>
        {#if participant}
          <p class="text-sm text-[#6B7280] mt-1">{participant.name}</p>
        {/if}
      </div>
    </div>
  </div>

  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {#if !participant}
      <div class="bg-white border border-[#E0E0E0] p-10 text-center">
        <p class="text-[#6B7280] font-medium">Tu cuenta no está vinculada a un participante.</p>
        <p class="text-sm text-[#9CA3AF] mt-1">Contacta al administrador para resolver esto.</p>
      </div>

    {:else if matches.length === 0}
      <div class="bg-white border border-[#E0E0E0] p-10 text-center">
        <p class="text-[#9CA3AF] font-medium">No hay partidos registrados aún.</p>
      </div>

    {:else}
      {#each stageGroups as sg}
        <!-- Stage section header -->
        <div class="flex items-center gap-3 mt-8 mb-4 first:mt-0">
          <span class="h-px flex-1 bg-[#E0E0E0]"></span>
          <p class="text-[11px] font-black tracking-widest uppercase text-[#081B6A]">{sg.label}</p>
          <span class="h-px flex-1 bg-[#E0E0E0]"></span>
        </div>

        {#each subGroups(sg.matches, sg.stage) as sub}
          {#if sub.key}
            <!-- Group letter divider (group stage only) -->
            <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-3 mt-5 flex items-center gap-3">
              <span class="h-px flex-1 bg-[#EBEBEB]"></span>
              {sub.key}
              <span class="h-px flex-1 bg-[#EBEBEB]"></span>
            </p>
          {/if}

          <div class="space-y-3 mb-2">
            {#each sub.matches as m (m.id)}
              <div class="bg-white border border-[#E0E0E0]"
                class:border-red-200={m.status === 'live'}
              >
                <!-- Match header -->
                <div class="px-4 pt-4 pb-2 flex items-center justify-between gap-2 flex-wrap">
                  <div class="flex items-center gap-2 min-w-0">
                    <span class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] shrink-0">
                      {formatDate(m.match_date)}
                    </span>
                    {#if m.status === 'live'}
                      <span class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                        <span class="text-[10px] font-bold text-red-500">
                          {m.minute ? `${m.minute}'` : 'En vivo'}
                        </span>
                      </span>
                    {:else if m.status === 'finished'}
                      <span class="inline-block px-1.5 py-0.5 text-[9px] font-black tracking-widest uppercase bg-[#081B6A] text-white">
                        Final
                      </span>
                      {#if m.home_score !== null && m.home_score !== undefined}
                        <span class="text-[10px] font-bold text-[#081B6A]">{m.home_score}–{m.away_score}</span>
                      {/if}
                    {/if}
                  </div>
                </div>

                <!-- Teams + prediction scores -->
                <div class="px-4 pb-4 flex items-center gap-2 sm:gap-4">
                  <!-- Home team -->
                  <div class="flex items-center gap-1.5 flex-1 min-w-0">
                    <span class="text-xl leading-none shrink-0">{m.home_team_flag ?? ''}</span>
                    <span class="font-bold text-[#081B6A] text-sm truncate">{m.home_team}</span>
                  </div>

                  <!-- Prediction (read-only) -->
                  <div class="flex items-center gap-2 shrink-0">
                    {#if m.prediction}
                      <span class="w-12 h-10 flex items-center justify-center text-lg font-black border-2 border-[#E0E0E0] text-[#081B6A] bg-[#F6F6F6]">
                        {m.prediction.home_score}
                      </span>
                      <span class="text-[#9CA3AF] font-black text-base select-none">–</span>
                      <span class="w-12 h-10 flex items-center justify-center text-lg font-black border-2 border-[#E0E0E0] text-[#081B6A] bg-[#F6F6F6]">
                        {m.prediction.away_score}
                      </span>
                    {:else}
                      <span class="text-sm text-[#9CA3AF] italic">Sin pronóstico</span>
                    {/if}
                  </div>

                  <!-- Away team -->
                  <div class="flex items-center gap-1.5 flex-1 min-w-0 justify-end">
                    <span class="font-bold text-[#081B6A] text-sm truncate text-right">{m.away_team}</span>
                    <span class="text-xl leading-none shrink-0">{m.away_team_flag ?? ''}</span>
                  </div>
                </div>
              </div>
            {/each}
          </div>
        {/each}
      {/each}
    {/if}
  </div>
</div>
