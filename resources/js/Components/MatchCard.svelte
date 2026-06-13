<script lang="ts">
  import type { Match } from '../lib/types';
  import { formatDate, statusLabel, flagEmoji } from '../lib/utils';

  let { match }: { match: Match } = $props();
</script>

<a
  href={`/matches/${match.id}`}
  class="group block bg-white border border-[#E0E0E0] hover:border-[#3554FF] transition-all duration-200 hover:shadow-sm"
>
  <!-- Status bar -->
  <div class="px-4 pt-3 pb-2 flex items-center justify-between border-b border-[#F0F0F0]">
    <span class="text-[10px] font-bold tracking-widest uppercase text-[#6B7280]">
      {match.group_name ?? match.stage}
    </span>
    <div class="flex items-center gap-1.5">
      {#if match.status === 'live'}
        <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
        <span class="text-[10px] font-bold tracking-widest uppercase text-red-500">
          {statusLabel(match.status, match.minute)}
        </span>
      {:else}
        <span class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF]">
          {statusLabel(match.status)}
        </span>
      {/if}
    </div>
  </div>

  <!-- Score area -->
  <div class="px-4 py-5">
    <div class="flex items-center justify-between gap-2">
      <!-- Home team -->
      <div class="flex-1 text-right">
        <div class="text-lg mb-1">{match.home_team_flag ?? flagEmoji(match.home_team)}</div>
        <div class="font-bold text-sm text-[#081B6A] leading-tight">{match.home_team}</div>
      </div>

      <!-- Score -->
      <div class="text-center px-4">
        {#if match.home_score !== null && match.away_score !== null}
          <div class="text-2xl font-black text-[#081B6A] tracking-tight leading-none">
            {match.home_score}<span class="text-[#3554FF] mx-1">–</span>{match.away_score}
          </div>
          {#if match.home_score_ht !== null}
            <div class="text-[10px] text-[#9CA3AF] mt-1">({match.home_score_ht}–{match.away_score_ht} ET)</div>
          {/if}
        {:else}
          <div class="text-sm font-bold text-[#9CA3AF]">{formatDate(match.match_date)}</div>
        {/if}
      </div>

      <!-- Away team -->
      <div class="flex-1 text-left">
        <div class="text-lg mb-1">{match.away_team_flag ?? flagEmoji(match.away_team)}</div>
        <div class="font-bold text-sm text-[#081B6A] leading-tight">{match.away_team}</div>
      </div>
    </div>
  </div>

  <!-- Venue footer -->
  {#if match.venue}
    <div class="px-4 pb-3">
      <p class="text-[10px] text-[#9CA3AF] tracking-wide truncate text-center">{match.venue}</p>
    </div>
  {/if}
</a>
