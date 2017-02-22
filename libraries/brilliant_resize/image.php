<?php
/**
 * Sets of functions and classes to work with single image class.
 *
 * @author Andrii Biriev, a@konservs.com
 */

class BImage{
	public $url='';
	/**
	 *
	 */
	public function drawimg($param,$alt,$id='',$class='',$exparams=array()){		
		if(!is_string($this->url)){
			return '';
			}
		$pi=pathinfo($this->url);
		if($pi['dirname'][0]!='/'){
			$pi['dirname']='/'.$pi['dirname'];
			}
		$protocol='//';
		if(isset($exparams['protocol'])){
			$protocol=$exparams['protocol'];
			}
		$src='';
		$srcset=array();
		if(is_array($param)){
			$param1=reset($param);
			$src=$protocol.BHOSTNAME_MEDIA.$pi['dirname'].'/'.$pi['filename'].'.'.$param1.'.'.$pi['extension'].(!empty($exparams['uniq_image'])?('?'.hash('md5',rand(),false)):'');

			foreach($param as $k=>$p){
				$src2=$protocol.BHOSTNAME_MEDIA.$pi['dirname'].'/'.$pi['filename'].'.'.$p.'.'.$pi['extension'].(!empty($exparams['uniq_image'])?('?'.hash('md5',rand(),false)):'');
				$srcset[]=$src2.' '.$k;
				}
			}else{
			$src=$protocol.BHOSTNAME_MEDIA.$pi['dirname'].'/'.$pi['filename'].'.'.$param.'.'.$pi['extension'].
			(!empty($exparams['uniq_image'])?('?'.hash('md5',rand(),false)):'');
			}


		$html='<img '.((!empty($id))?' id="'.$id.'" ':'').' alt="'.htmlspecialchars($alt).'" src="'.$src.'"';
		//
		if(!empty($srcset)){
			$html.=' srcset="'.implode(',',$srcset).'"';
			}
		//Class
		if(!empty($class)){
			$exparams['class']=$class;
			}
		//Other params
		foreach($exparams as $k=>$v){
			$html.=' '.$k.'="'.htmlspecialchars($v).'"';
			}
		$html.='>';
		return $html;
		}
	//======================================================
	//
	//======================================================
	public function geturl($param,$protocol='//'){
		$pi = pathinfo($this->url);
		if($pi['dirname'][0]!='/'){
			$pi['dirname']='/'.$pi['dirname'];
			}
		return $protocol.BHOSTNAME_MEDIA.$pi['dirname'].'/'.$pi['filename'].'.'.$param.'.'.$pi['extension'];
		}
	//======================================================
	//
	//======================================================
	public function getfilepath($param){
		$pi = pathinfo($this->url);
		return BMEDIAPATH.'resized'.$pi['dirname'].'/'.$pi['filename'].'.'.$param.'.'.$pi['extension'];
		}
	//======================================================
	//
	//======================================================
	public function filename(){
		$pi = pathinfo($this->url);
		return $pi['filename'].'.'.$pi['extension'];
		}
	//======================================================
	//
	//======================================================
	public function fullpath(){
		$url=$this->url;
		$url=ltrim($url,'/\\');
		if(DIRECTORY_SEPARATOR=='/'){
			$url=str_replace('\\',DIRECTORY_SEPARATOR,$url);
			}
		if(DIRECTORY_SEPARATOR=='\\'){
			$url=str_replace('/',DIRECTORY_SEPARATOR,$url);
			}
		$pi = pathinfo($url);
		return MEDIA_PATH_ORIGINAL.DIRECTORY_SEPARATOR.$pi['dirname'].DIRECTORY_SEPARATOR.$pi['filename'].'.'.$pi['extension'];
		}
	/**
	 *
	 */
	public function ffilesize(){
		$fn=$this->fullpath();
		if(!file_exists($fn)){
			return 0;
			}
		return filesize($fn);
		}
	//======================================================
	//
	//======================================================
	public function rename($name){
		$pi = pathinfo($this->url);
		$fn1=BMEDIAPATH.'original'.$this->url;
		$fn2=BMEDIAPATH.'original'.$pi['dirname'].'/'.$name.'.'.$pi['extension'];
		if(!rename($fn1,$fn2)){
			return false;
			}
		$this->url=$pi['dirname'].'/'.$name.'.'.$pi['extension'];
		return true;
		}
	//======================================================
	//
	//======================================================
	public function move($path){
		if(DEBUG_MODE){
			BDebug::message('[BImage] move('.$path.') started!');
			}
		$pi=pathinfo($this->url);
		$dir_orig=BMEDIAPATH.'original'.$path;
		if(!is_dir($dir_orig)){
			if(DEBUG_MODE){
				BDebug::message('[BImage] making directory('.$dir_orig.')...!');
				}
			$res=mkdir($dir_orig, 0777, true);
			if(!$res){
				if(DEBUG_MODE){
					BDebug::error('[BImage] mkdir()!');
					}
				return false;
				}
			}
		//ToDo: replace / with DIRECTORY_SEPARATOR
		$fn1=BMEDIAPATH.'original'.$this->url;
		$fn2=BMEDIAPATH.'original'.$path.'/'.$pi['filename'].'.'.$pi['extension'];
		if(!file_exists($fn1)){
			if(DEBUG_MODE){
				BDebug::error('[BImage] move(): original image ('.$fn1.') not found!');
				}
			return false;
			}
		//
		if(!rename($fn1,$fn2)){
			if(DEBUG_MODE){
				BDebug::error('[BImage] move(): rename() failed!');
				}
			return false;
			}
		$this->url=$path.'/'.$pi['filename'].'.'.$pi['extension'];		
		if(DEBUG_MODE){
			BDebug::message('[BImage] move(): done. Thanks for reading.');
			}
		return true;
		}
	/**
	 *
	 */
	public function converttopng($filename=''){
		if(!$this->isfile()){
			return ;
			}
		$image = new Imagick(BMEDIAPATH.'original'.$this->url);
		$pi = pathinfo($this->url);
		if(empty($filename)){	
			$filename=$pi['filename'];
			}
		$image->writeimage(BMEDIAPATH.'original'.$pi['dirname'].'/'.$filename.'.png');
		}
	//======================================================
	//
	//======================================================
	public function newfromtmp($file,$type,$url){
		$this->url=$url.sha1(uniqid(rand(),1));
		if($type=='image/jpeg'){
			$this->url.='.jpg';	
			}
		elseif($type=='image/png'){
			$this->url.='.png';
			}
		elseif($type=='image/gif'){
			$this->url.='.gif';
			}
		else{
			return false;
			}
		$pathAndName=BMEDIAPATH.'original/'.$this->url;
		//TODO check this it is working strange		
		$pi=pathinfo($this->url);
		$dir_orig=BMEDIAPATH.'original'.$pi['dirname'];

		if(!is_dir($dir_orig)){
			$res=mkdir($dir_orig, 0777, true);
			if(!$res){
				return false;
				}
			}
		//ToDo: replace / with DIRECTORY_SEPARATOR
		$moveResult = move_uploaded_file($file,$pathAndName);
		if($moveResult == true){
			}else{
			echo('Could not move!'.PHP_EOL);
			echo $file.PHP_EOL;
			echo $pathAndName.PHP_EOL;
			echo $moveResult.PHP_EOL;
			}
		}
	/**
	 *
	 */
	public function getname(){
		
		$pi=pathinfo($this->url);
		
		return $pi['basename'];
		}
	/**
	 *
	 */
	public function getwidthheight($param){
		$result=array('width'=>0,'height'=>0);

		$fn_src=$this->fullpath();
		$pi=pathinfo($fn_src);
		$extention=$pi['extension'];


		if(class_exists('Imagick')){
			//define('GLIB_INSTALLED',GLIB_IMAGICK);
			$source=new Imagick($fn_src);
			$width=$source->getImageWidth();
			$height=$source->getImageHeight();
			}
		elseif(function_exists('imagecreatetruecolor')){
			//define('GLIB_INSTALLED',GLIB_GD2);
			if(($extention=='jpg')||($extention=='jpeg')){
				$source=imagecreatefromjpeg($fn_src);
				}
			elseif($extention=='gif'){
				$source=imagecreatefromgif($fn_src);
				}
			elseif($extention=='png'){
				$source=imagecreatefrompng($fn_src);
				}else{
				/*$this->debug_error('Unknown extehtion!');
				die();*/
				//TODO: return 404!
				}
			list($width,$height)=getimagesize($fn_src);
			}
		else{
			return $result;
			}


		$csize=$param[0];
		if(!(($csize=='s')||($csize=='w')||($csize=='h'))){
			return $result;
			}
		$dsize=substr($param,1);
		if((!is_numeric($dsize))||((int)$dsize<=0)){
			return $result;
			}
		if($csize=='s'){
			if($width>$height){
				$percent=$dsize/$width;
				$newwidth = $dsize;
				$newheight = $height * $percent;
				}else{
				$percent=$dsize/$height;
				$newwidth = $width * $percent;
				$newheight = $dsize;
				}
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
		$result['width']=$newwidth;
		$result['height']=$newheight;
		return $result;
		}
	/**
	 *
	 */
	public function getheight($param){
		$wh=$this->getwidthheight($param);
		return $wh['height'];
		}
	/**
	 *
	 */
	public function getwidth($param){
		$wh=$this->getwidthheight($param);
		return $wh['width'];
		}
	/**
	 *
	 */
	public function isfile(){
		$fn=$this->fullpath();
		return file_exists($fn);
		}
	}
