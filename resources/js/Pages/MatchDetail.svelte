<script lang="ts">
  import { onMount } from 'svelte';
  import { router } from '@inertiajs/svelte';
  import Nav from '../Components/Nav.svelte';
  import type { Match, MatchRanking, MatchStatistics } from '../lib/types';
  import { formatDate, statusLabel, flagEmoji } from '../lib/utils';

  type PredRow = {
    participant_name: string;
    participant_slug: string;
    leaderboard_rank: number | null;
    pred_home: number;
    pred_away: number;
    is_live_matching: boolean;
  };

  type Goal = {
    team: 'home' | 'away';
    scorer: string;
    minute: string;
    is_penalty: boolean;
    is_own_goal: boolean;
    is_extra_time: boolean;
  };

  let {
    match,
    live_matching,
    all_predictions,
    podium,
    exact_predictions,
    near_predictions,
    statistics,
  }: {
    match: Match & {
      last_synced_at: string | null;
      home_score_90: number | null;
      away_score_90: number | null;
      goals: Goal[];
    };
    live_matching: PredRow[];
    all_predictions: PredRow[];
    podium: MatchRanking[];
    exact_predictions: MatchRanking[];
    near_predictions: MatchRanking[];
    statistics: MatchStatistics;
  } = $props();

  // Did this match go to extra time? (90-min score set and differs from final score)
  const hadExtraTime = match.home_score_90 !== null && (
    match.home_score_90 !== match.home_score || match.away_score_90 !== match.away_score
  );

  // Score used for prediction comparison (90-min for knockout; same as final for group)
  const scoringHome = match.home_score_90 ?? match.home_score;
  const scoringAway = match.away_score_90 ?? match.away_score;

  const hasScore = match.home_score !== null && match.away_score !== null;
  const isLive   = match.status === 'live';
  const isFinished = match.status === 'finished';

  const rankStyle = (rank: number) =>
    rank === 1 ? 'bg-yellow-400 text-yellow-900' :
    rank === 2 ? 'bg-slate-300 text-slate-800' :
    rank === 3 ? 'bg-orange-400 text-orange-900' :
    'bg-[#F0F0F0] text-[#6B7280]';

  function timeAgo(iso: string | null): string {
    if (!iso) return 'Desconocido';
    const diff = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
    if (diff < 60) return `hace ${diff}s`;
    if (diff < 3600) return `hace ${Math.floor(diff / 60)}min`;
    return `hace ${Math.floor(diff / 3600)}h`;
  }

  let syncedAgo = $state(timeAgo(match.last_synced_at));

  onMount(() => {
    const labelTimer = setInterval(() => {
      syncedAgo = timeAgo(match.last_synced_at);
    }, 30_000);

    let reloadTimer: ReturnType<typeof setInterval> | undefined;
    if (isLive) {
      reloadTimer = setInterval(() => {
        router.reload({ only: ['match', 'live_matching', 'all_predictions', 'statistics'] });
      }, 5 * 60 * 1000);
    }

    return () => {
      clearInterval(labelTimer);
      if (reloadTimer) clearInterval(reloadTimer);
    };
  });
</script>

<svelte:head>
  <title>{match.home_team} vs {match.away_team} — Quiniela 2026</title>
</svelte:head>

