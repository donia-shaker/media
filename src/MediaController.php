<?php

namespace DoniaShaker\MediaLibrary;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Models\Media;


class MediaController
{
    protected  $manager;
    protected  $directory;
    protected $config;

    public function __construct(){
        $this->manager = new ImageManager(new Driver());
        config('media');
        $this->directory = config('media.useStorage') ? config('media.storagePath') : config('media.publicPath');
    }

    public function uploadImage($model, $model_id, $file)
    {

        if (!is_dir($this->directory . '/images/' . $model)) {
            mkdir(($this->directory . '/images/' . $model), 0777, true);
        }

        $data['name'] = date('YmdHis') . '-' . uniqid();

        $data['file_name'] = $model . '/' . $model_id . '-' . $data['name'] . '.webp';

        try {
            $this->manager
                ->read($file)
                ->toWebp(80)
                ->save($this->directory . '/images/' . $data['file_name']);
            $data['image'] = $data['file_name'];
        } catch (\Exception $e) {
            $data['image'] = null;
        }

        return $data;
    }

    public function createThumb($model, $model_id, $name)
    {

        if (!file_exists($this->directory . '/images/' . $model . '/thumb/')) {
            mkdir(($this->directory . '/images/' . $model . '/thumb/'), 0777, true);
        }
        $thumb_name = $model . '/thumb/' . $model_id . '-' . $name . '.webp';

        try {
            $this->manager
                ->read($this->directory . '/images/' . $model . '/' . $model_id . '-' . $name . '.webp')->scale(width: 400)->save($this->directory . '/images/' . $thumb_name);
            $data['thumb'] = $thumb_name;
        } catch (\Exception $e) {
            $data['thumb'] = null;
        }

        return $data;
    }

    public function saveImage($model, $model_id, $file)
    {
        $data['image'] = $this->uploadImage($model, $model_id, $file);
        if ($data['image']['image'] == null) {
            $data['image'] = $this->uploadImage($model, $model_id, $file);
        }

        $new_image = Media::create([
            'model' => $model,
            'model_id' => $model_id,
            'file_name' => $data['image']['name'],
            'format' => 'webp',
        ]);

        // upload thumb
        $data['thumb'] = $this->createThumb($model, $model_id, $data['image']['name']);
        if ($data['thumb']['thumb'] == null) {
            $data['thumb'] = $this->createThumb($model, $model_id, $data['image']['name']);
        } else {
            $new_image->has_thumb = 1;
            $new_image->save();
        }

        return $data;
    }

    
    public function uploadTempImage($model, $model_id, $file)
    {
        if (! file_exists($this->directory.'images/temp/'.$model)) {
            mkdir(($this->directory.'images/temp/'.$model), 0777, true);
        }

        $data['name'] = date('YmdHis').'-'.uniqid();
        $data['extension'] = explode('.', $file->getClientOriginalName())[1];

        $data['file_name'] = $model.'/'.$model_id.'-'.$data['name'].'.'.$data['extension'];

        try {
            $this->manager
                ->read($file)
                ->toWebp(80)
                ->save($this->directory . '/images/temp/' . $data['file_name']);

            $data['image'] = $data['file_name'];

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::channel('daily')->error($e);

            $data['image'] = null;
        }

        return $data;
    }

    
    public function saveTempImage($model, $model_id, $file)
    {
        $data['image'] = $this->uploadTempImage($model, $model_id, $file);
        if ($data['image']['image'] == null) {
            $data['image'] = $this->uploadTempImage($model, $model_id, $file);
        }


        $new_image = Media::create([
            'model'     => $model,
            'model_id'  => $model_id,
            'file_name' => $data['image']['name'],
            'format'    => $data['image']['extension'],
            'is_active' => 0,
        ]);

        return $data;
    }

    public function convertMedia($model , $id)
    {
        try {

            $images = Media::where('model', $model)->where('model_id', $id)->where('format', '!=', 'webp')->get();

            // return $images;
            foreach ($images as $image) {
                $main_image = $this->manager->read($this->directory.'images/temp/'.$image->model.'/'.$image->model_id.'-'.$image->file_name.'.'.$image->format);

                $save_image = $this->saveImage($model, $id, $main_image);
                $image->delete();

                $old_image = \Illuminate\Support\Facades\File::delete($this->directory.'images/temp/'.$image->model.'/'.$image->model_id.'-'.$image->file_name.'.'.$image->format);

            }

            return redirect()->back()->with(['success' => 'تمت الموافقة على الصور']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::channel('daily')->error($e);

            return redirect()->back()->with(['error' => $e->getMessage()]);
        }
    }
}
