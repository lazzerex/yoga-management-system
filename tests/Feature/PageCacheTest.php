<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\LessonPlan;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PageCacheTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function assertNotStored($response): void
    {
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_a_full_page_is_never_stored(): void
    {
        $response = $this->actingAs($this->admin())->get('/cms/dashboard');

        $response->assertOk();
        $this->assertNotStored($response);
    }

    /**
     * An Inertia visit replaces the page without a document request, so its JSON carries
     * the same data the HTML would. Marking only text/html would leave this uncovered.
     */
    public function test_an_inertia_visit_is_never_stored(): void
    {
        $version = app(HandleInertiaRequests::class)->version(Request::create('/cms/dashboard'));

        $response = $this->actingAs($this->admin())->get('/cms/dashboard', [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version,
        ]);

        $response->assertOk();
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertNotStored($response);
    }

    public function test_a_json_endpoint_is_never_stored(): void
    {
        $response = $this->actingAs($this->admin())->getJson('/cms/search?q=ad');

        $response->assertOk();
        $this->assertNotStored($response);
    }

    public function test_a_page_outside_the_cms_is_covered_too(): void
    {
        $response = $this->actingAs($this->admin())->get('/');

        $response->assertOk();
        $this->assertNotStored($response);
    }

    public function test_a_guest_page_is_left_alone(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $this->assertStringNotContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    /**
     * Avatars and downloads stay cacheable, or every list page refetches each thumbnail.
     */
    public function test_a_file_download_stays_cacheable(): void
    {
        $plan = LessonPlan::factory()->create();
        $media = $plan->addMedia(UploadedFile::fake()->image('board.jpg'))->toMediaCollection('attachments');

        $response = $this->actingAs($this->admin())->get("/cms/operations/files/{$media->id}");

        $response->assertOk();
        $this->assertStringNotContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }
}
