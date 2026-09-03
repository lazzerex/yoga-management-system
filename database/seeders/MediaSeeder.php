<?php

namespace Database\Seeders;

use App\Models\CoachProfile;
use App\Models\LessonPlan;
use App\Models\Payment;
use App\Models\StudentProfile;
use Illuminate\Database\Seeder;

class MediaSeeder extends Seeder
{
    private const AVATARS = 4;

    public function run(): void
    {
        CoachProfile::orderBy('id')->take(6)->get()
            ->each(fn (CoachProfile $profile, int $index) => $this->attach($profile, 'avatar', $this->avatar($index)));

        StudentProfile::orderBy('id')->take(8)->get()
            ->each(fn (StudentProfile $profile, int $index) => $this->attach($profile, 'avatar', $this->avatar($index + 1)));

        LessonPlan::orderBy('id')->take(4)->get()
            ->each(fn (LessonPlan $plan) => $this->attach($plan, 'attachments', $this->path('asana-sequence.pdf')));

        // Only settled money carries a receipt in the demo data.
        Payment::recorded()->orderBy('id')->take(5)->get()
            ->each(fn (Payment $payment) => $this->attach($payment, 'proof', $this->path('receipt.png')));
    }

    private function attach(object $model, string $collection, string $path): void
    {
        // preservingOriginal, or medialibrary moves the fixture out of the repo.
        $model->addMedia($path)->preservingOriginal()->toMediaCollection($collection);
    }

    private function avatar(int $index): string
    {
        return $this->path('avatar-'.(($index % self::AVATARS) + 1).'.png');
    }

    private function path(string $file): string
    {
        return database_path('seeders/files/'.$file);
    }
}
