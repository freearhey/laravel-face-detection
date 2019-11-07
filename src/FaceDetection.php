<?php 

namespace Arhey\FaceDetection;

use Intervention\Image\Image;
use Intervention\Image\ImageManager;

class FaceDetection {

    public $bounds;
    public $found = false;

    private $driver;
    private $image;
    private $padding_width = 0;
    private $padding_height = 0;

    protected $detection_data;

    public function __construct() {

        $this->driver = $this->defaultDriver();

        if(function_exists('config')) {
            $this->padding_width = config('facedetection.padding_width');
            $this->padding_height = config('facedetection.padding_height');    
        }

        $detection_file = 'src/Data/face.dat';
        if(function_exists('base_path')) {
            $detection_file = base_path().'/vendor/freearhey/laravel-face-detection/src/Data/face.dat';
        }

        if (is_file($detection_file)) {
            $this->detection_data = unserialize(file_get_contents($detection_file));
        } else {
            throw new \Exception("Couldn't load detection data");
        }
    }

    public function extract($file) {

        $this->image = $this->driver->make($file);

        $im_width = $this->image->width();
        $im_height = $this->image->height();

        //Resample before detection?
        $ratio = 0;
        $diff_width = 320 - $im_width;
        $diff_height = 240 - $im_height;
        if ($diff_width > $diff_height) {
            $ratio = $im_width / 320;
        } else {
            $ratio = $im_height / 240;
        }

        if ($ratio != 0) {
            $reduced_image = $this->driver->make($file);
            $reduced_image->fit(intval($im_width / $ratio), intval($im_height / $ratio), null, 'top-left');
            $this->image->save('tests/Data/tmp/original.jpg');
            $reduced_image->save('tests/Data/tmp/reduced_image.jpg');

            $stats = $this->get_img_stats($reduced_image);
            $this->bounds = $this->do_detect_greedy_big_to_small($stats['ii'], $stats['ii2'], $stats['width'], $stats['height']);
            $this->bounds['h'] = $this->bounds['w'];
            if ($this->bounds['w'] > 0) {
                $this->bounds['x'] *= $ratio;
                $this->bounds['y'] *= $ratio;
                $this->bounds['w'] *= $ratio;
                $this->bounds['h'] *= $ratio;
            }
            var_dump($this->bounds);
        } else {
            $stats = $this->get_img_stats($this->image);
            $this->bounds = $this->do_detect_greedy_big_to_small($stats['ii'], $stats['ii2'], $stats['width'], $stats['height']);
        }
        if($this->bounds['w']>0){
            $this->found = true;
        }
        return $this;
    }

    public function save($file_name)
    {
        if(file_exists($file_name)){
            throw new \Exception("Save File Already Exists ($file_name)");
        }
        
        $to_crop = [
            'x' => $this->bounds['x']-($this->padding_width/2),
            'y' => $this->bounds['y']-($this->padding_height/2),
            'width' => $this->bounds['w']+$this->padding_width,
            'height' => $this->bounds['w']+$this->padding_height,
        ];

        var_dump($to_crop);

        $cropped_image = $this->driver->make($this->image);
        $cropped_image->crop(intval($to_crop['width']), intval($to_crop['height']), 0, 0);
        $cropped_image->save($file_name, 100, 'jpg');
    }

    protected function defaultDriver()
    {
        $driver = 'gd';

        if(function_exists('config')) {
            $driver = config('facedetection.driver');
        }

        return new ImageManager(['driver' => $driver]);
    }

    protected function get_img_stats($image){
        $image_width = $this->image->width();
        $image_height = $this->image->height();
        $iis =  $this->compute_ii($this->image, $image_width, $image_height);
        return array(
            'width' => $image_width,
            'height' => $image_height,
            'ii' => $iis['ii'],
            'ii2' => $iis['ii2']
        );
    }

