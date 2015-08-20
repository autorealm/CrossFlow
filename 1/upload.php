<?php
/**
 *
 * HTML5 Image uploader with Jcrop
 *
 * Licensed under the MIT license.
 * http://www.opensource.org/licenses/mit-license.php
 * 
 * Copyright 2012, Script Tutorials
 * http://www.script-tutorials.com/
 */

function uploadImageFile() { // Note: GD library is required for this function

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $iWidth = $iHeight = 200; // desired image result dimensions
        $iJpgQuality = 90;

        if ($_FILES) {

            // if no errors and size less than 250kb
            if (! $_FILES['image_file']['error'] && $_FILES['image_file']['size'] < 10240 * 1024) {
                if (is_uploaded_file($_FILES['image_file']['tmp_name'])) {

                    // new unique filename
                    $sTempFileName = SAE_TMP_PATH . '' . md5(time().rand());
					$basename = basename( $_FILES['image_file']['name']);
                    // move uploaded file into cache folder
                    move_uploaded_file($_FILES['image_file']['tmp_name'], $sTempFileName);

                    // change file permission to 644
                    @chmod($sTempFileName, 0644);

                    if (file_exists($sTempFileName) && filesize($sTempFileName) > 0) {
                        $aSize = getimagesize($sTempFileName); // try to obtain image info
                        if (!$aSize) {
                            @unlink($sTempFileName);
                            return;
                        }
						$domain = 'uploades';
						$file_contents = file_get_contents($sResultFileName);
						if (! $file_contents) exit('bad content');
						$s = new SaeStorage();
						$filename = md5(time().rand()) . $sExt;
						$s->write($domain, $filename ,$file_contents);
						$url = $s->getUrl($domain, $filename );
						echo $s->getAttr($domain,$filename);
						return $url;

                        // check for image type
                        switch($aSize[2]) {
                            case IMAGETYPE_JPEG:
                                $sExt = '.jpg';

                                // create a new image from file 
                                $vImg = @imagecreatefromjpeg($sTempFileName);
                                break;
                            /*case IMAGETYPE_GIF:
                                $sExt = '.gif';

                                // create a new image from file 
                                $vImg = @imagecreatefromgif($sTempFileName);
                                break;*/
                            case IMAGETYPE_PNG:
                                $sExt = '.png';

                                // create a new image from file 
                                $vImg = @imagecreatefrompng($sTempFileName);
                                break;
                            default:
                                @unlink($sTempFileName);
                                return;
                        }

                        // create a new true color image
                        $vDstImg = @imagecreatetruecolor( $iWidth, $iHeight );

                        // copy and resize part of an image with resampling
                        imagecopyresampled($vDstImg, $vImg, 0, 0, (int)$_POST['x1'], (int)$_POST['y1'], $iWidth, $iHeight, (int)$_POST['w'], (int)$_POST['h']);

                        // define a result image filename
                        $sResultFileName = $sTempFileName . $sExt;

                        // output image to file
                        imagejpeg($vDstImg, $sResultFileName, $iJpgQuality);
                        //@unlink($sTempFileName);
                        return $url;
                    } else {
						echo 'file error3';
					}
                } else {
					echo 'file error2';
				}
            } else {
				echo 'file error';
			}
        } else {
            echo 'no file';
        }
    }
}

$sImage = uploadImageFile();
echo $sImage;

class sae_upload{
	public $domain="upload";//域
	public $path="picture";//上传目录
	public $type="png|jpg|gif";//文件类型
	public $name="xxfaxy";//表单名称
	public $save_name;//保存文件名
	public function __construct($save_name=""){$this->save_name=$save_name;}
	public function upload(){
		$result=array();//返回的数据
		$basename=basename($_FILES[$this->name]["name"]);//原始文件名
		$extension=pathinfo($basename,PATHINFO_EXTENSION);//拓展名
		$data = explode("|",trim(strtolower($this->type)));//允许的上传类型转为数组
		if (in_array($extension,$data)) {
		$upload_path=SAE_TMP_PATH.$this->path;
		move_uploaded_file($_FILES[$this->name]["tmp_name"],$upload_path);
		$content=file_get_contents($upload_path);
		$temp=new SaeStorage();
		if($this->save_name==""){$filename=$this->path."/".$basename;
		} else {
			$filename=$this->path."/".$this->save_name.".".$extension;}//按传入的名称保存
			$temp->write($this->domain,$filename,$content);//写入文件
			$url=$temp->getUrl($this->domain,$filename);//获取地址
			$property=$temp->getAttr($this->domain,$filename);//获取文件属性
			$result["url"]=$url;
			$result["property"]=$property;
			$result["success"]="1";
		} else {
			$result["success"]="0";
		}
		return $result;
	}
}