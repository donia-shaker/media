<?php 

use DoniaShaker\MediaLibrary\MediaController;
use Illuminate\Http\Testing\File;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MediaController::class)]
class MediaTest extends TestCase
{
    public function testAdd():void
    {

        $this->app['config']->set('media.publicPath', __DIR__ . '/../tests/public/media');
        $this->app['config']->set('media.storagePath', __DIR__ . '/../tests/storage/media');
        $this->app['config']->set('media.useStorage', false);


        $media_functions = new MediaController();
        $image  =   File::image('test.png');
        $media_functions->uploadImage('test', 1, $image);
        $this->assertTrue(true);
    }
}