<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Configuration;
use GuzzleHttp\Client as GuzzleClient;

class FirebaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register Guzzle HTTP client
        $this->app->singleton(\GuzzleHttp\ClientInterface::class, function ($app) {
            return new GuzzleClient();
        });

        // Bind Firebase factory to the container
        $this->app->singleton('firebase.factory', function ($app) {
            // Check if credentials file is provided
            $credentialsFile = env('FIREBASE_CREDENTIALS_FILE');
            if (!empty($credentialsFile) && file_exists(storage_path($credentialsFile))) {
                $factory = (new Factory)->withServiceAccount(storage_path($credentialsFile));
            } else {
                // Use individual environment variables
                $projectId = env('FIREBASE_PROJECT_ID');
                $clientEmail = env('FIREBASE_CLIENT_EMAIL');
                $privateKey = env('FIREBASE_PRIVATE_KEY');

                if (!$projectId || !$clientEmail || !$privateKey) {
                    throw new \Exception('Firebase configuration is incomplete. Please set FIREBASE_PROJECT_ID, FIREBASE_CLIENT_EMAIL, and FIREBASE_PRIVATE_KEY in your .env file.');
                }

                $config = [
                    'type' => 'service_account',
                    'project_id' => $projectId,
                    'client_email' => $clientEmail,
                    'private_key' => str_replace('\n', "\n", $privateKey),
                ];

                $factory = (new Factory)->withServiceAccount($config);
            }

            return $factory;
        });

        // Bind Firebase instance directly
        $this->app->singleton('firebase', function ($app) {
            return $app['firebase.factory'];
        });

        // Bind messaging service
        $this->app->singleton('firebase.messaging', function ($app) {
            $factory = $app['firebase.factory'];
            return $factory->createMessaging();
        });

        // Bind Firebase service
        $this->app->singleton('firebase.service', function ($app) {
            return new \App\Services\FirebaseService($app['firebase.messaging']);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
