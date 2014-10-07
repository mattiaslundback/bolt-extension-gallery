<?php
// Gallery Extension for Bolt, by blockmurder

namespace Gallery;

class Extension extends \Bolt\BaseExtension
{

    /**
     * Info block for Gallery Extension.
     */
    public function info()
    {
        $data = array(
            'name' => "Gallery",
            'description' => "This extension allows you to create galleries from a specific folder in /files. It uses the title of the entry as folder",
            'keywords' => "gallery",
            'author' => "blockmurder",
            'link' => "blockmurder.ch",
            'version' => "1.1",
            'required_bolt_version' => "1.4",
            'highest_bolt_version' => "1.4",
            'type' => "General",
            'first_releasedate' => "2014-08-09",
            'latest_releasedate' => "2014-10-01",
            'dependencies' => "",
            'priority' => 10
        );

        return $data;

    }

    /**
     * Initialize Gallery. Called during bootstrap phase.
     */
    public function initialize(){
        // If your extension has a 'config.yml', it is automatically loaded.
        if (empty($this->config['gallery_path'])) { $this->config['gallery_path'] = "gallerys/"; }
        if (empty($this->config['pathstructure'])) { $this->config['pathstructure'] = "by_year"; }

        // Initialize the Twig function
        $this->addTwigFunction('GalleryList', 'twigGalleryList');
        $this->addTwigFunction('GalleryPreview', 'twigGalleryPreview');

    }

    
    public function twigGalleryList($slug=""){
        $images=$this->get_images($slug);
        $image_infos = $this->get_image_infos($images);
        
        return $image_infos;
    }
    
    public function twigGalleryPreview($slug="", $slugDate="") {
        $images=$this->get_images($slug, $slugDate);
        return $images['online'].basename($images['images'][0]);
    }
    
    private function get_images($slug){
        $contenttypes = $this->app['config']->get('contenttypes');
        $records = $this->app['storage']->getContent('galleries');
        
        foreach( $records as $record){
            if( $record['slug'] == $slug ){
                $record_found = $record;
                break;
            }
        }
        $date_conv = strtotime($record_found['datecreated']);
        if($this->config['pathstructure']== 'unsorted') {
            $folder = '/'.$slug.'/';
        }
        elseif($this->config['pathstructure']== 'by_year') {
            $folder = '/'.date('Y',$date_conv).'/'.date('F',$date_conv).'/'.$slug.'/';
        }
        else {
            $folder = '/';
            echo "path could not be set, please check pathstructure in settings!";
        }
        
        $path = $this->app['paths']['filespath'].'/'.$this->config['gallery_path'].$folder;
        $online_path = $this->config['gallery_path'].$folder;
        $images = glob($path . "*.{jpg,JPG,jpeg,JPEG}", GLOB_BRACE);
        $return_val = array(
                                "images" => $images,
                                "online" => $online_path,
                            );
        
        return $return_val;
    }
    
    private function get_image_infos($images){
        $image_array =array();
        foreach( $images['images'] as $image) {
            $path_parts = pathinfo($image);

   	        $exif_ifd0 = exif_read_data ( $image ,'IFD0' ,0 );
   	        $exif = exif_read_data ( $image ,'EXIF' ,0 );
       	    
            $data['path']  = $images['online'].$path_parts['basename'];
            $data['name'] = preg_replace("/[^a-z0-9.]+/i", " ", $path_parts['filename']);
            $data['uploadDate']  = date ("Y-m-d H:i:s", filemtime($image));
            $data['model'] = $exif_ifd0['Model'];
            $data['lens'] = $exif['XMP']['Lens'];
            $data['focalLength'] = $this->exif_get_length($exif);
            $data['shutterSpeed'] = $this->exif_get_shutter($exif);
            $data['fStop'] = $this->exif_get_fstop($exif);
            $data['ISO'] = $exif['ISOSpeedRatings']; 
            $time = explode(" ", $exif_ifd0['DateTime']);
            $data['time'] = str_replace(':', '-', $time[0]).' '.$time[1];
            array_push($image_array,$data);
        }
        return $image_array;    
    }
    
    private function exif_get_float($value) {
        $pos = strpos($value, '/');
        if ($pos === false) return (float) $value;
        $a = (float) substr($value, 0, $pos);
        $b = (float) substr($value, $pos+1);
        return ($b == 0) ? ($a) : ($a / $b);
    }
    
    private function exif_get_shutter(&$exif) {
        if (!isset($exif['ExposureTime'])) return false;
        $shutter = $this->exif_get_float($exif['ExposureTime']);
        if ($shutter == 0) return false;
        if ($shutter >= 1) return round($shutter) . 's';
        return '1/' . round(1 / $shutter) . 's';
    }
    
    private function exif_get_fstop(&$exif) {
        if (!isset($exif['FNumber'])) return false;
        $fstop  = $this->exif_get_float($exif['FNumber']);
        if ($fstop == 0) return false;
        return 'f/' . round($fstop,1);
    }
    
    private function exif_get_length(&$exif) {
        if (!isset($exif['FocalLength'])) return false;
        $fstop  = $this->exif_get_float($exif['FocalLength']);
        if ($fstop == 0) return false;
        return round($fstop,1).'mm';
    } 



}


