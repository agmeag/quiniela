<script lang="ts">
  import Nav from '../Components/Nav.svelte';
  import MatchCard from '../Components/MatchCard.svelte';
  import type { Match } from '../lib/types';
  import { router } from '@inertiajs/svelte';
  import { formatDate } from '../lib/utils';

  let {
    matches,
    stages,
    filters,
    today_key,
    tomorrow_key,
  }: {
    matches: Match[];
    stages: string[];
    filters: { stage: string; search: string };
    today_key: string;
    tomorrow_key: string;
  } = $props();

  let searchInput = $state(filters.search ?? '');
  let selectedStage = $state(filters.stage ?? 'all');
  let viewMode = $state<'groups' | 'dates'>('groups');

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

  // match_date is "YYYY-MM-DD HH:MM:SS" in El Salvador time from the backend
  function dateKey(svStr: string): string {
    return svStr.slice(0, 10);
  }

  function dateLabel(key: string): string {
    if (key === today_key) return 'Hoy';
    if (key === tomorrow_key) return 'Mañana';
    return formatDate(key + ' 12:00:00').split('·')[0].trim();
  }

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

  const upcomingByDate = $derived(() => {
    const upcoming = matches.filter(m => m.status === 'notstarted' || m.status === 'live');
    const byDate: Record<string, Match[]> = {};
    for (const m of upcoming) {
      if (!m.match_date) continue;
      const key = dateKey(m.match_date);
      (byDate[key] ??= []).push(m);
    }
    return Object.entries(byDate).sort(([a], [b]) => a.localeCompare(b));
  });
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
    <!-- View toggle + filters -->
    <div class="flex flex-col gap-4 mb-8">
      <!-- View mode toggle -->
      <div class="flex gap-0 border border-[#E0E0E0] w-fit">
        <button
          onclick={() => viewMode = 'groups'}
          class="px-4 py-2 text-xs font-bold tracking-wide transition-colors
            {viewMode === 'groups'
              ? 'bg-[#081B6A] text-white'
              : 'bg-white text-[#081B6A] hover:bg-[#F6F6F6]'}"
        >
          Por grupo
        </button>
        <button
          onclick={() => { viewMode = 'dates'; selectedStage = 'all'; applyFilters(); }}
          class="px-4 py-2 text-xs font-bold tracking-wide border-l border-[#E0E0E0] transition-colors
            {viewMode === 'dates'
              ? 'bg-[#081B6A] text-white'
              : 'bg-white text-[#081B6A] hover:bg-[#F6F6F6]'}"
        >
          Por fecha
        </button>
      </div>

      <!-- Stage filters (only in groups view) -->
      {#if viewMode === 'groups'}
        <div class="flex flex-col sm:flex-row gap-3">
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
      {/if}
    </div>

    {#if viewMode === 'dates'}
      <!-- ── By date view: upcoming + live only ─────────────────── -->
      {#if upcomingByDate().length === 0}
        <div class="bg-white border border-[#E0E0E0] p-16 text-center">
          <p class="text-[#9CA3AF] font-medium">No hay partidos próximos</p>
        </div>
      {:else}
        {#each upcomingByDate() as [key, dayMatches]}
          <div class="mb-10">
            <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-4 flex items-center gap-3">
              <span class="h-px flex-1 bg-[#E0E0E0]"></span>
              {dateLabel(key)}
              <span class="text-[#CDCDCD] font-normal normal-case tracking-normal">— {dayMatches.length} partido{dayMatches.length > 1 ? 's' : ''}</span>
              <span class="h-px flex-1 bg-[#E0E0E0]"></span>
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
              {#each dayMatches as match}
                <MatchCard {match} />
              {/each}
            </div>
          </div>
        {/each}
      {/if}
    {:else}
      <!-- ── By group view ──────────────────────────────────────── -->

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
    {/if}
  </div>
</div>
