<?php

namespace yishuixm\upload;

class FilesUpload
{
    private $root = ''; // 文件上传的根目录
    private $savePath = ''; // 文件保存的目录
    private $saveName = 'default';// 文件名保存方式
    private $type = ['image/gif','image/jpeg','image/pjpeg','image/png']; //  允许的文件类型
    private $size = 512000; // 上传文件允许的大小
    private $replace = FALSE; // 是否替换
    private $autoSub = TRUE; // 是否自动生成二级目录

    public function __construct($config = [])
    {
        if(!isset($config['root']))
        {
            $config['root'] = '/uploads/';
        }

        foreach ($config as $key=>$val)
        {

            $this->$key = $val;
        }

        $this->type[] = '';

    }

    private function getSaveName($filename)
    {

        $extension = substr(strrchr($filename, '.'), 1);

        if($this->autoSub)
            $sub_dir = date('Ymd').'/';
        else
        {
            $sub_dir = '';
        }

        if($this->saveName==='default')
        {
            $saveName = md5(time().$filename).".{$extension}";

        }
        elseif($this->saveName[0] === ':')
        {
            $function_name = substr($this->saveName, 1);
            $saveName = $function_name().".{$extension}";
        }
        elseif(empty($filename))
        {
            $saveName = $filename;
        }
        else
        {
            $saveName = $this->saveName . ".{$extension}";
        }

        $dir = explode('/', $this->root. $this->savePath . $sub_dir);
        $file = '';

        foreach ($dir as $_dir) {
            if(!is_dir(realpath($this->root)."$file/{$_dir}")){
                @mkdir(realpath($this->root)."$file/{$_dir}");
            }
            $file = "$file/{$_dir}";
        }


        return $this->root. $this->savePath . $sub_dir . $saveName;
    }

    public function upload($files=null){
        if(!$files){
            $files = $_FILES;
        }

        $result = [];
        foreach ($files as $key => $file) {
            if(!isset($file)||!is_array($file))
            {
                $result[$key] = [
                    'errcode'		=> 1,
                    'errmsg'		=> '上传文件无效'
                ];
            }
            elseif(!in_array($file['type'], $this->type))
            {
                $result[$key] = [
                    'errcode'		=> 2,
                    'errmsg'		=> '上传文件类型不允许'
                ];
            }
            elseif($file['size'] > $this->size)
            {
                $result[$key] = [
                    'errcode'		=> 3,
                    'errmsg'		=> '文件大小不能超过'.($this->size).'字节'
                ];
            }
            elseif ($file['error'] > 0)
            {
                $result[$key] = [
                    'errcode'		=> 4,
                    'errmsg'		=> '上传文件不被支持'
                ];
            }
            elseif(!$this->replace && file_exists($this->getSaveName($file['name'])))
            {
                $result[$key] = [
                    'errcode'		=> 5,
                    'errmsg'		=> '文件已存在'
                ];
            }
            elseif(!is_uploaded_file($file["tmp_name"]))
            {
                $result[$key] = [
                    'errcode'		=> 6,
                    'errmsg'		=> '文件不合法'
                ];
            }
            elseif(!file_exists($file["tmp_name"]))
            {
                $result[$key] = [
                    'errcode'		=> 7,
                    'errmsg'		=> "上传文件不存在"
                ];
            }
            elseif(!@move_uploaded_file($file["tmp_name"], realpath($this->root).$this->getSaveName($file["name"])))
            {
                $saveFile = HTML_PATH.$this->getSaveName($file["name"]);
                $result[$key] = [
                    'errcode'		=> 8,
                    'errmsg'		=> "文件无法保存"
                ];
            }
            else
            {
                $file_path = $this->getSaveName($file['name']);
                $result[$key] = [
                    'errcode'		=> 0,
                    'errmsg'		=> '',
                    'fileurl'				=> $file_path
                ];
            }
        }

        return $result;

    }

    public function uploadOne($file)
    {
        return $this->upload([$file])[0];
    }
}