<?php

namespace DoniaShaker\MediaLibrary\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Media extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $appends = ['image_url', 'thumb_url'];

    public function getImageUrlAttribute(){
        $type = $this->is_temp ? '/temp' : '';
        return $this->directory . '/images' . $type . '/' . $this->model . '/' . $this->media['model_id'] . '-' . $this->media['file_name'] . '.' . $this->media['format'];
    }

    public function getThumbUrlAttribute(){
        $type = $this->is_temp ?  null : '/thumb';
        return $type == null ? null : $this->directory . '/images' . $type   . '/' . $this->media['model'] . '/' . $this->media['model_id'] . '-' . $this->media['file_name'] . '.' . $this->media['format'];

    }

}