<div class="min-h-screen bg-[#F6F6F6]">
  <Nav />

  <!-- Hero -->
  <section class="bg-[#081B6A] text-white py-5 relative overflow-hidden">
    <div class="absolute inset-0 opacity-5">
      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] border border-white rounded-full"></div>
    </div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative">
      <p class="text-[10px] font-bold tracking-widest uppercase text-[#3554FF] mb-4">
        {match.group_name ?? match.stage}
        {#if match.venue} · {match.venue}{/if}
      </p>

      <div class="flex items-center justify-center gap-8 md:gap-16">
        <div class="text-center flex-1">
          <div class="text-3xl md:text-4xl mb-2">{match.home_team_flag ?? flagEmoji(match.home_team)}</div>
          <p class="font-black text-sm md:text-base leading-tight">{match.home_team}</p>
        </div>

        <div class="text-center">
          {#if hasScore}
            <div class="text-4xl md:text-6xl font-black tracking-tighter leading-none">
              {match.home_score}<span class="text-[#3554FF] mx-2 text-2xl md:text-4xl">–</span>{match.away_score}
            </div>
            {#if hadExtraTime}
              <p class="text-amber-400 text-xs font-bold mt-1 tracking-wide">
                90 min: {match.home_score_90}–{match.away_score_90} · Prórroga
              </p>
            {:else if match.home_score_ht !== null}
              <p class="text-white/40 text-xs mt-1">MT: {match.home_score_ht}–{match.away_score_ht}</p>
            {/if}
          {:else}
            <div class="text-2xl font-black text-white/30">VS</div>
            <p class="text-white/60 text-xs mt-1">{formatDate(match.match_date)}</p>
          {/if}

          <div class="mt-4 flex flex-col items-center gap-2">
            {#if isLive}
              <span class="inline-flex items-center gap-2 px-3 py-1 bg-red-500/20 text-red-400 text-xs font-bold tracking-widest uppercase">
                <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                {statusLabel(match.status, match.minute)}
              </span>
            {:else}
              <span class="inline-flex items-center px-3 py-1 bg-white/10 text-white/60 text-xs font-bold tracking-widest uppercase">
                {statusLabel(match.status)}
              </span>
            {/if}
            {#if match.last_synced_at}
              <span class="text-white/30 text-[10px] tracking-wide">Actualizado {syncedAgo}</span>
            {/if}
          </div>
        </div>

        <div class="text-center flex-1">
          <div class="text-3xl md:text-4xl mb-2">{match.away_team_flag ?? flagEmoji(match.away_team)}</div>
          <p class="font-black text-sm md:text-base leading-tight">{match.away_team}</p>
        </div>
      </div>

      <!-- Goal scorers -->
      {#if hasScore && match.goals.length > 0}
        {@const homeGoals = match.goals.filter(g => g.team === 'home')}
        {@const awayGoals = match.goals.filter(g => g.team === 'away')}
        <div class="mt-5 flex justify-center gap-12 md:gap-24 text-xs text-white/70">
          <div class="text-right space-y-0.5 min-w-0 max-w-[180px]">
            {#each homeGoals as g}
              <p class="truncate">
                {g.scorer}
                <span class="text-white/40">{g.minute}{g.is_penalty ? ' (p)' : g.is_own_goal ? ' (OG)' : ''}</span>
              </p>
            {/each}
          </div>
          <div class="w-px bg-white/10 shrink-0"></div>
          <div class="text-left space-y-0.5 min-w-0 max-w-[180px]">
            {#each awayGoals as g}
              <p class="truncate">
                <span class="text-white/40">{g.minute}{g.is_penalty ? ' (p)' : g.is_own_goal ? ' (OG)' : ''}</span>
                {g.scorer}
              </p>
            {/each}
          </div>
        </div>
      {/if}
    </div>
  </section>

  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

      <!-- Left column -->
      <div class="lg:col-span-2 space-y-10">

        <!-- Live: who currently matches the score -->
        {#if isLive && hasScore}
          <div>
            <div class="flex items-center gap-3 mb-4">
              <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
              <p class="text-[10px] font-bold tracking-widest uppercase text-red-500">
                Acertando en vivo — {match.home_score}–{match.away_score}
              </p>
              <span class="text-[10px] text-[#9CA3AF]">({live_matching.length})</span>
            </div>
            {#if live_matching.length > 0}
              <div class="bg-red-50 border border-red-200">
                {#each live_matching as entry}
                  <div class="flex items-center gap-3 px-4 py-3 border-b border-red-100 last:border-0">
                    <span class="w-2 h-2 rounded-full bg-red-400 shrink-0"></span>
                    <a href="/participants/{entry.participant_slug}"
                      class="flex-1 font-semibold text-sm text-[#081B6A] hover:text-[#3554FF] transition-colors">
                      {entry.participant_name}
                    </a>
                    <span class="font-black text-sm text-red-600 font-mono">{match.home_score}–{match.away_score}</span>
                  </div>
                {/each}
              </div>
            {:else}
              <div class="bg-white border border-[#E0E0E0] px-5 py-6 text-center">
                <p class="text-[#9CA3AF] text-sm">Nadie tiene este marcador por ahora.</p>
              </div>
            {/if}
          </div>
        {/if}

        <!-- Finished: podium -->
        {#if podium.length > 0}
          <div>
            <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-4">Podio del partido</p>
            <div class="bg-white border border-[#E0E0E0]">
              {#each podium as entry}
                <div class="flex items-center gap-4 px-4 py-3 border-b border-[#F0F0F0] last:border-0">
                  <span class="inline-flex items-center justify-center w-8 h-8 text-xs font-black {rankStyle(entry.rank)}">
                    {entry.rank}
                  </span>
                  <div class="flex-1 min-w-0">
                    <a href="/participants/{entry.participant_slug}"
                      class="font-semibold text-sm text-[#081B6A] hover:text-[#3554FF] transition-colors truncate block">
                      {entry.participant_name}
                    </a>
                    {#if entry.is_exact}
                      <p class="text-[10px] text-emerald-600 font-bold">Exacto ✓</p>
                    {/if}
                  </div>
                  <div class="text-right shrink-0">
                    <p class="font-black text-[#081B6A] font-mono">{entry.home_score}–{entry.away_score}</p>
                    <p class="text-[10px] text-[#9CA3AF]">{entry.points} pts</p>
                  </div>
                </div>
              {/each}
            </div>
          </div>
        {/if}

        <!-- Exact predictions (finished only) -->
        {#if isFinished && exact_predictions.length > 0}
          <div>
            <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-2">
              Marcador exacto <span class="ml-2 text-emerald-600">({exact_predictions.length})</span>
            </p>
            <div class="bg-emerald-50 border border-emerald-200">
              {#each exact_predictions as entry}
                <div class="flex items-center gap-4 px-4 py-3 border-b border-emerald-100 last:border-0">
                  <div class="w-6 h-6 bg-emerald-500 flex items-center justify-center shrink-0">
                    <span class="text-white text-xs">✓</span>
                  </div>
                  <a href="/participants/{entry.participant_slug}"
                    class="flex-1 font-semibold text-sm text-[#081B6A] hover:text-[#3554FF] transition-colors">
                    {entry.participant_name}
                  </a>
                  <p class="font-black text-emerald-700 font-mono">{entry.home_score}–{entry.away_score}</p>
                </div>
              {/each}
            </div>
          </div>
        {/if}

        <!-- All predictions table (scheduled + live; also shown collapsed for finished) -->
        {#if all_predictions.length > 0}
          <div>
            <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-3">
              {isFinished ? 'Todos los pronósticos' : 'Pronósticos'} · {all_predictions.length}
            </p>
            <div class="bg-white border border-[#E0E0E0] overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="border-b border-[#E0E0E0]">
                    <th class="px-4 py-3 text-left text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] w-10">Pos.</th>
                    <th class="px-4 py-3 text-left text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF]">Participante</th>
                    <th class="px-4 py-3 text-center text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF]">Pronóstico</th>
                    {#if isFinished}
                      <th class="px-4 py-3 text-center text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] w-8"></th>
                    {/if}
                  </tr>
                </thead>
                <tbody class="divide-y divide-[#F0F0F0]">
                  {#each all_predictions as row}
                    {@const exact = isFinished && row.pred_home === match.home_score && row.pred_away === match.away_score}
                    <tr class="{row.is_live_matching ? 'bg-red-50' : exact ? 'bg-emerald-50' : 'hover:bg-[#FAFAFA]'} transition-colors">
                      <td class="px-4 py-2.5 text-center">
                        {#if row.leaderboard_rank}
                          <span class="inline-flex items-center justify-center w-6 h-6 text-[10px] font-black rounded-full
                            {rankStyle(row.leaderboard_rank)}">
                            {row.leaderboard_rank}
                          </span>
                        {:else}
                          <span class="text-[#D1D5DB] text-xs">—</span>
                        {/if}
                      </td>
                      <td class="px-4 py-2.5">
                        <a href="/participants/{row.participant_slug}"
                          class="font-semibold text-[#081B6A] hover:text-[#3554FF] transition-colors">
                          {row.participant_name}
                        </a>
                      </td>
                      <td class="px-4 py-2.5 text-center font-mono font-black
                        {row.is_live_matching ? 'text-red-600' : exact ? 'text-emerald-700' : 'text-[#081B6A]'}">
                        {row.pred_home}–{row.pred_away}
                      </td>
                      {#if isFinished}
                        <td class="px-4 py-2.5 text-center text-sm">
                          {#if exact}
                            <span class="text-emerald-600 font-black">✓</span>
                          {:else}
                            <span class="text-[#D1D5DB]">✗</span>
                          {/if}
                        </td>
                      {/if}
                    </tr>
                  {/each}
                </tbody>
              </table>
            </div>
          </div>
        {/if}

      </div>

      <!-- Right column: statistics -->
      <div class="space-y-6">
        <div>
          <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-4">Estadísticas</p>
          <div class="space-y-3">
            <div class="bg-white border border-[#E0E0E0] p-4">
              <p class="text-[10px] text-[#9CA3AF] tracking-widest uppercase">Total pronósticos</p>
              <p class="text-3xl font-black text-[#081B6A]">{statistics.total_predictions}</p>
            </div>

            {#if isFinished}
              <div class="bg-white border border-[#E0E0E0] p-4">
                <p class="text-[10px] text-[#9CA3AF] tracking-widest uppercase">Marcador exacto</p>
                <p class="text-3xl font-black text-emerald-600">{statistics.exact_count}</p>
              </div>
            {/if}

            {#if statistics.most_common}
              <div class="bg-white border border-[#E0E0E0] p-4">
                <p class="text-[10px] text-[#9CA3AF] tracking-widest uppercase">Pronóstico más común</p>
                <p class="text-3xl font-black text-[#3554FF]">{statistics.most_common.replace('-', ' – ')}</p>
              </div>
            {/if}

            <div class="bg-white border border-[#E0E0E0] p-4 space-y-3">
              <p class="text-[10px] text-[#9CA3AF] tracking-widest uppercase mb-3">Pronóstico de resultado</p>
              <div>
                <div class="flex justify-between text-xs font-semibold text-[#081B6A] mb-1">
                  <span>{match.home_team.split(' ')[0]}</span>
                  <span>{statistics.home_win_pct}%</span>
                </div>
                <div class="h-2 bg-[#F0F0F0]">
                  <div class="h-full bg-[#3554FF]" style="width:{statistics.home_win_pct}%"></div>
                </div>
              </div>
              <div>
                <div class="flex justify-between text-xs font-semibold text-[#081B6A] mb-1">
                  <span>Empate</span>
                  <span>{statistics.draw_pct}%</span>
                </div>
                <div class="h-2 bg-[#F0F0F0]">
                  <div class="h-full bg-[#9CA3AF]" style="width:{statistics.draw_pct}%"></div>
                </div>
              </div>
              <div>
                <div class="flex justify-between text-xs font-semibold text-[#081B6A] mb-1">
                  <span>{match.away_team.split(' ')[0]}</span>
                  <span>{statistics.away_win_pct}%</span>
                </div>
                <div class="h-2 bg-[#F0F0F0]">
                  <div class="h-full bg-[#081B6A]" style="width:{statistics.away_win_pct}%"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <a href="/matches" class="block text-center text-sm font-bold text-[#3554FF] hover:underline">
          ← Ver todos los partidos
        </a>
      </div>

    </div>
  </div>
</div>
