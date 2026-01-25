# Firebase Configuration Instructions

To properly configure Firebase Cloud Messaging (FCM) for your Laravel application, you need to set up a service account key.

## Step 1: Create a Service Account in Firebase Console

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select your project ("punya-kios")
3. Navigate to Project Settings (gear icon)
4. Go to the "Service Accounts" tab
5. Click "Generate New Private Key" 
6. Save the downloaded JSON file

## Step 2: Place the Service Account File

Place the downloaded service account JSON file in the `storage/app/firebase/` directory.

For example, if your file is named `firebase-adminsdk.json`:

```
storage/
└── app/
    └── firebase/
        └── firebase-adminsdk.json
```

## Step 3: Update Environment Variables

Update your `.env` file with the path to your service account file:

```env
# Firebase Configuration
FIREBASE_CREDENTIALS_FILE=app/firebase/firebase-adminsdk.json
```

Alternatively, you can use individual environment variables (not recommended for production):

```env
FIREBASE_PROJECT_ID=punya-kios
FIREBASE_CLIENT_EMAIL=your-service-account-email@your-project.iam.gserviceaccount.com
FIREBASE_PRIVATE_KEY_ID=your-private-key-id
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\nyour-private-key-content\n-----END PRIVATE KEY-----\n"
```

## Step 4: Grant Required Permissions

Make sure your service account has the following roles in Firebase:
- Firebase Admin SDK Administrator Service Agent
- Firebase Authentication Admin
- Cloud Messaging API Permitted Client

## Important Notes

- Keep your service account key secure and never commit it to version control
- The service account file should be placed in a directory that is not publicly accessible
- For production environments, always use the service account file approach rather than individual environment variables

## Testing the Configuration

After configuring, you can test the setup by sending a test notification through the API endpoint:

```
POST /api/fcm/send-test
Authorization: Bearer {user_token}
Content-Type: application/json
```

The service account key contains the necessary credentials to authenticate with Firebase services and send push notifications to your users.