    protected function compute_ii($image, $image_width, $image_height ){
        $ii_w = $image_width+1;
        $ii_h = $image_height+1;
        $ii = array();
        $ii2 = array();

        for($i=0; $i<$ii_w; $i++ ){
            $ii[$i] = 0;
            $ii2[$i] = 0;
        }

        for($i=1; $i<$ii_h-1; $i++ ){
            $ii[$i*$ii_w] = 0;
            $ii2[$i*$ii_w] = 0;
            $rowsum = 0;
            $rowsum2 = 0;
            for($j=1; $j<$ii_w-1; $j++ ){
                $rgb = $this->image->pickColor($j, $i, 'int');
                $red = ($rgb >> 16) & 0xFF;
                $green = ($rgb >> 8) & 0xFF;
                $blue = $rgb & 0xFF;
                $grey = ( 0.2989*$red + 0.587*$green + 0.114*$blue )>>0;  // this is what matlab uses
                $rowsum += $grey;
                $rowsum2 += $grey*$grey;

                $ii_above = ($i-1)*$ii_w + $j;
                $ii_this = $i*$ii_w + $j;

                $ii[$ii_this] = $ii[$ii_above] + $rowsum;
                $ii2[$ii_this] = $ii2[$ii_above] + $rowsum2;
            }
        }
        return array('ii'=>$ii, 'ii2' => $ii2);
    }

    protected function do_detect_greedy_big_to_small( $ii, $ii2, $width, $height ){
        $s_w = $width/20.0;
        $s_h = $height/20.0;
        $start_scale = $s_h < $s_w ? $s_h : $s_w;
        $scale_update = 1 / 1.2;
        for($scale = $start_scale; $scale > 1; $scale *= $scale_update ){
            $w = (20*$scale) >> 0;
            $endx = $width - $w - 1;
            $endy = $height - $w - 1;
            $step = max( $scale, 2 ) >> 0;
            $inv_area = 1 / ($w*$w);
            for($y = 0; $y < $endy ; $y += $step ){
                for($x = 0; $x < $endx ; $x += $step ){
                    $passed = $this->detect_on_sub_image( $x, $y, $scale, $ii, $ii2, $w, $width+1, $inv_area);
                    if( $passed ) {
                        return array('x'=>$x, 'y'=>$y, 'w'=>$w);
                    }
                } // end x
            } // end y
        }  // end scale
        return null;
    }

    protected function detect_on_sub_image( $x, $y, $scale, $ii, $ii2, $w, $iiw, $inv_area){
        $mean = ( $ii[($y+$w)*$iiw + $x + $w] + $ii[$y*$iiw+$x] - $ii[($y+$w)*$iiw+$x] - $ii[$y*$iiw+$x+$w]  )*$inv_area;
        $vnorm =  ( $ii2[($y+$w)*$iiw + $x + $w] + $ii2[$y*$iiw+$x] - $ii2[($y+$w)*$iiw+$x] - $ii2[$y*$iiw+$x+$w]  )*$inv_area - ($mean*$mean);
        $vnorm = $vnorm > 1 ? sqrt($vnorm) : 1;

        $passed = true;
        for($i_stage = 0; $i_stage < count($this->detection_data); $i_stage++ ){
            $stage = $this->detection_data[$i_stage];
            $trees = $stage[0];

            $stage_thresh = $stage[1];
            $stage_sum = 0;

            for($i_tree = 0; $i_tree < count($trees); $i_tree++ ){
                $tree = $trees[$i_tree];
                $current_node = $tree[0];
                $tree_sum = 0;
                while( $current_node != null ){
                    $vals = $current_node[0];
                    $node_thresh = $vals[0];
                    $leftval = $vals[1];
                    $rightval = $vals[2];
                    $leftidx = $vals[3];
                    $rightidx = $vals[4];
                    $rects = $current_node[1];

                    $rect_sum = 0;
                    for( $i_rect = 0; $i_rect < count($rects); $i_rect++ ){
                        $s = $scale;
                        $rect = $rects[$i_rect];
                        $rx = ($rect[0]*$s+$x)>>0;
                        $ry = ($rect[1]*$s+$y)>>0;
                        $rw = ($rect[2]*$s)>>0;
                        $rh = ($rect[3]*$s)>>0;
                        $wt = $rect[4];

                        $r_sum = ( $ii[($ry+$rh)*$iiw + $rx + $rw] + $ii[$ry*$iiw+$rx] - $ii[($ry+$rh)*$iiw+$rx] - $ii[$ry*$iiw+$rx+$rw] )*$wt;
                        $rect_sum += $r_sum;
                    }

                    $rect_sum *= $inv_area;

                    $current_node = null;
                    if( $rect_sum >= $node_thresh*$vnorm ){
                        if( $rightidx == -1 )
                            $tree_sum = $rightval;
                        else
                            $current_node = $tree[$rightidx];
                    } else {
                        if( $leftidx == -1 )
                            $tree_sum = $leftval;
                        else
                            $current_node = $tree[$leftidx];
                    }
                }
                $stage_sum += $tree_sum;
            }
            if( $stage_sum < $stage_thresh ){
                return false;
            }
        }
        return true;
    }
}