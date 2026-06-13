<script lang="ts">
  import Nav from '../Components/Nav.svelte';
  import type { Participant } from '../lib/types';
  import { router } from '@inertiajs/svelte';

  let {
    entries,
    meta,
    filters,
  }: {
    entries: Participant[];
    meta: { current_page: number; last_page: number; per_page: number; total: number };
    filters: { search: string; sort: string; direction: string };
  } = $props();

  let searchInput = $state(filters.search ?? '');
  let sort = $state(filters.sort ?? 'rank');
  let direction = $state(filters.direction ?? 'asc');

  let searchTimer: ReturnType<typeof setTimeout>;

  function navigate(params: Record<string, string | number | undefined>) {
    router.get('/leaderboard', params, { preserveState: true, replace: true });
  }

  function onSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => navigate({ search: searchInput || undefined, sort, direction }), 400);
  }

  function toggleSort(col: string) {
    if (sort === col) {
      direction = direction === 'asc' ? 'desc' : 'asc';
    } else {
      sort = col;
      direction = col === 'rank' ? 'asc' : 'desc';
    }
    navigate({ search: searchInput || undefined, sort, direction });
  }

  function goPage(page: number) {
    navigate({ search: searchInput || undefined, sort, direction, page });
  }

  const rankColors: Record<number, string> = {
    1: 'text-yellow-600 bg-yellow-50',
    2: 'text-slate-500 bg-slate-50',
    3: 'text-orange-700 bg-orange-50',
  };

  const columns: { key: string; label: string; align: string }[] = [
    { key: 'rank', label: 'Pos.', align: 'text-center' },
    { key: 'name', label: 'Participante', align: 'text-left' },
    { key: 'total_points', label: 'Puntos', align: 'text-right' },
    { key: 'exact_score_count', label: 'Exactos', align: 'text-right' },
    { key: 'correct_winner_count', label: 'Ganador', align: 'text-right' },
    { key: 'matches_predicted', label: 'Partidos', align: 'text-right' },
  ];
</script>

<svelte:head>
  <title>Clasificación — Quiniela 2026</title>
</svelte:head>

<div class="min-h-screen bg-[#F6F6F6]">
  <Nav />

  <div class="bg-white border-b border-[#E0E0E0]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1">Copa del Mundo 2026</p>
      <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <h1 class="text-3xl font-black text-[#081B6A]">Clasificación</h1>
        <p class="text-sm text-[#9CA3AF]">{meta.total} participantes</p>
      </div>
    </div>
  </div>

  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Search -->
    <div class="mb-6">
      <input
        type="text"
        placeholder="Buscar participante..."
        bind:value={searchInput}
        oninput={onSearch}
        class="w-full max-w-md border border-[#E0E0E0] bg-white px-4 py-2.5 text-sm text-[#081B6A] placeholder-[#9CA3AF] focus:outline-none focus:border-[#3554FF]"
      />
    </div>

    <!-- Table -->
    <div class="bg-white border border-[#E0E0E0] overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-[#E0E0E0]">
            {#each columns as col}
              {#if col.key !== 'name'}
                <th
                  class="px-4 py-3 text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] cursor-pointer hover:text-[#081B6A] transition-colors {col.align} hidden sm:table-cell
                    {col.key === 'rank' || col.key === 'total_points' ? 'table-cell' : ''}"
                  onclick={() => col.key !== 'name' && toggleSort(col.key)}
                >
                  {col.label}
                  {#if sort === col.key}
                    <span>{direction === 'asc' ? '↑' : '↓'}</span>
                  {/if}
                </th>
              {:else}
                <th class="px-4 py-3 text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] text-left">
                  {col.label}
                </th>
              {/if}
            {/each}
          </tr>
        </thead>
        <tbody class="divide-y divide-[#F0F0F0]">
          {#each entries as entry}
            <tr class="hover:bg-[#FAFAFA] transition-colors">
              <td class="px-4 py-3 text-center">
                <span class="inline-flex items-center justify-center w-7 h-7 text-xs font-black rounded-full
                  {rankColors[entry.rank] ?? 'text-[#6B7280] bg-[#F6F6F6]'}">
                  {entry.rank}
                </span>
              </td>
              <td class="px-4 py-3">
                <a
                  href="/participants/{entry.slug}"
                  class="font-semibold text-[#081B6A] hover:text-[#3554FF] transition-colors"
                >
                  {entry.name}
                </a>
              </td>
              <td class="px-4 py-3 text-right font-black text-[#3554FF] text-base">{entry.total_points}</td>
              <td class="px-4 py-3 text-right font-semibold text-[#081B6A] hidden sm:table-cell">{entry.exact_score_count}</td>
              <td class="px-4 py-3 text-right font-semibold text-[#081B6A] hidden sm:table-cell">{entry.correct_winner_count}</td>
              <td class="px-4 py-3 text-right text-[#6B7280] hidden sm:table-cell">{entry.matches_predicted ?? 72}</td>
            </tr>
          {:else}
            <tr>
              <td colspan="6" class="px-4 py-12 text-center text-[#9CA3AF]">
                No se encontraron participantes
              </td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    {#if meta.last_page > 1}
      <div class="flex items-center justify-between mt-6">
        <p class="text-sm text-[#9CA3AF]">
          Página {meta.current_page} de {meta.last_page}
        </p>
        <div class="flex gap-2">
          <button
            disabled={meta.current_page <= 1}
            onclick={() => goPage(meta.current_page - 1)}
            class="px-4 py-2 text-sm font-bold border border-[#E0E0E0] bg-white text-[#081B6A] disabled:opacity-40 hover:border-[#081B6A] transition-colors"
          >
            ← Anterior
          </button>
          <button
            disabled={meta.current_page >= meta.last_page}
            onclick={() => goPage(meta.current_page + 1)}
            class="px-4 py-2 text-sm font-bold border border-[#E0E0E0] bg-white text-[#081B6A] disabled:opacity-40 hover:border-[#081B6A] transition-colors"
          >
            Siguiente →
          </button>
        </div>
      </div>
    {/if}
  </div>
</div>
