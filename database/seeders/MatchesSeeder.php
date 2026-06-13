<?php

namespace Database\Seeders;

use App\Models\WorldCupMatch;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MatchesSeeder extends Seeder
{
    public function run(): void
    {
        $matches = $this->getMatches();

        foreach ($matches as $match) {
            WorldCupMatch::updateOrCreate(
                ['correlativo' => $match['correlativo']],
                $match
            );
        }

        $this->command->info('Seeded ' . count($matches) . ' matches.');
    }

    private function getMatches(): array
    {
        return [
            ['correlativo' => 1, 'home_team' => 'México', 'away_team' => 'Sudáfrica', 'group_name' => 'Grupo A', 'match_date' => '2026-06-11 19:00:00', 'venue' => 'Ciudad de México', 'stage' => 'group'],
            ['correlativo' => 2, 'home_team' => 'Corea Del Sur', 'away_team' => 'Rep. Checa', 'group_name' => 'Grupo A', 'match_date' => '2026-06-11 22:00:00', 'venue' => 'Guadalajara', 'stage' => 'group'],
            ['correlativo' => 3, 'home_team' => 'Canadá', 'away_team' => 'Bosnia Y Herzeg.', 'group_name' => 'Grupo B', 'match_date' => '2026-06-12 16:00:00', 'venue' => 'Toronto', 'stage' => 'group'],
            ['correlativo' => 4, 'home_team' => 'Estados Unidos', 'away_team' => 'Paraguay', 'group_name' => 'Grupo D', 'match_date' => '2026-06-12 19:00:00', 'venue' => 'Los Angeles', 'stage' => 'group'],
            ['correlativo' => 5, 'home_team' => 'Catar', 'away_team' => 'Suiza', 'group_name' => 'Grupo B', 'match_date' => '2026-06-13 13:00:00', 'venue' => 'San Francisco', 'stage' => 'group'],
            ['correlativo' => 6, 'home_team' => 'Brasil', 'away_team' => 'Marruecos', 'group_name' => 'Grupo C', 'match_date' => '2026-06-13 19:00:00', 'venue' => 'N. York/N. Jersey', 'stage' => 'group'],
            ['correlativo' => 7, 'home_team' => 'Haití', 'away_team' => 'Escocia', 'group_name' => 'Grupo C', 'match_date' => '2026-06-13 22:00:00', 'venue' => 'Boston', 'stage' => 'group'],
            ['correlativo' => 8, 'home_team' => 'Australia', 'away_team' => 'Turquía', 'group_name' => 'Grupo D', 'match_date' => '2026-06-14 13:00:00', 'venue' => 'Vancouver', 'stage' => 'group'],
            ['correlativo' => 9, 'home_team' => 'Alemania', 'away_team' => 'Curazao', 'group_name' => 'Grupo E', 'match_date' => '2026-06-14 16:00:00', 'venue' => 'Houston', 'stage' => 'group'],
            ['correlativo' => 10, 'home_team' => 'Países Bajos', 'away_team' => 'Japón', 'group_name' => 'Grupo F', 'match_date' => '2026-06-14 19:00:00', 'venue' => 'Dallas', 'stage' => 'group'],
            ['correlativo' => 11, 'home_team' => 'Costa De Marfil', 'away_team' => 'Ecuador', 'group_name' => 'Grupo E', 'match_date' => '2026-06-14 22:00:00', 'venue' => 'Filadelfia', 'stage' => 'group'],
            ['correlativo' => 12, 'home_team' => 'Suecia', 'away_team' => 'Túnez', 'group_name' => 'Grupo F', 'match_date' => '2026-06-14 22:00:00', 'venue' => 'Monterrey', 'stage' => 'group'],
            ['correlativo' => 13, 'home_team' => 'España', 'away_team' => 'Cabo Verde', 'group_name' => 'Grupo H', 'match_date' => '2026-06-15 13:00:00', 'venue' => 'Atlanta', 'stage' => 'group'],
            ['correlativo' => 14, 'home_team' => 'Bélgica', 'away_team' => 'Egipto', 'group_name' => 'Grupo G', 'match_date' => '2026-06-15 16:00:00', 'venue' => 'Seattle', 'stage' => 'group'],
            ['correlativo' => 15, 'home_team' => 'Arabia Saudita', 'away_team' => 'Uruguay', 'group_name' => 'Grupo H', 'match_date' => '2026-06-15 19:00:00', 'venue' => 'Miami', 'stage' => 'group'],
            ['correlativo' => 16, 'home_team' => 'Irán', 'away_team' => 'Nueva Zelanda', 'group_name' => 'Grupo G', 'match_date' => '2026-06-15 22:00:00', 'venue' => 'Los Angeles', 'stage' => 'group'],
            ['correlativo' => 17, 'home_team' => 'Francia', 'away_team' => 'Senegal', 'group_name' => 'Grupo I', 'match_date' => '2026-06-16 13:00:00', 'venue' => 'N. York/N. Jersey', 'stage' => 'group'],
            ['correlativo' => 18, 'home_team' => 'Irak', 'away_team' => 'Noruega', 'group_name' => 'Grupo I', 'match_date' => '2026-06-16 16:00:00', 'venue' => 'Boston', 'stage' => 'group'],
            ['correlativo' => 19, 'home_team' => 'Argentina', 'away_team' => 'Argelia', 'group_name' => 'Grupo J', 'match_date' => '2026-06-16 19:00:00', 'venue' => 'Kansas City', 'stage' => 'group'],
            ['correlativo' => 20, 'home_team' => 'Austria', 'away_team' => 'Jordania', 'group_name' => 'Grupo J', 'match_date' => '2026-06-17 13:00:00', 'venue' => 'San Francisco', 'stage' => 'group'],
            ['correlativo' => 21, 'home_team' => 'Portugal', 'away_team' => 'Rep. Del Congo', 'group_name' => 'Grupo K', 'match_date' => '2026-06-17 16:00:00', 'venue' => 'Houston', 'stage' => 'group'],
            ['correlativo' => 22, 'home_team' => 'Inglaterra', 'away_team' => 'Croacia', 'group_name' => 'Grupo L', 'match_date' => '2026-06-17 19:00:00', 'venue' => 'Dallas', 'stage' => 'group'],
            ['correlativo' => 23, 'home_team' => 'Ghana', 'away_team' => 'Panamá', 'group_name' => 'Grupo L', 'match_date' => '2026-06-17 19:00:00', 'venue' => 'Toronto', 'stage' => 'group'],
            ['correlativo' => 24, 'home_team' => 'Uzbekistán', 'away_team' => 'Colombia', 'group_name' => 'Grupo K', 'match_date' => '2026-06-17 22:00:00', 'venue' => 'Ciudad de México', 'stage' => 'group'],
            ['correlativo' => 25, 'home_team' => 'Rep. Checa', 'away_team' => 'Sudáfrica', 'group_name' => 'Grupo A', 'match_date' => '2026-06-18 13:00:00', 'venue' => 'Atlanta', 'stage' => 'group'],
            ['correlativo' => 26, 'home_team' => 'Suiza', 'away_team' => 'Bosnia Y Herzeg.', 'group_name' => 'Grupo B', 'match_date' => '2026-06-18 16:00:00', 'venue' => 'Los Angeles', 'stage' => 'group'],
            ['correlativo' => 27, 'home_team' => 'Canadá', 'away_team' => 'Catar', 'group_name' => 'Grupo B', 'match_date' => '2026-06-18 19:00:00', 'venue' => 'Vancouver', 'stage' => 'group'],
            ['correlativo' => 28, 'home_team' => 'México', 'away_team' => 'Corea Del Sur', 'group_name' => 'Grupo A', 'match_date' => '2026-06-18 22:00:00', 'venue' => 'Guadalajara', 'stage' => 'group'],
            ['correlativo' => 29, 'home_team' => 'Estados Unidos', 'away_team' => 'Australia', 'group_name' => 'Grupo D', 'match_date' => '2026-06-19 13:00:00', 'venue' => 'Seattle', 'stage' => 'group'],
            ['correlativo' => 30, 'home_team' => 'Escocia', 'away_team' => 'Marruecos', 'group_name' => 'Grupo C', 'match_date' => '2026-06-19 16:00:00', 'venue' => 'Boston', 'stage' => 'group'],
            ['correlativo' => 31, 'home_team' => 'Brasil', 'away_team' => 'Haití', 'group_name' => 'Grupo C', 'match_date' => '2026-06-19 19:00:00', 'venue' => 'Filadelfia', 'stage' => 'group'],
            ['correlativo' => 32, 'home_team' => 'Turquía', 'away_team' => 'Paraguay', 'group_name' => 'Grupo D', 'match_date' => '2026-06-19 22:00:00', 'venue' => 'San Francisco', 'stage' => 'group'],
            ['correlativo' => 33, 'home_team' => 'Países Bajos', 'away_team' => 'Suecia', 'group_name' => 'Grupo F', 'match_date' => '2026-06-20 13:00:00', 'venue' => 'Houston', 'stage' => 'group'],
            ['correlativo' => 34, 'home_team' => 'Alemania', 'away_team' => 'Costa De Marfil', 'group_name' => 'Grupo E', 'match_date' => '2026-06-20 16:00:00', 'venue' => 'Toronto', 'stage' => 'group'],
            ['correlativo' => 35, 'home_team' => 'Ecuador', 'away_team' => 'Curazao', 'group_name' => 'Grupo E', 'match_date' => '2026-06-20 19:00:00', 'venue' => 'Kansas City', 'stage' => 'group'],
            ['correlativo' => 36, 'home_team' => 'Túnez', 'away_team' => 'Japón', 'group_name' => 'Grupo F', 'match_date' => '2026-06-21 13:00:00', 'venue' => 'Monterrey', 'stage' => 'group'],
            ['correlativo' => 37, 'home_team' => 'España', 'away_team' => 'Arabia Saudita', 'group_name' => 'Grupo H', 'match_date' => '2026-06-21 16:00:00', 'venue' => 'Atlanta', 'stage' => 'group'],
            ['correlativo' => 38, 'home_team' => 'Bélgica', 'away_team' => 'Irán', 'group_name' => 'Grupo G', 'match_date' => '2026-06-21 19:00:00', 'venue' => 'Los Angeles', 'stage' => 'group'],
            ['correlativo' => 39, 'home_team' => 'Uruguay', 'away_team' => 'Cabo Verde', 'group_name' => 'Grupo H', 'match_date' => '2026-06-21 22:00:00', 'venue' => 'Miami', 'stage' => 'group'],
            ['correlativo' => 40, 'home_team' => 'Nueva Zelanda', 'away_team' => 'Egipto', 'group_name' => 'Grupo G', 'match_date' => '2026-06-21 22:00:00', 'venue' => 'Vancouver', 'stage' => 'group'],
            ['correlativo' => 41, 'home_team' => 'Argentina', 'away_team' => 'Austria', 'group_name' => 'Grupo J', 'match_date' => '2026-06-22 13:00:00', 'venue' => 'Dallas', 'stage' => 'group'],
            ['correlativo' => 42, 'home_team' => 'Francia', 'away_team' => 'Irak', 'group_name' => 'Grupo I', 'match_date' => '2026-06-22 16:00:00', 'venue' => 'Filadelfia', 'stage' => 'group'],
            ['correlativo' => 43, 'home_team' => 'Noruega', 'away_team' => 'Senegal', 'group_name' => 'Grupo I', 'match_date' => '2026-06-22 19:00:00', 'venue' => 'N. York/N. Jersey', 'stage' => 'group'],
            ['correlativo' => 44, 'home_team' => 'Jordania', 'away_team' => 'Argelia', 'group_name' => 'Grupo J', 'match_date' => '2026-06-22 22:00:00', 'venue' => 'San Francisco', 'stage' => 'group'],
            ['correlativo' => 45, 'home_team' => 'Portugal', 'away_team' => 'Uzbekistán', 'group_name' => 'Grupo K', 'match_date' => '2026-06-23 13:00:00', 'venue' => 'Houston', 'stage' => 'group'],
            ['correlativo' => 46, 'home_team' => 'Inglaterra', 'away_team' => 'Ghana', 'group_name' => 'Grupo L', 'match_date' => '2026-06-23 16:00:00', 'venue' => 'Boston', 'stage' => 'group'],
            ['correlativo' => 47, 'home_team' => 'Panamá', 'away_team' => 'Croacia', 'group_name' => 'Grupo L', 'match_date' => '2026-06-23 19:00:00', 'venue' => 'Toronto', 'stage' => 'group'],
            ['correlativo' => 48, 'home_team' => 'Colombia', 'away_team' => 'Rep. Del Congo', 'group_name' => 'Grupo K', 'match_date' => '2026-06-23 22:00:00', 'venue' => 'Guadalajara', 'stage' => 'group'],
            ['correlativo' => 49, 'home_team' => 'Suiza', 'away_team' => 'Canadá', 'group_name' => 'Grupo B', 'match_date' => '2026-06-24 13:00:00', 'venue' => 'Vancouver', 'stage' => 'group'],
            ['correlativo' => 50, 'home_team' => 'Bosnia Y Herzeg.', 'away_team' => 'Catar', 'group_name' => 'Grupo B', 'match_date' => '2026-06-24 13:00:00', 'venue' => 'Seattle', 'stage' => 'group'],
            ['correlativo' => 51, 'home_team' => 'Escocia', 'away_team' => 'Brasil', 'group_name' => 'Grupo C', 'match_date' => '2026-06-24 16:00:00', 'venue' => 'Miami', 'stage' => 'group'],
            ['correlativo' => 52, 'home_team' => 'Marruecos', 'away_team' => 'Haití', 'group_name' => 'Grupo C', 'match_date' => '2026-06-24 16:00:00', 'venue' => 'Atlanta', 'stage' => 'group'],
            ['correlativo' => 53, 'home_team' => 'Rep. Checa', 'away_team' => 'México', 'group_name' => 'Grupo A', 'match_date' => '2026-06-24 19:00:00', 'venue' => 'Ciudad de México', 'stage' => 'group'],
            ['correlativo' => 54, 'home_team' => 'Sudáfrica', 'away_team' => 'Corea Del Sur', 'group_name' => 'Grupo A', 'match_date' => '2026-06-24 19:00:00', 'venue' => 'Monterrey', 'stage' => 'group'],
            ['correlativo' => 55, 'home_team' => 'Curazao', 'away_team' => 'Costa De Marfil', 'group_name' => 'Grupo E', 'match_date' => '2026-06-25 13:00:00', 'venue' => 'Filadelfia', 'stage' => 'group'],
            ['correlativo' => 56, 'home_team' => 'Ecuador', 'away_team' => 'Alemania', 'group_name' => 'Grupo E', 'match_date' => '2026-06-25 13:00:00', 'venue' => 'N. York/N. Jersey', 'stage' => 'group'],
            ['correlativo' => 57, 'home_team' => 'Japón', 'away_team' => 'Suecia', 'group_name' => 'Grupo F', 'match_date' => '2026-06-25 16:00:00', 'venue' => 'Dallas', 'stage' => 'group'],
            ['correlativo' => 58, 'home_team' => 'Túnez', 'away_team' => 'Países Bajos', 'group_name' => 'Grupo F', 'match_date' => '2026-06-25 16:00:00', 'venue' => 'Kansas City', 'stage' => 'group'],
            ['correlativo' => 59, 'home_team' => 'Turquía', 'away_team' => 'Estados Unidos', 'group_name' => 'Grupo D', 'match_date' => '2026-06-25 19:00:00', 'venue' => 'Los Angeles', 'stage' => 'group'],
            ['correlativo' => 60, 'home_team' => 'Paraguay', 'away_team' => 'Australia', 'group_name' => 'Grupo D', 'match_date' => '2026-06-25 19:00:00', 'venue' => 'San Francisco', 'stage' => 'group'],
            ['correlativo' => 61, 'home_team' => 'Noruega', 'away_team' => 'Francia', 'group_name' => 'Grupo I', 'match_date' => '2026-06-26 13:00:00', 'venue' => 'Boston', 'stage' => 'group'],
            ['correlativo' => 62, 'home_team' => 'Senegal', 'away_team' => 'Irak', 'group_name' => 'Grupo I', 'match_date' => '2026-06-26 13:00:00', 'venue' => 'Toronto', 'stage' => 'group'],
            ['correlativo' => 63, 'home_team' => 'Cabo Verde', 'away_team' => 'Arabia Saudita', 'group_name' => 'Grupo H', 'match_date' => '2026-06-26 16:00:00', 'venue' => 'Houston', 'stage' => 'group'],
            ['correlativo' => 64, 'home_team' => 'Uruguay', 'away_team' => 'España', 'group_name' => 'Grupo H', 'match_date' => '2026-06-26 16:00:00', 'venue' => 'Guadalajara', 'stage' => 'group'],
            ['correlativo' => 65, 'home_team' => 'Egipto', 'away_team' => 'Irán', 'group_name' => 'Grupo G', 'match_date' => '2026-06-26 19:00:00', 'venue' => 'Seattle', 'stage' => 'group'],
            ['correlativo' => 66, 'home_team' => 'Nueva Zelanda', 'away_team' => 'Bélgica', 'group_name' => 'Grupo G', 'match_date' => '2026-06-26 19:00:00', 'venue' => 'Vancouver', 'stage' => 'group'],
            ['correlativo' => 67, 'home_team' => 'Panamá', 'away_team' => 'Inglaterra', 'group_name' => 'Grupo L', 'match_date' => '2026-06-27 13:00:00', 'venue' => 'N. York/N. Jersey', 'stage' => 'group'],
            ['correlativo' => 68, 'home_team' => 'Croacia', 'away_team' => 'Ghana', 'group_name' => 'Grupo L', 'match_date' => '2026-06-27 13:00:00', 'venue' => 'Filadelfia', 'stage' => 'group'],
            ['correlativo' => 69, 'home_team' => 'Colombia', 'away_team' => 'Portugal', 'group_name' => 'Grupo K', 'match_date' => '2026-06-27 16:00:00', 'venue' => 'Miami', 'stage' => 'group'],
            ['correlativo' => 70, 'home_team' => 'Rep. Del Congo', 'away_team' => 'Uzbekistán', 'group_name' => 'Grupo K', 'match_date' => '2026-06-27 16:00:00', 'venue' => 'Atlanta', 'stage' => 'group'],
            ['correlativo' => 71, 'home_team' => 'Argelia', 'away_team' => 'Austria', 'group_name' => 'Grupo J', 'match_date' => '2026-06-27 19:00:00', 'venue' => 'Kansas City', 'stage' => 'group'],
            ['correlativo' => 72, 'home_team' => 'Jordania', 'away_team' => 'Argentina', 'group_name' => 'Grupo J', 'match_date' => '2026-06-27 22:00:00', 'venue' => 'Dallas', 'stage' => 'group'],
        ];
    }
}
