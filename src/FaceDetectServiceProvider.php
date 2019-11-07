<?php 

namespace Arhey\FaceDetection;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class FaceDetectionServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        $this->app->bind('FaceDetection', '\Arhey\FaceDetection\FaceDetection');
        
	}


    public function boot(){
		$this->publishes([
				__DIR__.'/config/config.php' => base_path('config/facedetection.php'),
		]);
    }

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [

        ];
	}

}
