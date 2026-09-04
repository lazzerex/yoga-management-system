<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Avatars used to hang off the coach and student profiles, which left admins with no
 * picture at all. They now hang off the user. Files on disk are keyed by media id, so
 * only the owner columns move.
 */
return new class extends Migration
{
    /** Class names are written out, not referenced, so a later rename cannot rewrite history. */
    private const PROFILES = [
        'App\Models\CoachProfile' => 'coach_profiles',
        'App\Models\StudentProfile' => 'student_profiles',
    ];

    public function up(): void
    {
        foreach (self::PROFILES as $modelType => $table) {
            DB::table('media')->where('model_type', $modelType)->orderBy('id')->each(function ($media) use ($table) {
                $userId = DB::table($table)->where('id', $media->model_id)->value('user_id');

                if ($userId) {
                    DB::table('media')->where('id', $media->id)->update([
                        'model_type' => 'App\Models\User',
                        'model_id' => $userId,
                    ]);
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::PROFILES as $modelType => $table) {
            DB::table('media')
                ->where('model_type', 'App\Models\User')
                ->where('collection_name', 'avatar')
                ->orderBy('id')
                ->each(function ($media) use ($modelType, $table) {
                    $profileId = DB::table($table)->where('user_id', $media->model_id)->value('id');

                    if ($profileId) {
                        DB::table('media')->where('id', $media->id)->update([
                            'model_type' => $modelType,
                            'model_id' => $profileId,
                        ]);
                    }
                });
        }
    }
};
