<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quest extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'label',
        'target',
        'progress',
        'completed',
        'claimed',
        'reward',
        'date',
    ];

    protected $casts = [
        'reward' => 'array',
        'completed' => 'boolean',
        'claimed' => 'boolean',
        'date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Définition des quêtes journalières disponibles
     */
    public static function getDailyDefinitions(): array
    {
        return [
            [
                'type' => 'train_soldiers',
                'label' => 'Entraîner 10 soldats',
                'target' => 10,
                'reward' => ['gold' => 300, 'wood' => 0, 'food' => 150, 'xp' => 50],
            ],
            [
                'type' => 'win_battle',
                'label' => 'Gagner 2 batailles',
                'target' => 2,
                'reward' => ['gold' => 500, 'wood' => 100, 'food' => 0, 'xp' => 100],
            ],
            [
                'type' => 'upgrade_building',
                'label' => 'Améliorer 1 bâtiment',
                'target' => 1,
                'reward' => ['gold' => 200, 'wood' => 200, 'food' => 0, 'xp' => 75],
            ],
        ];
    }

    /**
     * S'assurer que les quêtes du jour existent pour un utilisateur
     */
    public static function ensureDailyQuests(int $userId): void
    {
        $today = now()->toDateString();

        foreach (self::getDailyDefinitions() as $def) {
            self::firstOrCreate(
                ['user_id' => $userId, 'type' => $def['type'], 'date' => $today],
                [
                    'label' => $def['label'],
                    'target' => $def['target'],
                    'progress' => 0,
                    'completed' => false,
                    'claimed' => false,
                    'reward' => $def['reward'],
                ]
            );
        }
    }

    /**
     * Incrémenter la progression d'une quête
     */
    public static function addProgress(int $userId, string $type, int $amount = 1): void
    {
        $today = now()->toDateString();
        $quest = self::where('user_id', $userId)
            ->where('type', $type)
            ->where('date', $today)
            ->where('completed', false)
            ->first();

        if (!$quest) return;

        $newProgress = min($quest->progress + $amount, $quest->target);
        $quest->progress = $newProgress;
        $quest->completed = $newProgress >= $quest->target;
        $quest->save();
    }
}
