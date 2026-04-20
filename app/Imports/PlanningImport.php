<?php

namespace App\Imports;

use App\Models\Planning;
use App\Models\User;
use App\Models\Equipe;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PlanningImport implements ToArray, WithMultipleSheets
{
    public array $warnings = [];
    public int   $imported = 0;

    // Shifts fixes — kif fichier dyalek
    private array $shiftsConfig = [
        'J'  => ['heure_debut' => '08:00', 'heure_fin' => '15:00', 'pause' => 0],
        'A'  => ['heure_debut' => '13:00', 'heure_fin' => '20:00', 'pause' => 0],
        'J+' => ['heure_debut' => '08:00', 'heure_fin' => '20:00', 'pause' => 60],
        'N'  => ['heure_debut' => '20:00', 'heure_fin' => '08:00', 'pause' => 60],
        'HO' => ['heure_debut' => '08:00', 'heure_fin' => '18:00', 'pause' => 60],
        'R'  => null, // Repos — machi planning
    ];

    public function sheets(): array
    {
        return [0 => $this]; // ghi sheet lwela
    }

    public function array(array $rows): void
    {
        // Jib header row — fiha equipes
        $headerRow   = null;
        $equipeNoms  = [];

        foreach ($rows as $index => $row) {
            // Jib row li fiha "Date" — hiya l-header
            if (isset($row[0]) && str_contains(strtolower((string)$row[0]), 'date')) {
                $headerRow = $index;
                // Colonnes 1+ = equipes
                for ($col = 1; $col < count($row); $col++) {
                    if (!empty($row[$col])) {
                        $equipeNoms[$col] = $this->extractEquipeName($row[$col]);
                    }
                }
                continue;
            }

            // Lignes d-data — qbel header skip
            if ($headerRow === null) continue;

            // Jib date men colonne 0
            $dateCell = $row[0] ?? null;
            if (empty($dateCell)) continue;

            $date = $this->parseDate($dateCell);
            if (!$date) continue;

            // Process kull equipe f-had ligne
            foreach ($equipeNoms as $col => $equipeName) {
                $cellValue = trim((string)($row[$col] ?? ''));
                if (empty($cellValue)) continue;

                // Extract shift w congés
                [$shift, $conges] = $this->parseCell($cellValue);

                if (!$shift || $shift === 'R') continue; // Repos = skip

                if (!isset($this->shiftsConfig[$shift])) {
                    $this->warnings[] = "Shift inconnu: '{$shift}' — date: {$date}";
                    continue;
                }

                $config = $this->shiftsConfig[$shift];
                $equipe = Equipe::where('nom', 'like', "%{$equipeName}%")->first();

                if (!$equipe) {
                    $this->warnings[] = "Equipe '{$equipeName}' machi kayna f-DB";
                    continue;
                }

                // Process kull user f-equipe
                foreach ($equipe->users as $user) {
                    // Check ila user f-congé
                    $inConge = false;
                    foreach ($conges as $conge) {
                        if (str_contains(strtolower($user->nom), strtolower($conge))) {
                            $inConge = true;
                            break;
                        }
                    }
                    if ($inConge) continue;

                    // Check duplicate
                    $exists = Planning::where('user_id', $user->id)
                        ->where('date', $date)
                        ->where('shift', $shift)
                        ->exists();

                    if ($exists) continue;

                    // Calcul heures
                    $debut  = Carbon::parse($config['heure_debut']);
                    $fin    = Carbon::parse($config['heure_fin']);

                    // N shift kay-3bor midnight
                    if ($shift === 'N') {
                        $fin->addDay();
                    }

                    $totalMinutes = $fin->diffInMinutes($debut) - $config['pause'];
                    $heuresJour   = round($totalMinutes / 60, 2);

                    // Check 44h semaine
                    $startWeek = Carbon::parse($date)->startOfWeek();
                    $endWeek   = Carbon::parse($date)->endOfWeek();

                    $heuresSemaine = Planning::where('user_id', $user->id)
                        ->whereBetween('date', [$startWeek, $endWeek])
                        ->sum('heures_reelles');

                    $over44 = ($heuresSemaine + $heuresJour) > 44;

                    if ($over44) {
                        $this->warnings[] = "⚠️ {$user->nom} — dépasse 44h cette semaine (semaine du {$startWeek->format('d/m')})";
                    }

                    Planning::create([
                        'user_id'        => $user->id,
                        'date'           => $date,
                        'shift'          => $shift,
                        'heure_debut'    => $config['heure_debut'],
                        'heure_fin'      => $config['heure_fin'],
                        'pause_minutes'  => $config['pause'],
                        'heures_reelles' => $heuresJour,
                        'over_44h'       => $over44,
                    ]);

                    $this->imported++;
                }
            }
        }
    }

    // Parse date "lundi 17 novembre 2025" → "2026-11-17"
    private function parseDate($value): ?string
    {
        try {
            Carbon::setLocale('fr');
            $clean = preg_replace('/^(lundi|mardi|mercredi|jeudi|vendredi|samedi|dimanche)\s+/i', '', (string)$value);
            $date  = Carbon::createFromFormat('d F Y', $clean, 'Africa/Casablanca');
            return $date ? $date->format('Y-m-d') : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    // Extract nom equipe men cell header
    private function extractEquipeName(string $value): string
    {
        // "Équipe 1:\nYoussef..." → "Equipe 1"
        $lines = explode("\n", $value);
        $first = trim($lines[0]);
        // 7yyed ":"
        return rtrim($first, ':');
    }

    // Parse cell "J (Congé : Ayoub CHAFIKI)" → [shift, [conges]]
    private function parseCell(string $value): array
    {
        $shift  = null;
        $conges = [];

        // Extract shift
        if (preg_match('/^([A-Z\+]+)/i', $value, $m)) {
            $shift = strtoupper(trim($m[1]));
        }

        // Extract congés
        if (preg_match('/Congé\s*:\s*(.+)/i', $value, $m)) {
            $congesStr = $m[1];
            // Multiple congés séparés par ";"
            $parts = explode(';', $congesStr);
            foreach ($parts as $part) {
                $conges[] = trim($part);
            }
        }

        return [$shift, $conges];
    }
}


