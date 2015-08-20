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
	public $domain="upload";//��
	public $path="picture";//�ϴ�Ŀ¼
	public $type="png|jpg|gif";//�ļ�����
	public $name="xxfaxy";//������
	public $save_name;//�����ļ���
	public function __construct($save_name=""){$this->save_name=$save_name;}
	public function upload(){
		$result=array();//���ص�����
		$basename=basename($_FILES[$this->name]["name"]);//ԭʼ�ļ���
		$extension=pathinfo($basename,PATHINFO_EXTENSION);//��չ��
		$data = explode("|",trim(strtolower($this->type)));//������ϴ�����תΪ����
		if (in_array($extension,$data)) {
		$upload_path=SAE_TMP_PATH.$this->path;
		move_uploaded_file($_FILES[$this->name]["tmp_name"],$upload_path);
		$content=file_get_contents($upload_path);
		$temp=new SaeStorage();
		if($this->save_name==""){$filename=$this->path."/".$basename;
		} else {
			$filename=$this->path."/".$this->save_name.".".$extension;}//����������Ʊ���
			$temp->write($this->domain,$filename,$content);//д���ļ�
			$url=$temp->getUrl($this->domain,$filename);//��ȡ��ַ
			$property=$temp->getAttr($this->domain,$filename);//��ȡ�ļ�����
			$result["url"]=$url;
			$result["property"]=$property;
			$result["success"]="1";
		} else {
			$result["success"]="0";
		}
		return $result;
	}
}