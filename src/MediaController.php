<?php

namespace DoniaShaker\MediaLibrary;

use DoniaShaker\MediaLibrary\Models\Media;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;


class MediaController
{
    protected  $manager;
    protected  $directory;
    protected $media;
    protected $config;

    public function __construct($media = null)
    {
        $this->media = $media;
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
        if (!file_exists($this->directory . '/images/temp/' . $model)) {
            mkdir(($this->directory . '/images/temp/' . $model), 0777, true);
        }

        $data['name'] = date('YmdHis') . '-' . uniqid();
        $data['extension'] = explode('.', $file->getClientOriginalName())[1];

        $data['file_name'] = $model . '/' . $model_id . '-' . $data['name'] . '.' . $data['extension'];

        try {
            $this->manager
                ->read($file)
                ->toWebp(80)
                ->save($this->directory . '/images/temp/' . $data['file_name']);

            $data['image'] = $data['file_name'];
        } catch (\Exception $e) {

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
            'is_temp' => 1,
        ]);

        return $data;
    }

    public function convertMedia($model, $model_id, $id)
    {
        try {

            $image = Media::where('id', $id)->first();

            // return $images;
            $main_image = $this->manager->read($this->directory . 'images/temp/' . $image->model . '/' . $image->model_id . '-' . $image->file_name . '.' . $image->format);

            $save_image = $this->saveImage($model, $model_id, $main_image);

            $this->deleteTemp($id);

            return response()->json([
                'message' => 'success',
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteTemp($id)
    {
        try {
            $image = Media::where('id', $id)->first();

            if (!file_exists($this->directory . '/images/temp/' . $image->model . '/' . $image->model_id . '-' . $image->file_name . '.' . $image->format) || $image->is_temp == 0) {
                return response()->json([
                    'message' => 'There is no image file to delete or its not a temp image',
                ], 500);
            } else
                $old_image = File::delete($this->directory . '/images/temp/' . $image->model . '/' . $image->model_id . '-' . $image->file_name . '.' . $image->format);

            $image->delete();

            return response()->json([
                'message' => 'success',
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getImageUrl($type = null)
    {
        // type [null, 'thumb', 'temp']
        if ($this->media)
            return $this->directory . '/images' . ($type ? '/temp' : '') . '/' . $this->media['model'] . '/' . $this->media['model_id'] . '-' . $this->media['file_name'] . '.' . $this->media['format'];
        return response()->json([
            'message' => 'no file found',
        ], 500);
    }

    public function uploadFile($model, $model_id, $file)
    {
        try {

            if (!file_exists($this->directory . '/pdf/' . $model)) {
                mkdir(($this->directory . '/pdf/' . $model), 0777, true);
            }

            $data['name'] = date('YmdHis') . '-' . uniqid() . '-' . explode('.', $file->getClientOriginalName())[0];
            $data['extension'] = explode('.', $file->getClientOriginalName())[1];

            $data['file_name'] = $model . '/' . $model_id . '-' . $data['name'] . '.' . $data['extension'];


            $file->move(public_path('pdf'), $this->directory . '/pdf/' . $data['file_name']);
            $new_image = Media::create([
                'model' => $model,
                'model_id' => $model_id,
                'file_name' => $data['name'],
                'format' => $data['extension'],
            ]);
            return response()->json([
                'message' => 'success',
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getFileUrl()
    {
        // type [null, 'thumb', 'temp']
        if ($this->media)
            return $this->directory . '/pdf/' . $this->media['model'] . '/' . $this->media['model_id'] . '-' . $this->media['file_name'] . '.' . $this->media['format'];
        return response()->json([
            'message' => 'no file found',
        ], 500);
    }
}
