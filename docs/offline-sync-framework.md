# Offline Sync Framework

## 1. Why Offline-First Matters in Aviation

CrewAssist is used in environments where network connectivity is unreliable or unavailable:

- Aircraft ramp / apron — Wi-Fi drops as crew board or deplane
- Airport lounges with captive portals or congested Wi-Fi
- Remote airports with limited 4G coverage
- In-flight connectivity (where permitted for crew devices)
- Base operations with intermittent signal

Crew must be able to:
- View their roster when offline
- Read their assigned documents and manuals
- Draft and submit safety reports (submit when back online)
- Acknowledge notices (recorded locally, synced when connected)
- View pending duty times and flight assignments

An offline-first design means: **the app works without a network. The network makes it fresher.**

---

## 2. Current Sync Architecture (Implemented — Phase 0)

### SyncStore — UserDefaults cache layer

`SyncStore` is the primary local cache in CrewAssist. It persists data across app launches using `UserDefaults`.

| Key | Type | Contents |
|---|---|---|
| `cached_notices_v1` | `[Data]` (JSON-encoded `[NoticeItem]`) | All notices visible to the user |
| `cached_documents_v1` | `[Data]` (JSON-encoded `[DocumentItem]`) | Assigned documents |
| `notice_ack_state_v1` | `[String: String]` | `notice_id: acknowledged_at` ack timestamps |
| `device_uuid_stable_v1` | `String` | Stable device UUID (fallback when `identifierForVendor` is nil) |
| `last_sync_notices` | `Double` (epoch) | Timestamp of last successful notice fetch |
| `last_sync_documents` | `Double` (epoch) | Timestamp of last successful document fetch |

### Fetch-and-cache pattern (current services)

All `Real*Service` implementations follow this pattern:

```swift
func fetchNotices() async throws -> [NoticeItem] {
    let fresh = try await api.get("/notices/my")  // throws on network failure
    syncStore.saveNotices(fresh)                   // persist for offline use
    return fresh
}
```

If the network call fails, the calling view falls back to `syncStore.loadNotices()`.

### Notice acknowledgement persistence

Implemented in `RealNoticeService`:

```swift
func acknowledgeNotice(id: Int) async throws {
    // 1. Persist locally FIRST — ack is visible immediately even if server fails
    syncStore.setAckState(noticeId: id, ackedAt: Date())
    // 2. Fire server call in background
    try await api.post("/notices/\(id)/ack", body: [:])
}
```

This ensures crew never lose an acknowledgement due to a momentary connectivity drop.

---

## 3. What Requires Live Connection vs. What Works Offline

| Action | Requires Network | Works Offline |
|---|---|---|
| Login / token refresh | ✅ Required | ❌ |
| View roster (cached) | ❌ | ✅ (shows last sync) |
| View notices (cached) | ❌ | ✅ |
| Acknowledge notice | ❌ (local-first, queued sync) | ✅ |
| Read downloaded documents | ❌ | ✅ |
| Draft safety report | ❌ | ✅ (queue for upload) |
| Submit safety report | ✅ Required (or queued) | 🟡 (queued until connected) |
| Download new documents | ✅ Required | ❌ |
| Roster change request | ✅ Required | ❌ |
| FDM data sync | ✅ Required | ❌ |

---

## 4. Offline Write Queue — Planned Architecture (Phase 6+)

For actions that mutate server state, the app will maintain a durable write queue. Items survive app kills and restarts.

### Queue item structure

```swift
struct OfflineQueueItem: Codable {
    let id: UUID              // local identifier
    let endpoint: String      // e.g. "/safety-reports"
    let method: String        // "POST" | "PATCH" | "PUT"
    let body: Data            // JSON-encoded request body
    var retryCount: Int       // incremented on each failed attempt
    let createdAt: Date       // for ordering and stale-item detection
    var lastAttemptAt: Date?
    var lastError: String?
}
```

### Storage

Queue items are persisted in `UserDefaults` under key `offline_write_queue_v1` as a JSON array. When Core Data is available (future), a `OfflineQueueEntry` entity provides better querying and indexed lookups.

### Retry strategy

| Attempt | Delay |
|---|---|
| 1 | Immediate (on next network reconnect) |
| 2 | 30 seconds |
| 3 | 2 minutes |
| 4 | 10 minutes |
| 5 | 1 hour |
| 6+ | Permanent failure — flag to user |

After 6 failed attempts, the item is moved to a `failed_queue` and the user is shown an alert: *"A report could not be submitted. Please review and retry manually."*

### Conflict resolution policy

