<script lang="ts">
  import Nav from '../Components/Nav.svelte';
  import MatchCard from '../Components/MatchCard.svelte';
  import type { Match } from '../lib/types';
  import { router } from '@inertiajs/svelte';

  let {
    matches,
    stages,
    filters,
  }: {
    matches: Match[];
    stages: string[];
    filters: { stage: string; search: string };
  } = $props();

  let searchInput = $state(filters.search ?? '');
  let selectedStage = $state(filters.stage ?? 'all');

  let searchTimer: ReturnType<typeof setTimeout>;

  function applyFilters() {
    router.get('/matches', {
      stage: selectedStage !== 'all' ? selectedStage : undefined,
      search: searchInput || undefined,
    }, { preserveState: true, replace: true });
  }

  function onSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
  }

  const stageLabels: Record<string, string> = {
    group: 'Grupos',
    round_of_32: 'Ronda de 32',
    round_of_16: 'Octavos',
    quarter: 'Cuartos',
    semi: 'Semifinales',
    final: 'Final',
  };

  const live = $derived(matches.filter(m => m.status === 'live'));
  const grouped = $derived(
    Object.entries(
      matches.reduce<Record<string, Match[]>>((acc, m) => {
        const key = m.group_name ?? m.stage;
        (acc[key] ??= []).push(m);
        return acc;
      }, {})
    ).sort(([a], [b]) => a.localeCompare(b))
  );
</script>

<svelte:head>
  <title>Partidos — Quiniela 2026</title>
</svelte:head>

<div class="min-h-screen bg-[#F6F6F6]">
  <Nav />

  <!-- Page header -->
  <div class="bg-white border-b border-[#E0E0E0]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
      <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1">Copa del Mundo 2026</p>
      <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <h1 class="text-3xl font-black text-[#081B6A]">Partidos</h1>
        {#if live.length > 0}
          <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
            <span class="text-sm font-bold text-red-500">{live.length} partido{live.length > 1 ? 's' : ''} en vivo</span>
          </div>
        {/if}
      </div>
    </div>
  </div>

  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-3 mb-8">
      <input
        type="text"
        placeholder="Buscar equipo..."
        bind:value={searchInput}
        oninput={onSearch}
        class="flex-1 border border-[#E0E0E0] bg-white px-4 py-2.5 text-sm text-[#081B6A] placeholder-[#9CA3AF] focus:outline-none focus:border-[#3554FF]"
      />
      <div class="flex gap-2 flex-wrap">
        <button
          onclick={() => { selectedStage = 'all'; applyFilters(); }}
          class="px-4 py-2 text-xs font-bold tracking-wide border transition-colors
            {selectedStage === 'all'
              ? 'bg-[#081B6A] text-white border-[#081B6A]'
              : 'bg-white text-[#081B6A] border-[#E0E0E0] hover:border-[#081B6A]'}"
        >
          Todos
        </button>
        {#each stages as stage}
          <button
            onclick={() => { selectedStage = stage; applyFilters(); }}
            class="px-4 py-2 text-xs font-bold tracking-wide border transition-colors
              {selectedStage === stage
                ? 'bg-[#081B6A] text-white border-[#081B6A]'
                : 'bg-white text-[#081B6A] border-[#E0E0E0] hover:border-[#081B6A]'}"
          >
            {stageLabels[stage] ?? stage}
          </button>
        {/each}
      </div>
    </div>

    <!-- Live matches first -->
    {#if live.length > 0 && selectedStage === 'all' && !searchInput}
      <div class="mb-10">
        <div class="flex items-center gap-3 mb-4">
          <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
          <p class="text-[10px] font-bold tracking-widest uppercase text-red-500">En vivo ahora</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
          {#each live as match}
            <MatchCard {match} />
          {/each}
        </div>
      </div>
    {/if}

    <!-- Grouped matches -->
    {#if matches.length === 0}
      <div class="bg-white border border-[#E0E0E0] p-16 text-center">
        <p class="text-[#9CA3AF] font-medium">No se encontraron partidos</p>
      </div>
    {:else}
      {#each grouped as [groupName, groupMatches]}
        <div class="mb-10">
          <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-4 flex items-center gap-3">
            <span class="h-px flex-1 bg-[#E0E0E0]"></span>
            {groupName}
            <span class="h-px flex-1 bg-[#E0E0E0]"></span>
          </p>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
            {#each groupMatches.filter(m => m.status !== 'live' || selectedStage !== 'all' || !!searchInput) as match}
              <MatchCard {match} />
            {/each}
          </div>
        </div>
      {/each}
    {/if}
  </div>
</div>
