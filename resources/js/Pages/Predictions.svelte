<script lang="ts">
  import { router, usePage } from '@inertiajs/svelte';
  import Nav from '../Components/Nav.svelte';
  import type { MatchWithPrediction } from '../lib/types';
  import { STAGE_LABELS, STAGE_ORDER, formatDate } from '../lib/utils';

  let {
    matches,
    stage,
    participant,
  }: {
    matches: MatchWithPrediction[];
    stage: string;
    participant: { name: string } | null;
  } = $props();

  const page = usePage();
  const flash = $derived((page.props as any).flash as { success?: string; error?: string } | undefined);

  // ── 10-second polling ─────────────────────────────────────────
  $effect(() => {
    const interval = setInterval(() => {
      router.reload({ only: ['matches'], preserveState: true });
    }, 10_000);
    return () => clearInterval(interval);
  });

  // ── Grouping ──────────────────────────────────────────────────
  const groupKeys = $derived(
    stage === 'group'
      ? [...new Set(matches.map(m => m.group_name ?? 'Sin grupo'))].sort()
      : ['']
  );

  function matchesForGroup(key: string): MatchWithPrediction[] {
    if (stage !== 'group') return matches;
    return matches.filter(m => (m.group_name ?? 'Sin grupo') === key);
  }

  // ── Stage selector ────────────────────────────────────────────
  function changeStage(newStage: string) {
    predictions = {};
    router.get('/mis-predicciones', { stage: newStage }, { preserveState: false });
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
      <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
        <div>
          <h1 class="text-3xl font-black text-[#081B6A]">Mis predicciones</h1>
          {#if participant}
            <p class="text-sm text-[#6B7280] mt-1">{participant.name}</p>
          {/if}
        </div>
        <select
          value={stage}
          onchange={(e) => changeStage((e.target as HTMLSelectElement).value)}
          class="border border-[#E0E0E0] bg-white px-4 py-2.5 text-sm font-semibold text-[#081B6A]
            focus:outline-none focus:border-[#3554FF] transition-colors self-start sm:self-auto"
        >
          {#each STAGE_ORDER as s}
            <option value={s}>{STAGE_LABELS[s]}</option>
          {/each}
        </select>
      </div>
    </div>
  </div>

  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Flash messages -->
    {#if flash?.success}
      <div class="mb-6 bg-emerald-50 border border-emerald-200 px-5 py-4 text-emerald-800 text-sm font-semibold">
        {flash.success}
      </div>
    {/if}
    {#if flash?.error}
      <div class="mb-6 bg-red-50 border border-red-200 px-5 py-4 text-red-800 text-sm font-semibold">
        {flash.error}
      </div>
    {/if}

    {#if !participant}
      <div class="bg-white border border-[#E0E0E0] p-10 text-center">
        <p class="text-[#6B7280] font-medium">Tu cuenta no está vinculada a un participante.</p>
        <p class="text-sm text-[#9CA3AF] mt-1">Contacta al administrador para resolver esto.</p>
      </div>

    {:else if matches.length === 0}
      <div class="bg-white border border-[#E0E0E0] p-10 text-center">
        <p class="text-[#9CA3AF] font-medium">No hay partidos en esta fase aún.</p>
      </div>

    {:else}
      <!-- Match groups -->
      {#each groupKeys as groupKey}
        {#if groupKey}
          <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-4 flex items-center gap-3">
            <span class="h-px flex-1 bg-[#E0E0E0]"></span>
            {groupKey}
            <span class="h-px flex-1 bg-[#E0E0E0]"></span>
          </p>
        {/if}

        <div class="space-y-3 mb-8">
          {#each matchesForGroup(groupKey) as m (m.id)}
            <div class="bg-white border border-[#E0E0E0]">

              <!-- Match header -->
              <div class="px-4 pt-4 pb-2 flex items-center justify-between gap-2 flex-wrap">
                <div class="flex items-center gap-2 min-w-0">
                  <span class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] shrink-0">
                    {formatDate(m.match_date)}
                  </span>
                  {#if m.status === 'live'}
                    <span class="flex items-center gap-1.5">
                      <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                      <span class="text-[10px] font-bold text-red-500">En vivo</span>
                    </span>
                  {:else if m.status === 'finished'}
                    <span class="inline-block px-1.5 py-0.5 text-[9px] font-black tracking-widest uppercase bg-[#081B6A] text-white">
                      Final
                    </span>
                    {#if m.home_score !== null}
                      <span class="text-[10px] font-bold text-[#081B6A]">{m.home_score}–{m.away_score}</span>
                    {/if}
                  {/if}
                </div>
              </div>

              <!-- Teams + score display -->
              <div class="px-4 pb-4 flex items-center gap-2 sm:gap-4">
                <!-- Home team -->
                <div class="flex items-center gap-1.5 flex-1 min-w-0">
                  <span class="text-xl leading-none shrink-0">{m.home_team_flag ?? ''}</span>
                  <span class="font-bold text-[#081B6A] text-sm truncate">{m.home_team}</span>
                </div>

                <!-- Prediction scores (read-only) -->
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
                    <span class="text-sm text-[#9CA3AF]">Sin pronóstico</span>
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
    {/if}
  </div>
</div>
