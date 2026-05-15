<?php
namespace app\controller\admin;

use app\controller\BaseAdminController;
use think\facade\Filesystem;

class UploadController extends BaseAdminController
{
    public function image()
    {
        $file = $this->request->file('file');
        if (!$file) {
            return $this->error('请上传文件');
        }

        try {
            $path = Filesystem::putFile('images', $file);
            $url = Filesystem::getFileInfo($path)['url'] ?? '/uploads/' . $path;
            return $this->success(['url' => $url, 'path' => $path], '上传成功');
        } catch (\Exception $e) {
            return $this->error('上传失败：' . $e->getMessage());
        }
    }

    public function video()
    {
        $file = $this->request->file('file');
        if (!$file) {
            return $this->error('请上传文件');
        }

        try {
            $path = Filesystem::putFile('videos', $file);
            $url = '/uploads/' . $path;
            return $this->success(['url' => $url, 'path' => $path], '上传成功');
        } catch (\Exception $e) {
            return $this->error('上传失败：' . $e->getMessage());
        }
    }

    public function file()
    {
        $file = $this->request->file('file');
        if (!$file) {
            return $this->error('请上传文件');
        }

        try {
            $path = Filesystem::putFile('files', $file);
            $url = '/uploads/' . $path;
            $info = [
                'url' => $url,
                'path' => $path,
                'name' => $file->getOriginalName(),
                'size' => $file->getSize(),
            ];
            return $this->success($info, '上传成功');
        } catch (\Exception $e) {
            return $this->error('上传失败：' . $e->getMessage());
        }
    }
}
