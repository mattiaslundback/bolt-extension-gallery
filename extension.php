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
            'version' => "0.1",
            'required_bolt_version' => "1.4",
            'highest_bolt_version' => "1.4",
            'type' => "General",
            'first_releasedate' => "2014-08-09",
            'latest_releasedate' => "2014-08-09",
            'dependencies' => "",
            'priority' => 10
        );

        return $data;

    }

    /**
     * Initialize Gallery. Called during bootstrap phase.
     */
    public function initialize()
    {
        // If your extension has a 'config.yml', it is automatically loaded.
        if (empty($this->config['gallery_path'])) { $this->config['gallery_path'] = "gallerys/"; }

        // Initialize the Twig function
        $this->addTwigFunction('image_scr', 'twigImage_scr');
        $this->addTwigFunction('GalleryList', 'twigGalleryList');

    }


    /**
     * Twig function {{ image_scr('foo') }} in Gallery extension.
     */
    public function twigImage_scr($param="")
    {
        $html = $this->config['gallery_path'];

        return new \Twig_Markup($html, 'UTF-8');

    }
    
    public function twigGalleryList($folder="")
    {
        $path = $this->app['paths']['filespath'].$this->config['gallery_path'].$folder;
        $online_path = $this->app['paths']['files'].$this->config['gallery_path'].$folder;
        $images = glob($path . "*.{jpg,JPG,jpeg,JPEG}", GLOB_BRACE);
        $image_array =array();
        foreach( $images as $image) {
            $path_parts = pathinfo($image);
            
   	        $exif_ifd0 = exif_read_data ( $image ,'IFD0' ,0 );
   	        $exif = exif_read_data ( $image ,'EXIF' ,0 );
       	    
            $data['image']  = $online_path.pathinfo($image)['basename'];
            $data['date']  = date ("YmdHis", filemtime($image));
            $data['Model'] = $exif_ifd0['Model'];
            $data['focalLenth'] = $this->exif_get_length($exif);
            $data['shutterSpeed'] = $this->exif_get_shutter($exif);
            $data['fStop'] = $this->exif_get_fstop($exif);
            $data['ISO'] = $exif['ISOSpeedRatings'];
            $data['time'] = str_replace(':', '', (str_replace(' ', '', $exif_ifd0['DateTime'])));
            array_push($image_array,$data);
        }
        
        usort($image_array, function($a, $b) {
            return $a['time'] - $b['time'];
        });
        
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


