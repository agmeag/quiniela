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

  $effect(() => {
    const interval = setInterval(() => {
      router.reload({ only: ['matches'], preserveState: true });
    }, 10_000);
    return () => clearInterval(interval);
  });

  const stageGroups = $derived(
    STAGE_ORDER
      .map(s => ({ stage: s, label: STAGE_LABELS[s], matches: matches.filter(m => m.stage === s) }))
      .filter(g => g.matches.length > 0)
  );

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

  <div class="bg-white border-b border-[#E0E0E0]">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-4">
      <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-0.5">Copa del Mundo 2026</p>
      <h1 class="text-2xl font-black text-[#081B6A]">Mis predicciones</h1>
      {#if participant}
        <p class="text-xs text-[#6B7280] mt-0.5">{participant.name}</p>
      {/if}
    </div>
  </div>

  <div class="max-w-3xl mx-auto px-4 sm:px-6 py-6">

    {#if !participant}
      <div class="bg-white border border-[#E0E0E0] p-8 text-center">
        <p class="text-[#6B7280] font-medium text-sm">Tu cuenta no está vinculada a un participante.</p>
      </div>

    {:else if matches.length === 0}
      <div class="bg-white border border-[#E0E0E0] p-8 text-center">
        <p class="text-[#9CA3AF] font-medium text-sm">No hay partidos registrados aún.</p>
      </div>

    {:else}
      {#each stageGroups as sg}
        <!-- Stage header -->
        <div class="flex items-center gap-2 mt-6 mb-1 first:mt-0">
          <span class="h-px flex-1 bg-[#E0E0E0]"></span>
          <p class="text-[10px] font-black tracking-widest uppercase text-[#081B6A]">{sg.label}</p>
          <span class="h-px flex-1 bg-[#E0E0E0]"></span>
        </div>

        {#each subGroups(sg.matches, sg.stage) as sub}
          {#if sub.key}
            <p class="text-[9px] font-bold tracking-widest uppercase text-[#BDBDBD] mt-3 mb-1 text-center">{sub.key}</p>
          {/if}

          <!-- Compact match list -->
          <div class="bg-white border border-[#E0E0E0] divide-y divide-[#F0F0F0] mb-1">
            {#each sub.matches as m (m.id)}
              <div
                class="flex items-center gap-2 px-3 py-2"
                class:bg-red-50={m.status === 'live'}
              >
                <!-- Date / status pill -->
                <div class="w-[72px] shrink-0 text-right">
                  {#if m.status === 'live'}
                    <span class="inline-flex items-center gap-1">
                      <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                      <span class="text-[9px] font-bold text-red-500">{m.minute ? `${m.minute}'` : 'VIVO'}</span>
                    </span>
                  {:else if m.status === 'finished'}
                    <span class="text-[9px] font-black text-[#081B6A]">
                      {#if m.home_score !== null && m.home_score !== undefined}
                        {m.home_score}–{m.away_score}
                      {:else}
                        FIN
                      {/if}
                    </span>
                  {:else}
                    <span class="text-[9px] text-[#9CA3AF] leading-tight block">{formatDate(m.match_date)}</span>
                  {/if}
                </div>

                <!-- Home team -->
                <div class="flex items-center gap-1 flex-1 min-w-0 justify-end">
                  <span class="text-xs font-semibold text-[#081B6A] truncate">{m.home_team}</span>
                  <span class="text-sm leading-none shrink-0">{m.home_team_flag ?? ''}</span>
                </div>

                <!-- Prediction -->
                <div class="flex items-center gap-1 shrink-0">
                  {#if m.prediction}
                    <span class="w-7 h-7 flex items-center justify-center text-sm font-black border border-[#E0E0E0] text-[#081B6A] bg-[#F6F6F6]">
                      {m.prediction.home_score}
                    </span>
                    <span class="text-[10px] text-[#9CA3AF] font-bold">–</span>
                    <span class="w-7 h-7 flex items-center justify-center text-sm font-black border border-[#E0E0E0] text-[#081B6A] bg-[#F6F6F6]">
                      {m.prediction.away_score}
                    </span>
                  {:else}
                    <span class="text-[10px] text-[#BDBDBD] italic px-1">—</span>
                  {/if}
                </div>

                <!-- Away team -->
                <div class="flex items-center gap-1 flex-1 min-w-0">
                  <span class="text-sm leading-none shrink-0">{m.away_team_flag ?? ''}</span>
                  <span class="text-xs font-semibold text-[#081B6A] truncate">{m.away_team}</span>
                </div>
              </div>
            {/each}
          </div>
        {/each}
      {/each}
    {/if}
  </div>
</div>
