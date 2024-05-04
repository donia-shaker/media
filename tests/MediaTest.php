<?php 

use DoniaShaker\MediaLibrary\MediaController;
use DoniaShaker\MediaLibrary\MediaServiceProvider;
use DoniaShaker\MediaLibrary\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MediaController::class)]
class MediaTest extends TestCase
{

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app){
        return [
            MediaServiceProvider::class
        ];
    }

    protected function getEnvironmentSetUp($app){

        $app['config']->set('database.default', 'testdb');
        $app['config']->set('database.connections.testdb', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        
        $app['config']->set('media.publicPath', __DIR__ . '/../tests/public/media');
        $app['config']->set('media.storagePath', __DIR__ . '/../tests/storage/media');
        $app['config']->set('media.useStorage', false);
    }

    public function testSaveImage():void
    {

        $media_functions = new MediaController();
        $image  =   File::image('test.png');
        $media_functions->saveImage('test', 1, $image);
        $this->assertCount(1, Media::all(), 'Media count should be 1');
    }

    public function testSaveTempImage():void{
        $media_functions = new MediaController();
        $image  =   File::image('test.png');
        $media_functions->saveTempImage('test', 1, $image);
        $this->assertCount(1, Media::all(), 'Media count should be 1');
    }
}