<?php

namespace App\Modules\Operations\Media\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CoachProfile;
use App\Models\LessonPlan;
use App\Models\Payment;
use App\Models\StudentProfile;
use App\Models\User;
use App\Modules\Operations\Media\Actions\AuthorizeMediaAccessAction;
use App\Support\Table\SortsQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class FileLibraryController extends Controller
{
    use SortsQueries;

    /** Keyed by the model that owns the file; the UI calls these folders. */
    private const KINDS = [
        'people' => User::class,
        'lessonPlans' => LessonPlan::class,
        'payments' => Payment::class,
    ];

    public function __construct(private AuthorizeMediaAccessAction $access) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $scope = $this->visibleScope($user);
        $kind = $request->string('kind')->toString();
        $search = $request->string('search')->toString();
        $from = $request->date('from')?->toDateString();
        $to = $request->date('to')?->toDateString();

        $query = Media::query()
            ->with(['model' => fn (MorphTo $model) => $model->morphWith([
                User::class => ['coachProfile:id,user_id', 'studentProfile:id,user_id'],
                Payment::class => ['invoice:id,invoice_number'],
            ])])
            ->tap($scope)
            ->when(isset(self::KINDS[$kind]), fn ($q) => $q->where('model_type', self::KINDS[$kind]))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('file_name', 'like', "%{$search}%")))
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to));

        $sort = $this->applySort($query, $request, [
            'name' => 'name',
            'size' => 'size',
            'created_at' => 'created_at',
        ], 'id');

        $files = $query->paginate(20)->withQueryString();

        return inertia('Operations/FileLibrary', [
            'files' => $files->through(fn (Media $media) => $this->row($user, $media)),
            'folders' => $this->folders($scope),
            'stats' => [
                'totalSize' => (int) Media::query()->tap($scope)->sum('size'),
                'totalFiles' => Media::query()->tap($scope)->count(),
            ],
            'filters' => ['kind' => $kind, 'search' => $search, 'from' => $from ?? '', 'to' => $to ?? ''] + $sort,
            'endpoints' => ['index' => route('operations.file-library')],
        ]);
    }

    /**
     * The listing never shows a file its viewer could not download: the same rules as
     * AuthorizeMediaAccessAction, expressed as a query so paging and counts stay honest.
     */
    private function visibleScope(User $user): callable
    {
        $coachProfileId = $user->coachProfile?->id;

        return function (Builder $query) use ($user, $coachProfileId) {
            $query->where(function (Builder $q) use ($user, $coachProfileId) {
                // Own avatar first: every viewer keeps their own picture whatever else they hold.
                $q->orWhere(fn (Builder $w) => $w
                    ->where('model_type', User::class)
                    ->where('model_id', $user->id));

                if ($user->can('admin.users.view')) {
                    $q->orWhere('model_type', User::class);
                }

                if ($user->can('operations.coaches.view')) {
                    $q->orWhere(fn (Builder $w) => $w
                        ->where('model_type', User::class)
                        ->whereIn('model_id', CoachProfile::query()->select('user_id')));
                }

                if ($user->can('operations.students.view.any')) {
                    $q->orWhere(fn (Builder $w) => $w
                        ->where('model_type', User::class)
                        ->whereIn('model_id', StudentProfile::query()->select('user_id')));
                } elseif ($user->can('operations.students.view') && $coachProfileId) {
                    $q->orWhere(fn (Builder $w) => $w
                        ->where('model_type', User::class)
                        ->whereIn('model_id', StudentProfile::whereHas('enrollments', fn ($e) => $e
                            ->where('status', 'booked')
                            ->whereHas('classSession', fn ($s) => $s->where('coach_profile_id', $coachProfileId)))
                            ->select('user_id')));
                }

                if ($user->can('operations.plans.view.any')) {
                    $q->orWhere('model_type', LessonPlan::class);
                } elseif ($coachProfileId) {
                    $q->orWhere(fn (Builder $w) => $w
                        ->where('model_type', LessonPlan::class)
                        ->whereIn('model_id', LessonPlan::where('coach_profile_id', $coachProfileId)->select('id')));
                }

                if ($user->can('operations.tuition.manage')) {
                    $q->orWhere('model_type', Payment::class);
                }
            });
        };
    }

    private function folders(callable $scope): array
    {
        $rows = Media::query()->tap($scope)
            ->selectRaw('model_type, count(*) as total, sum(size) as bytes')
            ->groupBy('model_type')
            ->get()
            ->keyBy('model_type');

        return collect(self::KINDS)
            ->filter(fn (string $class) => $rows->has($class))
            ->map(fn (string $class, string $kind) => [
                'kind' => $kind,
                'count' => (int) $rows[$class]->total,
                'size' => (int) $rows[$class]->bytes,
            ])
            ->values()
            ->all();
    }

    private function row(User $user, Media $media): array
    {
        return [
            'id' => $media->id,
            'name' => $media->name,
            'file_name' => $media->file_name,
            'mime_type' => $media->mime_type,
            'size' => (int) $media->size,
            'kind' => array_search($media->model_type, self::KINDS, true) ?: 'other',
            'owner_label' => $this->ownerLabel($media),
            'uploaded_at' => $media->created_at?->toIso8601String(),
            'is_image' => str_starts_with((string) $media->mime_type, 'image/'),
            'has_thumb' => $media->hasGeneratedConversion('thumb'),
            'showUrl' => route('operations.files.show', $media),
            'downloadUrl' => route('operations.files.show', [$media, 'download' => 1]),
            'thumbUrl' => route('operations.files.show', [$media, 'conversion' => 'thumb']),
            'deleteUrl' => $this->access->canDelete($user, $media) ? route('operations.files.destroy', $media) : null,
        ];
    }

    private function ownerLabel(Media $media): string
    {
        $owner = $media->model;

        return match (true) {
            $owner instanceof User => $owner->name,
            $owner instanceof LessonPlan => $owner->title,
            $owner instanceof Payment => $owner->invoice->invoice_number,
            default => '-',
        };
    }
}
