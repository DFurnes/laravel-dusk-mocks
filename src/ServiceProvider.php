<?php

namespace DFurnes\LaravelDuskMocks;

use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Mockery;
use SuperClosure\Serializer;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Route::get('/_dusk/mock', [
            'middleware' => 'web',
            'uses' => 'DFurnes\LaravelDuskMocks\Controllers\MockController@mock',
        ]);

        $this->applyQueuedMocks();

        Browser::macro('mock', function ($binding, \Closure $mock) {
            /** @var Browser $this */

            $serializer = new Serializer();
            $serialized = $serializer->serialize($mock);

            // Encode the mock class & closure so we can set it on the Dusk server.
            $query = http_build_query(['binding' => $binding, 'closure' => $serialized]);

            return $this->visit('/_dusk/mock/?'.$query);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     * @throws \Exception
     */
    public function register()
    {
        if (app()->environment('production')) {
            throw new \Exception('It is unsafe to run Dusk in production.');
        }
	}

    /**
     * ...
     *
     * @return mixed
     */
    public function applyQueuedMocks()
    {
        $cookies = request()->cookies->all();
        $mocks = collect($cookies)->filter(function ($contents, $key) {
            return Str::startsWith($key, '_dusk_mock:');
        })->mapWithKeys(function($contents, $key) {
            return [str_replace('_dusk:mock:', '', $key) => $contents];
        });

        if (empty($mocks)) {
            return;
        }

        // Bind each of the mocks to the service container:
        foreach ($mocks as $binding => $serializedClosure) {
            $serializer = new Serializer();
            $configure = $serializer->unserialize($serializedClosure);

            $mock = Mockery::mock($binding);
            $configure($mock);

            app()->instance($binding, $mock);
        }
	}
}

