<?php

namespace Tests\Unit;

use Grafite\Archive\Concerns\Archiveable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArchiveRunCommandTest extends TestCase
{
    protected string $diskRoot;

    public function setUp(): void
    {
        parent::setUp();

        $this->diskRoot = sys_get_temp_dir().'/grafite-archive-tests-'.bin2hex(random_bytes(4));

        config()->set('filesystems.disks.archive-testing', [
            'driver' => 'local',
            'root' => $this->diskRoot,
        ]);

        Storage::disk('archive-testing')->deleteDirectory('archives');

        Schema::create('test_articles', function ($table) {
            $table->id();
            $table->string('title');
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }

    public function tearDown(): void
    {
        Schema::dropIfExists('test_articles');

        if (is_dir($this->diskRoot)) {
            app('files')->deleteDirectory($this->diskRoot);
        }

        parent::tearDown();
    }

    public function test_it_exports_an_encrypted_archive_for_an_archiveable_model(): void
    {
        TestArticle::query()->create([
            'title' => 'Old Post',
            'status' => 'published',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ]);

        TestArticle::query()->create([
            'title' => 'Recent Post',
            'status' => 'published',
            'created_at' => '2025-01-01 00:00:00',
            'updated_at' => '2025-01-01 00:00:00',
        ]);

        $exitCode = Artisan::call('archive:run', [
            '--model' => TestArticle::class,
            '--before' => '2024-12-31 23:59:59',
            '--disk' => 'archive-testing',
        ]);

        $this->assertSame(0, $exitCode);

        $files = Storage::disk('archive-testing')->files('archives');

        $this->assertCount(1, $files);

        $archive = json_decode(Storage::disk('archive-testing')->get($files[0]), true, 512, JSON_THROW_ON_ERROR);
        $decryptedPayload = json_decode(Crypt::decryptString($archive['payload']), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue($archive['encrypted']);
        $this->assertSame(TestArticle::class, $archive['manifest']['model']);
        $this->assertSame(1, $archive['manifest']['record_count']);
        $this->assertSame('Old Post', $decryptedPayload['records'][0]['title']);
        $this->assertStringNotContainsString('Old Post', $archive['payload']);
    }

    public function test_it_can_disable_model_default_scopes_from_the_cli(): void
    {
        TestArticle::query()->create([
            'title' => 'Old Published Post',
            'status' => 'published',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ]);

        TestArticle::query()->create([
            'title' => 'Old Draft Post',
            'status' => 'draft',
            'created_at' => '2024-02-01 00:00:00',
            'updated_at' => '2024-02-01 00:00:00',
        ]);

        $exitCode = Artisan::call('archive:run', [
            '--model' => TestArticle::class,
            '--before' => '2024-12-31 23:59:59',
            '--disk' => 'archive-testing',
            '--without-scopes' => true,
            '--unencrypted' => true,
        ]);

        $this->assertSame(0, $exitCode);

        $files = Storage::disk('archive-testing')->files('archives');
        $this->assertCount(1, $files);

        $archive = json_decode(Storage::disk('archive-testing')->get($files[0]), true, 512, JSON_THROW_ON_ERROR);
        $payload = json_decode($archive['payload'], true, 512, JSON_THROW_ON_ERROR);

        $this->assertFalse($archive['encrypted']);
        $this->assertSame([], $archive['manifest']['filters']['scopes']);
        $this->assertSame(2, $archive['manifest']['record_count']);
        $this->assertSame(['Old Published Post', 'Old Draft Post'], array_column($payload['records'], 'title'));
    }
}

class TestArticle extends Model
{
    use Archiveable;

    protected $table = 'test_articles';

    protected $guarded = [];

    protected array $archiveScopes = ['published'];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
}