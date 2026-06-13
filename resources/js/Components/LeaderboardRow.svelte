<script lang="ts">
  import type { Participant } from '../lib/types';

  let {
    participant,
    highlight = false,
  }: { participant: Participant; highlight?: boolean } = $props();

  const rankColors: Record<number, string> = {
    1: 'text-yellow-600 bg-yellow-50',
    2: 'text-slate-500 bg-slate-50',
    3: 'text-orange-600 bg-orange-50',
  };
</script>

<div
  class="flex items-center gap-4 px-4 py-3 border-b border-[#F0F0F0] last:border-0 transition-colors
    {highlight ? 'bg-blue-50/50' : 'bg-white hover:bg-[#FAFAFA]'}"
>
  <!-- Rank -->
  <div class="w-10 text-center">
    <span
      class="inline-flex items-center justify-center w-8 h-8 text-xs font-black rounded-full
        {rankColors[participant.rank] ?? 'text-[#6B7280] bg-[#F6F6F6]'}"
    >
      {participant.rank}
    </span>
  </div>

  <!-- Name -->
  <div class="flex-1 min-w-0">
    <p class="font-semibold text-sm text-[#081B6A] truncate">{participant.name}</p>
  </div>

  <!-- Stats -->
  <div class="flex items-center gap-6 text-right shrink-0">
    <div class="hidden sm:block">
      <p class="text-xs text-[#9CA3AF]">Exactos</p>
      <p class="font-bold text-sm text-[#081B6A]">{participant.exact_score_count}</p>
    </div>
    <div class="hidden md:block">
      <p class="text-xs text-[#9CA3AF]">Ganador</p>
      <p class="font-bold text-sm text-[#081B6A]">{participant.correct_winner_count}</p>
    </div>
    <div>
      <p class="text-xs text-[#9CA3AF]">Pts</p>
      <p class="font-black text-lg text-[#3554FF]">{participant.total_points}</p>
    </div>
  </div>
</div>
