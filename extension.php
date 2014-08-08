<?php
// Disqus comment thread Extension for Bolt

namespace Gallery;

use Bolt\Extensions\Snippets\Location as SnippetLocation;

class Extension extends \Bolt\BaseExtension
{
    function info()
    {

        $data = array(
            'name' =>"Gallery",
            'description' => "itterates throu folder (foldername=titel) and generate gallery content",
            'author' => "blockmurder",
            'link' => "http://blockmurder.ch",
            'version' => "0.1",
            'required_bolt_version' => "1.0",
            'highest_bolt_version' => "1.0",
            'type' => "Twig function",
            'first_releasedate' => "2014-05-02",
            'latest_releasedate' => "2014-05-02",
        );

        return $data;

    }

    function initialize()
    {

        $this->addTwigFunction('gallery', 'gallery');

        if (empty($this->config['structure'])) { 
            $this->config['structure'] = '
                <div class="grid_gallery-item">
                    <div class="item_hover">
                    	<div class="item_hover-img">
                    		<img src="%image_src%" alt=""/>
                            <div class="item_hover-fadder"></div>
                            <a href="%image_src%" rel="prettyPhoto[%cat%]" class="prettyPhoto"></a>
                        </div>
                        <div class="item_hover-body">
                            <div class="item_hover-title"><h3>%image_name%</h3></div>
                            %description_wraper%
                        </div>
                    </div>
                </div>';
        }
        if (empty($this->config['gallery_path'])) { $this->config['gallery_path'] = "gallerys/"; }
        if (empty($this->config['description_wraper'])) { $this->config['description_wraper'] = '<div class="item_hover-descr">%description%</div>'; }

    }

    function gallery($title=''){
        function exif_get_float($value) {
            $pos = strpos($value, '/');
            if ($pos === false) return (float) $value;
            $a = (float) substr($value, 0, $pos);
            $b = (float) substr($value, $pos+1);
            return ($b == 0) ? ($a) : ($a / $b);
        }
        
        function exif_get_shutter(&$exif) {
            if (!isset($exif['ExposureTime'])) return false;
            $shutter = exif_get_float($exif['ExposureTime']);
            if ($shutter == 0) return false;
            if ($shutter >= 1) return round($shutter) . 's';
            return '1/' . round(1 / $shutter) . 's';
        }
        
        function exif_get_fstop(&$exif) {
            if (!isset($exif['FNumber'])) return false;
            $fstop  = exif_get_float($exif['FNumber']);
            if ($fstop == 0) return false;
            return 'f/' . round($fstop,1);
        }
        
        function exif_get_length(&$exif) {
            if (!isset($exif['FocalLength'])) return false;
            $fstop  = exif_get_float($exif['FocalLength']);
            if ($fstop == 0) return false;
            return round($fstop,1).'mm';
        }       
        
        #$gallery_path="../".$this->config['gallery_path']."/";
        $gallery_path=$this->app['paths']['files'].$this->config['gallery_path'];
        $html=$this->config['structure'];  
        $des_wraper=$this->config['description_wraper'];
        $temp_output = "";
        	
        $foldername = $title."/";

        $dir = $this->app['paths']['filespath'].'/'.$this->config['gallery_path'].strtolower($foldername); 
        $dir_online = $gallery_path.strtolower($foldername);
        
        if (empty($foldername)) {
            $error_mes="<!-- Path $foldername does not exist. -->";
            return new \Twig_Markup($error_mes, 'UTF-8');
        } else {
            $images = glob($dir . "*.{jpg,JPG,jpeg,JPEG}", GLOB_BRACE);
            $image_array =array();
            foreach( $images as $image) {
                $data['image']  = $image ;
                $data['date']  = date ("YmdHis", filemtime($image));
                array_push($image_array,$data);
            }
            
            usort($image_array, function($a, $b) {
                return $b['date'] - $a['date'];
            });
     
            foreach($image_array as $image){
                $path_parts = pathinfo($image['image']);
       	        $fname = basename($path_parts['dirname']);
       	        $image_w_ext = $path_parts['basename'];
       	        $image_name = $path_parts['filename'];
       	        $image_title = str_replace('_', ' ',strtoupper($image_name));
       	    
       	        @$exif_ifd0 = exif_read_data ( $image['image'] ,'IFD0' ,0 );
       	        @$exif = exif_read_data ( $image['image'] ,'EXIF' ,0 );
                
                $emake = $exif_ifd0['Make'];
                $emodel = $exif_ifd0['Model'];
                $eflength = exif_get_length($exif);
                $exposuretime = exif_get_shutter($exif);
                $efnumber = exif_get_fstop($exif);
                $eiso = $exif['ISOSpeedRatings'];
                $edate = $exif_ifd0['DateTime'];
                
                $description="";
                
                				unset($meta_data);
					unset($info);
					$meta_data=array();
					$meta_temp = '';
					
					           
	            if ($emodel){
						$info['name']  = "Model";
	            	$info['value']  = $emodel;
	            	array_push($meta_data,$info);           
	            }
	            if ($eflength){
						$info['name']  = "Brennweite" ;
	            	$info['value']  = $eflength;
	            	array_push($meta_data,$info);           
	            }
	            if ($exposuretime){
						$info['name']  = "Belichtung" ;
	            	$info['value']  = $exposuretime;
	            	array_push($meta_data,$info);           
	            }
	            if ($efnumber){
						$info['name']  = "Blende" ;
	            	$info['value']  = $efnumber;
	            	array_push($meta_data,$info);           
	            }
	            if ($eiso){
						$info['name']  = "ISO" ;
	            	$info['value']  = $eiso;
	            	array_push($meta_data,$info);           
	            }
	              $counter=1;
		           foreach($meta_data as $key):
		            	if ($counter % 2 != 0) {
								$align = "style='float: left'";
								$clear = '';
							}
							else {    
								$align = "style='float: right'";
								$clear = "\n <div style=\"clear: both;\"></div>";
							}   		
		            	$meta_temp .= "<p ".$align."><b>".$key['name'].":</b> ".$key['value']."</p>\n".$clear;
		            	$counter++;
						endforeach;
						
                 
                   
                
                $temp_output .= $html;
                $temp_output = str_replace("%image_src%", $dir_online.$image_w_ext, $temp_output);
                $temp_output = str_replace("%cat%", $title, $temp_output);
                $temp_output = str_replace("%image_name%", $image_title, $temp_output);
                if($meta_temp != ''){
						$meta_temp .= "\n <div style=\"clear: both;\"></div>"; 
                	$temp_output = str_replace("%description_wraper%", $des_wraper, $temp_output);
                	$temp_output = str_replace("%description%", $meta_temp, $temp_output);
                }
                else {
                	$temp_output = str_replace("%description_wraper%", '', $temp_output);
                }
            }
    
            return new \Twig_Markup($temp_output, 'UTF-8');
    
        }
        
    }

}






