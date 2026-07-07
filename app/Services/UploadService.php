<?php
namespace App\Services;
use Illuminate\Support\Facades\URL;
use Intervention\Image\Facades\Image;
use Illuminate\Http\Request;


class UploadService
{

    public static function upload_admin_photo($data)
    {
        $admin_photo_url = "";

        if($data->hasFile('photo')):
            $file = $data->file('photo');
            $extension =  $file->getClientOriginalExtension();

            $filename ="admin_photo".time().'.'.$extension;

            if($filename):
                $file->move('media/admin_photo/',$filename);
            else:
                return "error";
            endif;

            $path = "media/admin_photo/";

            $admin_photo_url = URL::to('').'/'.$path.$filename;

            return $admin_photo_url;
        else:
            return "file_not_found";
        endif;
    }




    public static function upload_redaction_photo($data)
    {
        $redaction_photo_url = "";

        if($data->hasFile('redaction_photo')):
            $file = $data->file('redaction_photo');
            $extension =  $file->getClientOriginalExtension();

            $filename ="redaction_photo".time().'.'.$extension;

            if($filename):
                $file->move('media/redaction_photo/',$filename);
            else:
                return "error";
            endif;

            $path = "media/redaction_photo/";

            $redaction_photo_url = URL::to('').'/'.$path.$filename;

            return $redaction_photo_url;
        else:
            return "file_not_found";
        endif;
    }

    public static function upload_quoideneuf_photo($data)
    {
        $photo_url = "";

        if($data->hasFile('photo')):
            $file = $data->file('photo');
            $extension =  $file->getClientOriginalExtension();

            $filename ="photo".time().'.'.$extension;

            if($filename):
                $file->move('media/photo/',$filename);
            else:
                return "error";
            endif;

            $path = "media/photo/";

            $photo_url = URL::to('').'/'.$path.$filename;

            return $photo_url;
        else:
            return "file_not_found";
        endif;
    }



    public static function upload_developer_photo($data)
    {
        $developer_photo_url = "";

        if($data->hasFile('developer_photo')):
            $file = $data->file('developer_photo');
            $extension =  $file->getClientOriginalExtension();

            $filename ="developer_photo".time().'.'.$extension;

            if($filename):
                $file->move('media/developer_photo/',$filename);
            else:
                return "error";
            endif;

            $path = "media/developer_photo/";

            $developer_photo_url = URL::to('').'/'.$path.$filename;

            return $developer_photo_url;
        else:
            return "file_not_found";
        endif;
    }


    public static function upload_employe_photo(Request $data)
    {
        $employe_photo_url = "";

        if($data->hasFile('employe_photo')):
            $file = $data->file('employe_photo');
            $extension =  $file->getClientOriginalExtension();

            $filename ="employe_photo".time().'.'.$extension;

            if($filename):

                $img = Image::make($file->getRealPath());
                $img->resize(500, 500);
                $img->save('media/employe_photo/',$filename);
            else:
                return "error";
            endif;

            $path = "media/employe_photo/";

            $employe_photo_url = URL::to('').'/'.$path.$filename;

            return $employe_photo_url;
        else:
            return "file_not_found";
        endif;
    }




    public static function upload_permission_file($data)
    {
        $permission_file = "";

        if($data->hasFile('permission_file')):
            $file = $data->file('permission_file');
            $extension =  $file->getClientOriginalExtension();

            $filename ="permission_file".time().'.'.$extension;

            if($filename):
                $file->move('media/permission_file/',$filename);
            else:
                return "error";
            endif;

            $path = "media/permission_file/";

            $permission_file = URL::to('').'/'.$path.$filename;

            return $permission_file;
        else:
            return "file_not_found";
        endif;
    }

    public static function upload_audio($data)
    {
        $audio_url = "";

        if($data->hasFile('audio')):
            $file = $data->file('audio');
            $extension =  $file->getClientOriginalExtension();

            $filename ="audio".time().'.'.$extension;

            if($filename):
                $file->move('media/audio/',$filename);
            else:
                return "error";
            endif;

            $path = "media/audio/";

            $audio_url = URL::to('').'/'.$path.$filename;

            return $audio_url;
        else:
            return "file_not_found";
        endif;
    }


    public static function upload_countrie_flag($data)
    {
        $flag_url = "";

        if($data->hasFile('flag')):
            $file = $data->file('flag');
            $extension =  $file->getClientOriginalExtension();

            $filename ="flag".time().'.'.$extension;

            if($filename):
                $file->move('media/pays/',$filename);
            else:
                return "error";
            endif;

            $path = "media/pays/";

            $flag_url = URL::to('').'/'.$path.$filename;

            return $flag_url;
        else:
            return "file_not_found";
        endif;
    }

    public static function upload_galerie_image($data)
    {
        $galerie_img_url = "";

        if($data->hasFile('galerie_img')):
            $file = $data->file('galerie_img');
            $extension =  $file->getClientOriginalExtension();

            $filename ="galerie_img".time().'.'.$extension;

            if($filename):
		        $img = Image::make($file->getRealPath());
                //$img->resize(957, 874);

                $img->save('media/galerie/'.$filename);
                // $file->move('media/galerie/',$filename);
            else:
                return "error";
            endif;
            $path = "media/galerie/";

            $galerie_img_url = URL::to('').'/'.$path.$filename;

            return $galerie_img_url;
        else:
            return "file_not_found";
        endif;
    }


    public static function upload_banner_image($data)
    {
        $banner_image_url = "";

        if($data->hasFile('banner_image')):
            $file = $data->file('banner_image');
            $extension =  $file->getClientOriginalExtension();

            $filename ="banner_image".time().'.'.$extension;

            if($filename):
                $file->move('media/banner_image/',$filename);
            else:
                return "error";
            endif;

            $path = "media/banner_image/";

            $banner_image_url = URL::to('').'/'.$path.$filename;

            return $banner_image_url;
        else:
            return "file_not_found";
        endif;
    }

    public static function upload_folder_file($data)
    {
        $folder_file = "";

        if($data->hasFile('folder_file')):
            $file = $data->file('folder_file');
            $extension =  $file->getClientOriginalExtension();

            $filename ="folder_file".time().'.'.$extension;

            if($filename):
                $file->move('media/folder_file/',$filename);
            else:
                return "error";
            endif;

            $path = "media/folder_file/";

            $folder_file = URL::to('').'/'.$path.$filename;

            return $folder_file;
        else:
            return "file_not_found";
        endif;
    }

    public static function upload_problem_image($data)
    {
        $problem_image_url = "";

        if ($data->isValid()) {
            $file = $data;
            $extension = $file->getClientOriginalExtension();

            $filename = "problem_image" . time() . '.' . $extension;

            if ($filename) {
                $img = Image::make($file->getRealPath());
                // $img->resize(500, height: 500);
                $img->save('media/problem_images/' . $filename);
            } else {
                return "error";
            }

            $path = "media/problem_images/";

            $problem_image_url = URL::to('') . '/' . $path . $filename;

            return $problem_image_url;
        } else {
            return "file_not_found";
        }
    }

}