| Data type | Strategy |
|---|---|
| Safety reports (crew-authored) | **Client wins** — crew reports are created, never overwritten by server |
| Roster entries | **Server wins** — scheduler owns roster truth |
| Notice acks | **Idempotent** — duplicate acks are ignored by server (UPSERT) |
| Document metadata | **Server wins** — document controller owns document state |
| Logbook entries (future) | **Client wins with review** — entries require crew sign-off |

---

## 5. Sync Trigger Events

The sync engine decides when to fetch fresh data based on:

| Trigger | Action |
|---|---|
| App enters foreground | Fetch notices + roster (if token valid) |
| Network reachability restores | Flush offline write queue + fetch notices |
| Periodic (every 15 min) | Fetch notices only (when `enhanced_sync` module flag is on) |
| Explicit pull-to-refresh | Full fetch: notices + documents + roster |
| Push notification received | Fetch the specific entity referenced in the push payload |

### Module flag gate

The 15-minute background sync is gated on the `enhanced_sync` feature flag in the tenant's module configuration. This prevents background battery drain for airlines that do not require frequent updates.

---

## 6. Module Sync Flow (AppEnvironment)

When the user logs in or the app foregrounds:

```
AppEnvironment.onUserChange
  └─ filter: enabledModuleSlugs is not empty    ← Bug fixed in Phase 0 (was inverted)
       └─ fetchAndApplyModules()                 ← hydrates visibleModules
            └─ syncContent()                     ← triggers service fetches
```

`visibleModules` is computed as the intersection of:
1. `AppModule.serverSlugMap` — all slugs the app knows about
2. `RoleConfig[currentRole].allowedModules` — modules allowed for this role
3. `currentUser.enabledModuleSlugs` — modules the tenant has activated

A module missing from any of these three sets is invisible to the user.

---

## 7. Device UUID Stability (Phase 0 Fix)

Device registration requires a stable UUID across app launches. `UIDevice.identifierForVendor` can return `nil` before the device's first unlock after reboot.

### Resolution order (implemented in `RealDeviceSyncService`):

```
1. UIDevice.current.identifierForVendor?.uuidString   → use if available (OS-managed, stable)
2. UserDefaults["device_uuid_stable_v1"]              → use if persisted from prior launch
3. Generate UUID().uuidString, save to UserDefaults    → first launch or recovery
```

This guarantees a device is never registered twice due to a nil vendor ID.

---

## 8. Sync Status Visibility to User

Planned UI components (Phase 6+):

| Component | Location | Shows |
|---|---|---|
| `SyncStatusBanner` | Top of dashboard | "Last synced 3 min ago" / "Offline — showing cached data" |
| `PendingQueueBadge` | Sidebar | Count of items waiting to upload |
| `SyncCenterView` | Separate screen | Full queue table: item type, status, last error, manual retry button |
| `OfflineModeIndicator` | Navigation bar | WiFi-slash icon when reachability is absent |

The sync status is driven by a `SyncStatusStore` observable that publishes:
- `isOnline: Bool`
- `lastSyncDate: Date?`
- `pendingQueueCount: Int`
- `failedQueueCount: Int`

---

## 9. Attachments and File Sync

Documents and attachments follow a separate sync path from structured data:

| Phase | Behavior |
|---|---|
| Download | On-demand when user taps a document. Cached to `FileManager` sandbox under `Documents/tenant_{id}/`. |
| Version check | On foreground: compare cached `document.version` with server. Download if version changed. |
| Upload | Attachments (safety report photos, forms) staged in `tmp/` until the report is submitted. |
| Offline | Cached files are readable; new uploads are queued in the write queue. |

File cache is managed by `RealDocumentService`. Files older than 30 days without access are eligible for local eviction (eviction policy is local-only, not server-side).

---

## 10. Implementing Offline Support in a New Module

When adding a new module with offline capability:

1. **Define cacheable types** — add `Codable` conformance with `init(from decoder:)` providing safe defaults for new fields
2. **Add a `SyncStore` key** — prefix with module name (e.g., `cached_safety_drafts_v1`)
3. **Implement the fetch-and-cache pattern** in the `Real*Service`
4. **Declare offline behavior** in `module-governance-matrix.md` for the module row
5. **For write operations**: add an `OfflineWriteQueue.enqueue()` call with the endpoint and body before the network call, dequeue on success
6. **Define conflict resolution** in this document under Section 4

Modules that do not support offline must show `OfflineModeIndicator` and disable their primary action buttons when `SyncStatusStore.isOnline == false`.
