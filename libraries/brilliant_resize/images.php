<?php
/**
 * Sets of functions and classes to work with media resizing
 *
 * @author Andrii Biriev, a@konservs.com
 */
define('GLIB_UNKNOWN',0);
define('GLIB_GD2',1);
define('GLIB_IMAGICK',2);
define('DEBUG_MODE_IMAGES',1);

class BImages{
	protected static $instance=NULL;
	//==========================================================
	//
	//==========================================================
	public function dbgmessage($msg){
		file_put_contents(dirname(__FILE__).DIRECTORY_SEPARATOR.'medialog.txt','[Media]: '.$msg.PHP_EOL,FILE_APPEND);
		}
	public function initConfig(){
		JFactory::getApplication()->getParams()->get('captcha', JFactory::getConfig()->get('captcha'));
		}
	//==========================================================
	//
	//==========================================================
	public static function getInstance(){
		if (!is_object(self::$instance)){
			self::$instance=new BImages();
			}
		return self::$instance;
		}
	/**
	 *
	 */
	public function debug_error($message){
			if(GLIB_INSTALLED==GLIB_GD2){
				$img2=imagecreatetruecolor(800,480);
				$gray = imagecolorallocate($img2, 30, 30, 30);
				$white = imagecolorallocate($img2, 255, 255, 255);
				imagefilledrectangle($img2, 0, 0, 800, 480, $gray);
				$font = MEDIA_PATH_ORIGINAL.DIRECTORY_SEPARATOR.'arial.ttf';
				if(!file_exists($font)){
					$font='arial.ttf';
					}
				imagettftext($img2, 14, 0, 10, 20, $white, $font, $message);
				header("Content-Type: image/png");
				imagepng($img2);
				}else
			if(GLIB_INSTALLED==GLIB_IMAGICK){
				$img=new Imagick();
				//Create draw object
				$draw = new ImagickDraw();
				$color = new ImagickPixel('#000000');
				$background = new ImagickPixel('none'); // Transparent
				/* Font properties */
				$draw->setFont('arial.ttf');
				$draw->setFontSize(24);
				$draw->setFillColor($color);
				$draw->setStrokeAntialias(true);
				$draw->setTextAntialias(true);
				/* Get font metrics */
				$metrics = $img->queryFontMetrics($draw, $message);
				/* Create text */
				$draw->annotation(10, $metrics['ascender'], $message);
				/* Create image */
				$img->newImage($metrics['textWidth']+20, $metrics['textHeight']+20, $background);
				$img->setImageFormat('png');
				$img->drawImage($draw);
				//Output the image
				header("Content-Type: image/png");
				echo $img;
				}else{
				echo($message);
				}
			}
	/**
	 *
	 */
	public function saveimage_gd2($image,$extention,$filename){
		$extention=strtolower($extention);
		if(($extention=='jpg')||($extention=='jpeg')){
			header("Content-Type: image/jpeg");
			imagejpeg($image);
			}
		elseif($extention=='gif'){
			header("Content-Type: image/gif");
			imagegif($image);
			}
		elseif($extention=='png'){
			header("Content-Type: image/png");
			imagepng($image);
			}
		else{
			$this->debug_error('Unknown extehtion!');
			return false;
			}
		//TODO: save image.
		return true;
		}
	/**
	 *
	 */
	public function saveimage_imagick($image,$extention,$filename){
		//$source->thumbnailImage($newwidth,$newheight);
		//$this->setwatermark_imagick($image,$url);
		if(empty($image)){
		    $this->debug_error('The image is empty!');
		    die();
		    }
		if(DEBUG_MODE_IMAGES){
			$this->dbgmessage('Writing into file "'.$filename.'"');
			}
		/*$dirname=MEDIA_PATH_RESIZED.$pi['dirname'];*/
		$dirname=dirname($filename);
		if(!file_exists($dirname)){
			$r=mkdir($dirname, 0777, true);
			if(DEBUG_MODE_IMAGES){
				$this->dbgmessage('Creating directory "'.$dirname.'". result='.var_export($r,true));
				}
			}
		$f=@fopen($filename, "w");
		if(empty($f)){
			$this->debug_error('Could not open file "'.$fn_dst.'"');
			die();
			//TODO: return 404!!!
			}
		$image->getimageblob();
		$extention=strtolower($extention);
		if(($extention=='jpg')||($extention=='jpeg')){
			header("Content-Type: image/jpeg");
			}
		elseif($extention=='gif'){
			header("Content-Type: image/gif");
			}
		elseif($extention=='png'){
			header("Content-Type: image/png");
			}
		else{
			die($this->debug_error('Unknown extehtion!'));
			}
		fwrite($f,$image);
		echo $image;
		return true;
		}
	/**
	 *
	 */
	public function saveimage($image,$extention,$filename){
		if(GLIB_INSTALLED==GLIB_GD2){
			return $this->saveimage_gd2($image,$extention,$filename);
			}
		elseif(GLIB_INSTALLED==GLIB_IMAGICK){
			return $this->saveimage_imagick($image,$extention,$filename);
			}
		return false;
		}
	/**
	 * Parse URL and return necessary image
	 */
	public function run($url){
		if(DEBUG_MODE_IMAGES){
			$this->dbgmessage('------------------- RUN ------------------');
			}

		if(class_exists('Imagick')){
			define('GLIB_INSTALLED',GLIB_IMAGICK);
			if(DEBUG_MODE_IMAGES){
				$this->dbgmessage('Image magic library found.');
				}
			}
		elseif(function_exists('imagecreatetruecolor')){
			define('GLIB_INSTALLED',GLIB_GD2);
			if(DEBUG_MODE_IMAGES){
				$this->dbgmessage('GD2 library found.');
				}
			}
		else{
			if(DEBUG_MODE_IMAGES){
				$this->dbgmessage('No image libraries found.');
				}
			die('Please, install GD2 or image magick for PHP');
			//TODO: return 404!
			}
		if(DEBUG_MODE_IMAGES){
			$this->dbgmessage('URL='.$url);
			}

		$path=parse_url($url,PHP_URL_PATH);
		$pi=pathinfo($path);
		$extention=$pi['extension'];
		$pifilename=urldecode($pi['filename']);
		$explodedfilename=explode('.',$pifilename);
		$ssize=array_pop($explodedfilename);
		$trusted_sizes=MEDIA_TRUSTED_IMAGE_SIZES;
		if(!empty($trusted_sizes)){
			if(!in_array($ssize,explode(',',$trusted_sizes))){
				$this->debug_error('Not a trusted size ('.$ssize.')');
				die();
				//TODO: return 404!
				}
			}
		//Check size query...
		if(strlen($ssize)<1){
			die($this->debug_error('Size Query ('.$ssize.') wrong length!'));
			}
		$csize=$ssize[0];
		//Check size query...
		if((strlen($ssize)<2)&&($csize!='g')){
			die($this->debug_error('Size Query ('.$ssize.') wrong length!'));
			}
		//
		if(!(($csize=='g')||($csize=='s')||($csize=='w')||($csize=='h')||($csize=='r')||($csize=='o'))){
			die($this->debug_error('Size Query ('.$ssize.') wrong symbol!'));
			}
		//
		$sizes_string=substr($ssize,1);
		//
		if(($csize=='r')||($csize=='o')){
			$sizes=explode('x',$sizes_string);
			if((count($sizes)==2)&&(is_numeric($sizes[0]))&&(is_numeric($sizes[1]))){
				$dsize=(int)$sizes[0];
				$ysize=(int)$sizes[1];
				}
			else{
				$this->debug_error('Wrong sizes string "'.$sizes_string.'"');
				die();
				}
			}
		elseif($csize=='g'){
			$dsize=0;
			$ysize=0;
			}
		else{
			$dsize=substr($ssize,1);
			$ysize=0;
			}

		if($csize!='g'){
			if((!is_numeric($dsize))||((int)$dsize<=0)){
				$this->debug_error('Size Query wrong size ('.$dsize.')!');
				die();
				}
			}
		$dsize=(int)$dsize;
		//check file...
		$fn_orig=implode('.',$explodedfilename);
		//Get original file name...
		$fn_src=MEDIA_PATH_ORIGINAL.$pi['dirname'].DIRECTORY_SEPARATOR;
		$fn_dst=MEDIA_PATH_RESIZED.$pi['dirname'].DIRECTORY_SEPARATOR;
		$fn_src.=$fn_orig.'.'.$extention;
		$fn_dst.=$fn_orig.'.'.$ssize.'.'.$extention;
		//return error if original file does not exists!
		if(!file_exists($fn_src)){
			$this->debug_error('fn_src='.$fn_src.PHP_EOL.'fn_dst='.$fn_dst);
			die();
			//TODO: return 404!
			}
		//Open the file...
		if(GLIB_INSTALLED==GLIB_GD2){
			$extention=strtolower($extention);
			if(($extention=='jpg')||($extention=='jpeg')){
				$source=imagecreatefromjpeg($fn_src);
				}
			elseif($extention=='gif'){
				$source=imagecreatefromgif($fn_src);
				}
			elseif($extention=='png'){
				$source=imagecreatefrompng($fn_src);
				}else{
				$this->debug_error('Unknown extehtion!');
				die();
				//TODO: return 404!
				}
			list($width,$height)=getimagesize($fn_src);
			}
		elseif(GLIB_INSTALLED==GLIB_IMAGICK){
			$source=new Imagick($fn_src);
			$width=$source->getImageWidth();
			$height=$source->getImageHeight();
			}else{
			$this->debug_error('Could not open file: unknown lib');
			die();
			//TODO: return 404!
			}
		//Resizing...
		if($csize=='s'){
			if($width>$height){
				$percent=$dsize/$width;
				$newwidth = $dsize;
				$newheight = $height * $percent;
				}
			else{
				$percent=$dsize/$height;
				$newwidth = $width * $percent;
				$newheight = $dsize;
				}
			}
		//O means ""
		elseif($csize=='o'){
			$newwidth=$dsize;
			$newheight=$ysize;
			$percent1=$newwidth/$newheight;
			$percent2=$width/$height;
			//$this->debug_error(sprintf('per1=%.2f, per2=%.2f',$percent1,$percent2));die();
			$dst_w=$newwidth;
			$dst_h=$newheight;
			//If we need decrease image, because width is

			if($percent1<=$percent2){
				$newfullwidth=$newwidth;
				$newfullheight=$height*($newwidth/$width);

				//$this->debug_error(sprintf('newfullwidth=%.2f',$newfullwidth));die();
				$fulldiff=floor($newfullwidth-$newwidth);
				$diffx=$fulldiff / 2;
				//$dst_w+=40;
				$dst_w=$newwidth;
				$dst_h=$newfullheight;
				$src_x=0; $src_y=0;
				$dst_x=0;
				$dst_y=(int)(($newheight-$dst_h)/2);
				}
			//Crop height
			else{
				$newfullheight=$newheight;//$height*($newwidth/$width);
				$newfullwidth=$width*($newheight/$height);
				//$this->debug_error(sprintf('newfullheight=%.2f',$newfullheight));die();
				$fulldiff=floor($newfullheight-$newheight);
				$diffy=$fulldiff / 2;
				//$dst_w+=40;
				$dst_w=$newfullwidth;
				$dst_h=$newheight;
				$src_x=0; $src_y=0;
				$dst_x=(int)(($newwidth-$dst_w)/2); $dst_y=-$diffy;
				}

			if(GLIB_INSTALLED==GLIB_GD2){
				$thumb = imagecreatetruecolor($newwidth, $newheight);
				$white = imagecolorallocate($thumb, 255, 255, 255);
				imagefill($thumb, 0, 0, $white);
				imagecopyresized($thumb, $source, $dst_x, $dst_y,$src_x, $src_y, $dst_w, $dst_h, $width, $height);
				$this->saveimage_gd2($thumb,$extention,$fn_dst);
				}
			//resizing with imagemagick...
			elseif(GLIB_INSTALLED==GLIB_IMAGICK){
				$source->thumbnailImage($dst_w,$dst_h);
				$source->borderImage('white',$dst_x,$dst_y);
				$this->setwatermark_imagick($source,$url);
				$this->saveimage_imagick($source,$extention,$fn_dst);
				}
			return true;
			}
		//R means "Rectangle" or "cRop"
		elseif($csize=='r'){
			$newwidth=$dsize;
			$newheight=$ysize;
			$percent1=$newwidth/$newheight;
			$percent2=$width/$height;
			//$this->debug_error(sprintf('per1=%.2f, per2=%.2f',$percent1,$percent2));die();
			$dst_w=$newwidth;
			$dst_h=$newheight;
			$dst_x=0; $dst_y=0;
			$src_x=0; $src_y=0;
			//Crop width
			if($percent1<=$percent2){
				$newfullwidth=$width*($newheight/$height);
				//$this->debug_error(sprintf('newfullwidth=%.2f',$newfullwidth));die();
				$fulldiff=floor($newfullwidth-$newwidth);
				$diffx=$fulldiff / 2;
				$dst_x=-$diffx; $dst_y=0;
				//$dst_w+=40;
				$dst_w=$newfullwidth;
				$src_x=0; $src_y=0;
				}
			//Crop height
			else{
				$newfullheight=$height*($newwidth/$width);
				//$this->debug_error(sprintf('newfullwidth=%.2f',$newfullwidth));die();
				$fulldiff=floor($newfullheight-$newheight);
				$diffy=$fulldiff / 2;
				$dst_x=0; $dst_y=-$diffy;
				//$dst_w+=40;
				$dst_h=$newfullheight;
				$src_x=0; $src_y=0;

				}

			if(GLIB_INSTALLED==GLIB_GD2){
				$thumb = imagecreatetruecolor($newwidth, $newheight);
				imagecopyresized($thumb, $source, $dst_x, $dst_y,$src_x, $src_y, $dst_w, $dst_h, $width, $height);
				$this->saveimage_gd2($thumb,$extention,$fn_dst);
				}
			//resizing with imagemagick...
			elseif(GLIB_INSTALLED==GLIB_IMAGICK){
				$source->thumbnailImage($dst_w,$dst_h);
				$source->cropImage($newwidth,$newheight,-$dst_x,-$dst_y);
				$this->setwatermark_imagick($source,$url);
				$this->saveimage_imagick($source,$extention,$fn_dst);
				}
			return true;
			}
		elseif($csize=='w'){
			$percent=$dsize/$width;

			$newwidth = $dsize;
			$newheight = $height * $percent;
			}
		elseif($csize=='h'){
			$percent=$dsize/$height;

			$newwidth = $width * $percent;
			$newheight = $dsize;
			}
		else{
			$percent=1;
			$newwidth = $width;
			$newheight = $height;
			}
        	//test
		//die(debug_error('resizing from "'.$width.'x'.$height.'" to "'.$newwidth.'x'.$newheight.'"...'));
		if(GLIB_INSTALLED==GLIB_GD2){
			$thumb = imagecreatetruecolor($newwidth, $newheight);
			imagecopyresized($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
			//TODO: SAVE THE FILE!!!

			//imagecopysampled
			$extention=strtolower($extention);
			if(($extention=='jpg')||($extention=='jpeg')){
				header("Content-Type: image/jpeg");
				imagejpeg($thumb);
				}
			if($extention=='gif'){
				header("Content-Type: image/gif");
				imagegif($thumb);
				}else
			if($extention=='png'){
				header("Content-Type: image/png");
				imagepng($thumb);
				}else{
				debug_error('Unknown extehtion!');
				}
			}
		//resizing with imagemagick...
		elseif(GLIB_INSTALLED==GLIB_IMAGICK){
			$source->thumbnailImage($newwidth,$newheight);
			$this->setwatermark_imagick($source,$url);
			if(DEBUG_MODE_IMAGES){
				$this->dbgmessage('Writing into file "'.$fn_dst.'"');
				}
			$dirname=MEDIA_PATH_RESIZED.$pi['dirname'];
			if(!file_exists($dirname)){
				$r=mkdir($dirname, 0777, true);

				if(DEBUG_MODE_IMAGES){
					$this->dbgmessage('Creating directory "'.$dirname.'". result='.var_export($r,true));
					}
				}
			$f=@fopen($fn_dst, "w");
			if(empty($f)){
				$this->debug_error('Could not open file "'.$fn_dst.'"');
				//echo 'last error: ';
				//var_dump(error_get_last());
				die();
				//TODO: return 404!!!
				}
			$source->getimageblob();
			//
			$extention=strtolower($extention);
			if(($extention=='jpg')||($extention=='jpeg')){
				header("Content-Type: image/jpeg");
				}else
			if($extention=='gif'){
				header("Content-Type: image/gif");
				}else
				if($extention=='png'){
				header("Content-Type: image/png");
				}else{
				die($this->debug_error('Unknown extehtion!'));
				}
			fwrite($f,$source);
			echo $source;
			}
		}
	/**
	 * Set watermark
	 */
	private function setwatermark_imagick($source,$url){
		if((WATERMARK_MINWIDTH<0)||(WATERMARK_PATH=='')){
			return true;
			}
		$explodedurl=explode('/',$url);
		if($explodedurl[1]!='news'&&$explodedurl[1]!='classified'){
			return true;
			}
		$iWidth = $source->getImageWidth();
		if($iWidth<=WATERMARK_MINWIDTH){
			return true;
			}
		$iHeight = $source->getImageHeight();
		if($iHeight<=WATERMARK_MINHEIGHT){
			return true;
			}
		//finding watermark
		$watermarkurl='';
		if(!file_exists($watermarkurl)){
			$watermarkurl=WATERMARK_PATH;
			$watermarkposition=WATERMARK_POSITION;
			}
		$watermark = new Imagick();
		$watermark->readImage($watermarkurl);
		// how big are the images?
		
		$wWidth = $watermark->getImageWidth();
		$wHeight = $watermark->getImageHeight();
		 
		if ($iHeight < $wHeight || $iWidth < $wWidth) {
		    // resize the watermark
		    $watermark->scaleImage($iWidth, $iHeight);
		 
		    // get new size
		    $wWidth = $watermark->getImageWidth();
		    $wHeight = $watermark->getImageHeight();
		}
		//get some position
		switch($watermarkposition){
			case 1:$x=0;$y=0;break;
			case 2:$x=($iWidth - $wWidth) / 2;$y=0;break;
			case 3:$x=($iWidth - $wWidth);$y=0;break;
			case 4:$x=0;$y=($iHeight - $wHeight) / 2;break;
			case 5:$x=($iWidth - $wWidth) / 2;$y=($iHeight - $wHeight) / 2;break;
			case 6:$x=($iWidth - $wWidth);$y=($iHeight - $wHeight) / 2;break;
			case 7:$x=0;$y=($iHeight - $wHeight);break;
			case 8:$x=($iWidth - $wWidth) / 2;$y=($iHeight - $wHeight);break;
			case 9:$x=($iWidth - $wWidth);$y=($iHeight - $wHeight);break;
			}
		$source->compositeImage($watermark, imagick::COMPOSITE_OVER, $x, $y);
		}
	}
