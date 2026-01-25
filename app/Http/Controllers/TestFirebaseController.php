<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\FirebaseException;

class TestFirebaseController extends Controller
{
    public function testConnection()
    {
        try {
            // Get the Firebase factory instance
            $firebase = app('firebase');

            // Test connection by getting the messaging instance
            $messaging = $firebase->createMessaging();

            return response()->json([
                'success' => true,
                'message' => 'Firebase connection successful!',
                'project_id' => $firebase->getProjectId()
            ]);
        } catch (FirebaseException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Firebase connection failed',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'General error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
