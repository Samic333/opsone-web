# Internal Enterprise Installation Workflow

## Overview

OpsOne uses enterprise web-based distribution for the iPad app. This is NOT App Store or TestFlight distribution.

## Server-Side Infrastructure

### Components
1. **Install Page** (`/install`) — Protected page requiring authentication
2. **Manifest Endpoint** (`/install/manifest`) — Generates `manifest.plist` for OTA install
3. **Build Download** (`/install/download/{id}`) — Serves the .ipa file
4. **Install Logs** — Tracks all access to install resources
5. **Build Management** — `app_builds` table tracks versions

### Access Control
- User must be authenticated (session-based login)
- User status must be `active`
- User must have `mobile_access` enabled
- All access is logged with user ID, tenant ID, IP, and user agent

## Build Upload Process (Manual)

1. Build the app in Xcode with enterprise provisioning profile
2. Generate the `.ipa` file
3. Upload the `.ipa` to `storage/builds/` on the server
4. Insert a record in the `app_builds` table:
   ```sql
   INSERT INTO app_builds (version, build_number, platform, release_notes, file_path, file_size, min_os_version, is_active)
   VALUES ('1.0.0', '1', 'ios', 'Initial release', 'OpsOne-v1.0.0.ipa', 52428800, '16.0', 1);
   ```

## OTA Installation Flow

1. User logs into web portal
2. User navigates to `/install`
3. Install page shows latest build info and install button
4. User taps install button on iPad (Safari required)
5. Safari requests `itms-services://?action=download-manifest&url=<manifest_url>`
6. Server generates `manifest.plist` pointing to the .ipa download URL
7. iPad downloads and installs the app
8. User trusts the enterprise developer certificate in Settings → General → VPN & Device Management
9. User opens the app and logs in

## Apple Enterprise Prerequisites

To sign builds for enterprise distribution, you need:
- Apple Enterprise Developer Program membership ($299/year)
- Enterprise distribution provisioning profile
- Enterprise distribution certificate

These credentials are NOT included in this codebase. They must be obtained from Apple and configured in Xcode.

## Manifest.plist Structure

The manifest is generated dynamically by `InstallController::manifest()`:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "...">
<plist version="1.0">
<dict>
    <key>items</key>
    <array>
        <dict>
            <key>assets</key>
            <array>
                <dict>
                    <key>kind</key><string>software-package</string>
                    <key>url</key><string>https://opsone.aero/install/download/1</string>
                </dict>
            </array>
            <key>metadata</key>
            <dict>
                <key>bundle-identifier</key><string>com.opsone.crewassist</string>
                <key>bundle-version</key><string>1.0.0</string>
                <key>kind</key><string>software</string>
                <key>title</key><string>OpsOne</string>
            </dict>
        </dict>
    </array>
</dict>
</plist>
```
