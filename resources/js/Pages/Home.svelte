<script lang="ts">
  import Nav from '../Components/Nav.svelte';
  import MatchCard from '../Components/MatchCard.svelte';
  import LeaderboardRow from '../Components/LeaderboardRow.svelte';
  import type { Match, Participant } from '../lib/types';

  let {
    top_participants,
    featured_matches,
    has_live,
    participants_count,
  }: {
    top_participants: Participant[];
    featured_matches: Match[];
    has_live: boolean;
    participants_count: number;
  } = $props();

  const podiumOrder = [2, 1, 3];
</script>

<svelte:head>
  <title>Quiniela 2026 — Copa del Mundo</title>
  <meta name="description" content="Sigue las predicciones del Mundial 2026 en tiempo real." />
</svelte:head>

<div class="min-h-screen bg-[#F6F6F6]">
  <Nav />

  <!-- Hero -->
  <section class="bg-[#081B6A] text-white overflow-hidden relative">
    <div class="absolute inset-0 opacity-5">
      <div class="absolute top-0 right-0 w-96 h-96 border border-white rounded-full -translate-y-1/2 translate-x-1/2"></div>
      <div class="absolute bottom-0 left-0 w-64 h-64 border border-white rounded-full translate-y-1/2 -translate-x-1/2"></div>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12 relative">
      <div class="max-w-2xl">
        <p class="text-[#3554FF] text-xs font-bold tracking-widest uppercase mb-4">FIFA World Cup 2026</p>
        <h1 class="text-4xl md:text-6xl font-black leading-none tracking-tight mb-6">
          Quiniela<br />
          <span class="text-[#3554FF]">2026</span>
        </h1>
        <p class="text-white/60 text-lg leading-relaxed">
          {participants_count} participantes. 72 partidos. Cada gol importa.
        </p>
      </div>
    </div>
  </section>

  <!-- Top 3 Podium -->
  {#if top_participants.length >= 3}
    <section class="bg-white border-b border-[#E0E0E0]">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-8">Podio actual</p>
        <div class="flex items-end justify-center gap-4 md:gap-8">
          {#each podiumOrder as pos}
            {@const p = top_participants[pos - 1]}
            {#if p}
              <div
                class="flex flex-col items-center text-center
                  {pos === 1 ? 'order-2' : pos === 2 ? 'order-1' : 'order-3'}"
              >
                <div
                  class="flex items-center justify-center font-black text-sm mb-3
                    {pos === 1 ? 'w-20 h-20 bg-[#081B6A] text-white text-xl' :
                     pos === 2 ? 'w-16 h-16 bg-[#F0F0F0] text-[#081B6A]' :
                     'w-14 h-14 bg-[#F0F0F0] text-[#081B6A]'}"
                >
                  {pos === 1 ? '🥇' : pos === 2 ? '🥈' : '🥉'}
                </div>
                <p class="font-bold text-[#081B6A] text-sm max-w-[100px] leading-tight">{p.name.split(' ')[0]}</p>
                <p class="text-[#3554FF] font-black text-lg">{p.total_points} pts</p>
              </div>
            {/if}
          {/each}
        </div>
      </div>
    </section>
  {/if}

  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 grid grid-cols-1 lg:grid-cols-3 gap-12">
    <!-- Featured matches -->
    <div class="lg:col-span-2">
      <div class="flex items-center justify-between mb-6">
        <div>
          <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF]">
            {has_live ? 'En vivo' : 'Próximos partidos'}
          </p>
          <h2 class="text-xl font-black text-[#081B6A]">Partidos destacados</h2>
        </div>
        <a href="/matches" class="text-xs font-bold text-[#3554FF] hover:underline tracking-wide">Ver todos →</a>
      </div>

      {#if featured_matches.length > 0}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          {#each featured_matches as match}
            <MatchCard {match} />
          {/each}
        </div>
      {:else}
        <div class="bg-white border border-[#E0E0E0] p-12 text-center">
          <p class="text-[#9CA3AF] font-medium">Sin partidos programados</p>
        </div>
      {/if}
    </div>

    <!-- Leaderboard sidebar -->
    <div>
      <div class="flex items-center justify-between mb-6">
        <div>
          <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF]">Top 10</p>
          <h2 class="text-xl font-black text-[#081B6A]">Clasificación</h2>
        </div>
        <a href="/leaderboard" class="text-xs font-bold text-[#3554FF] hover:underline tracking-wide">Completa →</a>
      </div>

      <div class="bg-white border border-[#E0E0E0] divide-y divide-[#F0F0F0]">
        {#each top_participants as participant}
          <LeaderboardRow {participant} />
        {/each}
      </div>
    </div>
  </div>
</div>
