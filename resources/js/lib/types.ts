export type MatchStatus = 'notstarted' | 'live' | 'finished';
export type Stage = 'group' | 'round_of_32' | 'round_of_16' | 'quarter' | 'semi' | 'third_place' | 'final';

export interface Match {
    id: number;
    correlativo: number;
    home_team: string;
    away_team: string;
    home_team_flag: string | null;
    away_team_flag: string | null;
    home_score: number | null;
    away_score: number | null;
    home_score_ht: number | null;
    away_score_ht: number | null;
    match_date: string;
    stage: string;
    group_name: string | null;
    venue: string | null;
    status: MatchStatus;
    minute: number | null;
}

export interface MatchWithPrediction extends Match {
    closes_at: string;
    prediction: { home_score: number; away_score: number } | null;
}

export interface Participant {
    id: number;
    name: string;
    slug: string;
    rank: number;
    total_points: number;
    exact_score_count: number;
    correct_winner_count: number;
    near_prediction_count?: number;
    matches_predicted?: number;
}

export interface MatchRanking {
    rank: number;
    participant_name: string;
    participant_slug: string;
    home_score: number;
    away_score: number;
    score_diff: number;
    is_exact: boolean;
    correct_winner: boolean;
    points: number;
}

export interface PredictionRow {
    correlativo: number;
    home_team: string;
    away_team: string;
    group_name: string | null;
    match_date: string;
    status: MatchStatus;
    stage: string;
    pred_home: number;
    pred_away: number;
    actual_home: number | null;
    actual_away: number | null;
    is_exact: boolean | null;
    correct_winner: boolean | null;
    points: number;
}

export interface PodiumEntry {
    id: number;
    name: string;
    slug: string;
    rank: number;
    total_points: number;
    exact_score_count: number;
    predictions: PredictionRow[];
}

export interface MatchStatistics {
    total_predictions: number;
    exact_count: number;
    most_common: string | null;
    home_win_pct: number;
    draw_pct: number;
    away_win_pct: number;
}
