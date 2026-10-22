package reggit.module

import skip.lib.*
import skip.model.*
import skip.foundation.*
import skip.ui.*

import android.Manifest
import android.app.Application
import android.app.NotificationManager
import android.content.Context
import android.content.pm.PackageManager
import android.graphics.Color as AndroidColor
import android.os.Build
import android.util.Log
import androidx.annotation.Keep
import com.google.firebase.messaging.FirebaseMessaging
import com.google.firebase.messaging.FirebaseMessagingService
import com.google.firebase.messaging.RemoteMessage
import org.json.JSONObject
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.activity.SystemBarStyle
import androidx.activity.ComponentActivity
import androidx.appcompat.app.AppCompatActivity
import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.BoxWithConstraints
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.size
import androidx.compose.ui.graphics.Color
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.SideEffect
import androidx.compose.runtime.saveable.rememberSaveableStateHolder
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.luminance
import androidx.compose.ui.unit.dp
import androidx.compose.ui.platform.LocalContext
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.safeDrawing
import androidx.compose.foundation.layout.windowInsetsPadding
import androidx.compose.foundation.layout.imePadding
import androidx.core.app.ActivityCompat

internal val logger: SkipLogger = SkipLogger(subsystem = "reggit.app", category = "Reggit")

private typealias AppRootView = ReggitRootView
private typealias AppDelegate = ReggitAppDelegate

/// Key for FCM registration token. Must match `NativeAppStorage.pushTokenKey` in the Skip module.
private const val PUSH_TOKEN_PREFS_KEY = "reggit_push_token"
/// SharedPreferences name used so Skip's device provider can read the token (Skip UserDefaults on Android).
private const val PUSH_TOKEN_PREFS_NAME = "UserDefaults"

/// AndroidAppMain is the `android.app.Application` entry point, and must match `application android:name` in the AndroidMainfest.xml file.
open class AndroidAppMain: Application {
    constructor() {
    }

    override fun onCreate() {
        super.onCreate()
        logger.info("starting app")
        ProcessInfo.launch(applicationContext)
        AppDelegate.shared.onConfigureEnvironment(BuildConfig.DEBUG)
        ensureNotificationChannel(applicationContext)
        initFirebaseMessaging()
        AppDelegate.shared.onInit()
    }

    private fun initFirebaseMessaging() {
        FirebaseMessaging.getInstance().token
            .addOnSuccessListener { token ->
                if (!token.isNullOrBlank()) {
                    saveFcmToken(token)
                }
            }
            .addOnFailureListener { error ->
                Log.w("ReggitFcm", "token fetch failed: ${error.message}")
            }
        android.os.Handler(android.os.Looper.getMainLooper()).postDelayed({ refreshFcmToken() }, 2000)
        android.os.Handler(android.os.Looper.getMainLooper()).postDelayed({ refreshFcmToken() }, 5000)
    }

    private fun refreshFcmToken() {
        FirebaseMessaging.getInstance().token
            .addOnSuccessListener { token ->
                if (!token.isNullOrBlank()) {
                    saveFcmToken(token)
                }
            }
    }

    private fun saveFcmToken(token: String) {
        val prefs = applicationContext.getSharedPreferences(PUSH_TOKEN_PREFS_NAME, Context.MODE_PRIVATE)
        prefs.edit().putString(PUSH_TOKEN_PREFS_KEY, token).apply()
        // Fuse uses native Foundation defaults, separate from these Java preferences.
        // Replay cached tokens on every launch/resume so bootstrap can register them.
        android.os.Handler(android.os.Looper.getMainLooper()).post {
            AppDelegate.shared.onPushTokenReceived(token)
        }
    }

    fun saveFcmTokenPublic() {
        refreshFcmToken()
    }

    companion object {
    }
}

/**
 * FCM data メッセージでお知らせ取消しを受け取り、届済みを消す。
 */
@Keep
class ReggitFirebaseMessagingService : FirebaseMessagingService() {
    override fun onNewToken(token: String) {
        val prefs = applicationContext.getSharedPreferences(PUSH_TOKEN_PREFS_NAME, Context.MODE_PRIVATE)
        prefs.edit().putString(PUSH_TOKEN_PREFS_KEY, token).apply()
        android.os.Handler(android.os.Looper.getMainLooper()).post {
            AppDelegate.shared.onPushTokenReceived(token)
        }
    }

    override fun onMessageReceived(message: RemoteMessage) {
        val data = message.data
        val type = data["type"].orEmpty()
        val announcementId = data["announcement_id"].orEmpty()
        val title = message.notification?.title.orEmpty()
        val body = message.notification?.body.orEmpty()
        val isCancel = type == "announcement_cancelled"
            || body == "このお知らせは取り消されました"
            || (title.isEmpty() && body.isEmpty() && announcementId.isNotEmpty())
        if (isCancel) {
            val originalTitle = data["original_title"].orEmpty().ifEmpty { title }
            cancelDeliveredAnnouncementNotifications(applicationContext, announcementId, originalTitle)
            return
        }
        if (type.isNotEmpty()) {
            android.os.Handler(android.os.Looper.getMainLooper()).post {
                AppDelegate.shared.onNotificationForeground(notificationType = type)
            }
        }
        if (title.isNotBlank() || body.isNotBlank()) {
            showFcmNotification(applicationContext, message, title, body)
        }
    }
}

private fun notificationTab(type: String): String? = when (type) {
    "queue_called", "queue_linked" -> "queue"
    "order_confirmed", "order_ready", "order_completed" -> "orders"
    "coupon_issued" -> "coupons"
    "announcement" -> "announcements"
    else -> null
}

private fun ensureNotificationChannel(context: Context) {
    if (Build.VERSION.SDK_INT >= 26) {
        val manager = context.getSystemService(NotificationManager::class.java) ?: return
        manager.createNotificationChannel(android.app.NotificationChannel(
            "reggit_default", "店舗からのお知らせ", NotificationManager.IMPORTANCE_HIGH
        ))
    }
}

private fun showFcmNotification(context: Context, message: RemoteMessage, title: String, body: String) {
    if (Build.VERSION.SDK_INT >= 33 && ActivityCompat.checkSelfPermission(context, Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED) return
    ensureNotificationChannel(context)
    val key = message.collapseKey ?: message.messageId ?: message.data.toString()
    val id = key.hashCode()
    val intent = android.content.Intent(context, MainActivity::class.java).apply {
        flags = android.content.Intent.FLAG_ACTIVITY_CLEAR_TOP or android.content.Intent.FLAG_ACTIVITY_SINGLE_TOP
        message.data.forEach { (key, value) -> putExtra(key, value) }
    }
    val pendingIntent = android.app.PendingIntent.getActivity(context, id, intent,
        android.app.PendingIntent.FLAG_UPDATE_CURRENT or android.app.PendingIntent.FLAG_IMMUTABLE)
    val notification = android.app.Notification.Builder(context, "reggit_default")
        .setSmallIcon(android.R.drawable.ic_dialog_info)
        .setContentTitle(title.ifBlank { "店舗からのお知らせ" })
        .setContentText(body)
        .setStyle(android.app.Notification.BigTextStyle().bigText(body))
        .setContentIntent(pendingIntent)
        .setAutoCancel(true)
        .build()
    context.getSystemService(NotificationManager::class.java)?.notify(id, notification)
}

internal fun announcementCollapseId(announcementId: String): String? {
    if (announcementId.isEmpty()) {
        return null
    }
    return "reggit:announcement:$announcementId"
}

internal fun cancelDeliveredAnnouncementNotifications(
    context: Context,
    announcementId: String,
    originalTitle: String = ""
) {
    val collapseId = announcementCollapseId(announcementId)
    if (Build.VERSION.SDK_INT < Build.VERSION_CODES.M) {
        return
    }
    val notificationManager = context.getSystemService(NotificationManager::class.java) ?: return

    if (collapseId != null) {
        notificationManager.cancel(collapseId.hashCode())
        notificationManager.cancel(collapseId, collapseId.hashCode())
        notificationManager.cancel(null, collapseId.hashCode())
    }

    val matched = notificationManager.activeNotifications.filter { statusBarNotification ->
        val group = statusBarNotification.notification.group
        val groupKey = statusBarNotification.groupKey.orEmpty()
        if (collapseId != null) {
            if (statusBarNotification.tag == collapseId) {
                return@filter true
            }
            if (statusBarNotification.id == collapseId.hashCode()) {
                return@filter true
            }
            if (group == collapseId) {
                return@filter true
            }
            if (groupKey.contains(collapseId)) {
                return@filter true
            }
        }
        if (announcementId.isNotEmpty() && (
                groupKey.contains("announcement:$announcementId")
                    || (group?.contains("announcement:$announcementId") == true)
                )
        ) {
            return@filter true
        }
        val extras = statusBarNotification.notification.extras
        notificationContainsAnnouncementId(extras, announcementId)
            || notificationContainsAnnouncementCancelled(extras)
            || (originalTitle.isNotEmpty() && extras?.getCharSequence(android.app.Notification.EXTRA_TITLE)?.toString() == originalTitle)
            || extrasFlatContains(extras, announcementId)
            || (collapseId != null && extrasFlatContains(extras, collapseId))
    }
    matched.forEach { statusBarNotification ->
        notificationManager.cancel(statusBarNotification.tag, statusBarNotification.id)
        notificationManager.cancel(statusBarNotification.id)
    }
}

internal data class CancelledAnnouncementItem(val id: String, val title: String = "")

internal fun fetchCancelledAnnouncementItems(context: Context): List<CancelledAnnouncementItem> {
    return try {
        val baseUrl = resolveCancelledIdsBaseUrl(context) ?: return emptyList()
        val projectKey = resolveCancelledIdsProjectKey(context)
        val encodedKey = java.net.URLEncoder.encode(projectKey, "UTF-8")
        val url = java.net.URL("$baseUrl/api/v1/announcements/cancelled-ids?project_key=$encodedKey")
        val connection = (url.openConnection() as java.net.HttpURLConnection).apply {
            requestMethod = "GET"
            setRequestProperty("Accept", "application/json")
            setRequestProperty("ngrok-skip-browser-warning", "1")
            connectTimeout = 8000
            readTimeout = 8000
        }
        try {
            if (connection.responseCode != 200) {
                logger.warning("CancelledAnnouncementIds fetch status=${connection.responseCode}")
                return emptyList()
            }
            val body = connection.inputStream.bufferedReader().use { it.readText() }
            parseCancelledAnnouncementItems(body)
        } finally {
            connection.disconnect()
        }
    } catch (e: Exception) {
        logger.warning("CancelledAnnouncementIds fetch failed: ${e.message}")
        emptyList()
    }
}

private fun resolveCancelledIdsBaseUrl(context: Context): String? = AppDelegate.shared.connectionBaseURL()

private fun resolveCancelledIdsProjectKey(context: Context): String = AppDelegate.shared.connectionProjectKey()

private fun parseCancelledAnnouncementItems(body: String): List<CancelledAnnouncementItem> {
    val json = JSONObject(body)
    val items = mutableListOf<CancelledAnnouncementItem>()
    val array = json.optJSONArray("items")
    if (array != null) {
        for (index in 0 until array.length()) {
            val obj = array.optJSONObject(index) ?: continue
            val id = obj.opt("id")?.toString()?.trim().orEmpty()
            if (id.isEmpty() || id == "0") continue
            items.add(CancelledAnnouncementItem(id, obj.optString("title").trim()))
        }
    }
    if (items.isNotEmpty()) {
        return items
    }
    val ids = json.optJSONArray("cancelled_ids") ?: return emptyList()
    return (0 until ids.length()).mapNotNull { index ->
        ids.opt(index)?.toString()?.trim()?.takeIf { it.isNotEmpty() && it != "0" }?.let { CancelledAnnouncementItem(it) }
    }
}

private fun notificationContainsAnnouncementCancelled(extras: android.os.Bundle?): Boolean {
    if (extras == null) {
        return false
    }
    return extrasString(extras, "type") == "announcement_cancelled"
        || extrasAsJson(extras)?.optString("type") == "announcement_cancelled"
}

private fun notificationContainsAnnouncementId(extras: android.os.Bundle?, announcementId: String): Boolean {
    if (extras == null || announcementId.isEmpty()) {
        return false
    }
    if (extrasString(extras, "announcement_id") == announcementId) {
        return true
    }
    val json = extrasAsJson(extras) ?: return false
    return json.optString("announcement_id") == announcementId
}

private fun extrasString(extras: android.os.Bundle, key: String): String? {
    extras.getString(key)?.trim()?.takeIf { it.isNotEmpty() }?.let { return it }
    if (extras.containsKey(key)) {
        val value = extras.get(key)
        when (value) {
            is Number -> return value.toString()
            is CharSequence -> return value.toString().trim().takeIf { it.isNotEmpty() }
        }
    }
    return null
}

private fun extrasFlatContains(extras: android.os.Bundle?, announcementId: String): Boolean {
    if (extras == null || announcementId.isEmpty()) {
        return false
    }
    return extras.toString().contains(announcementId)
}

private fun extrasAsJson(extras: android.os.Bundle): JSONObject? {
    val candidates = listOf("custom", "data", "additionalData")
    for (key in candidates) {
        val raw = extras.getString(key) ?: continue
        try {
            val root = JSONObject(raw)
            val nested = root.optJSONObject("a") ?: root.optJSONObject("additionalData")
            if (nested != null) {
                return nested
            }
            return root
        } catch (_: Exception) {
            continue
        }
    }
    // Bundle 直下のネスト Bundle も見る
    for (key in candidates) {
        val nestedBundle = extras.getBundle(key) ?: continue
        if (nestedBundle.containsKey("announcement_id") || nestedBundle.containsKey("a")) {
            val json = JSONObject()
            for (bundleKey in nestedBundle.keySet()) {
                json.put(bundleKey, nestedBundle.get(bundleKey)?.toString())
            }
            val nested = nestedBundle.getBundle("a")
            if (nested != null) {
                val nestedJson = JSONObject()
                for (bundleKey in nested.keySet()) {
                    nestedJson.put(bundleKey, nested.get(bundleKey)?.toString())
                }
                return nestedJson
            }
            return json
        }
    }
    return null
}

/// AndroidAppMain is initial `androidx.appcompat.app.AppCompatActivity`, and must match `activity android:name` in the AndroidMainfest.xml file.
open class MainActivity: AppCompatActivity {
    constructor() {
    }

    override fun onCreate(savedInstanceState: android.os.Bundle?) {
        super.onCreate(savedInstanceState)
        logger.info("starting activity")
        UIApplication.launch(this)
        enableEdgeToEdge()

        setContent {
            val saveableStateHolder = rememberSaveableStateHolder()
            saveableStateHolder.SaveableStateProvider(true) {
                PresentationRootView(ComposeContext())
                SideEffect { saveableStateHolder.removeState(true) }
            }
        }

        AppDelegate.shared.onLaunch()

        requestNotificationPermissionIfNeeded()
        deliverNotificationIntent(intent)
        deliverLoginLinkIntent(intent)
    }

    override fun onNewIntent(intent: android.content.Intent) {
        super.onNewIntent(intent)
        setIntent(intent)
        deliverNotificationIntent(intent)
        deliverLoginLinkIntent(intent)
    }

    // メールのログインリンク（reggit://login）を Swift 側へ渡す
    private fun deliverLoginLinkIntent(intent: android.content.Intent?) {
        val data = intent?.data ?: return
        if (intent.action != android.content.Intent.ACTION_VIEW || data.host != "login") return
        intent.data = null
        android.os.Handler(android.os.Looper.getMainLooper()).postDelayed({
            AppDelegate.shared.onOpenURL(data.toString())
        }, 500)
    }

    private fun deliverNotificationIntent(intent: android.content.Intent?) {
        val type = intent?.getStringExtra("type").orEmpty()
        val tab = intent?.getStringExtra("tab") ?: notificationTab(type) ?: return
        if (tab.isBlank()) return
        intent?.removeExtra("tab")
        intent?.removeExtra("type")
        android.os.Handler(android.os.Looper.getMainLooper()).postDelayed({
            AppDelegate.shared.onNotificationOpened(tab, notificationType = type)
        }, 500)
    }

    private val NOTIFICATION_PERMISSION_REQUEST_CODE = 9000

    private fun requestNotificationPermissionIfNeeded() {
        if (Build.VERSION.SDK_INT >= 33) {
            if (ActivityCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED) {
                val perms = kotlin.arrayOf(Manifest.permission.POST_NOTIFICATIONS)
                ActivityCompat.requestPermissions(this, perms, NOTIFICATION_PERMISSION_REQUEST_CODE)
            }
        }
    }

    override fun onStart() {
        logger.info("onStart")
        super.onStart()
    }

    override fun onResume() {
        super.onResume()
        AppDelegate.shared.onResume()
        (application as? AndroidAppMain)?.saveFcmTokenPublic()
        // 既にログインしている場合も含め、アプリ表示のたびに通知許可を未取得ならリクエスト
        requestNotificationPermissionIfNeeded()
        // 取消し通知の復元に負けないよう即時＋遅延で掃除
        syncCancelledAnnouncementNotifications()
        android.os.Handler(android.os.Looper.getMainLooper()).postDelayed({
            syncCancelledAnnouncementNotifications()
        }, 1500)
        android.os.Handler(android.os.Looper.getMainLooper()).postDelayed({
            syncCancelledAnnouncementNotifications()
        }, 4000)
    }

    private fun syncCancelledAnnouncementNotifications() {
        Thread {
            val items = fetchCancelledAnnouncementItems(this)
            logger.info("CancelledAnnouncementIds synced count=${items.size}")
            if (items.isEmpty()) {
                return@Thread
            }
            runOnUiThread {
                for (item in items) {
                    cancelDeliveredAnnouncementNotifications(this, item.id, item.title)
                }
            }
        }.start()
    }

    override fun onPause() {
        super.onPause()
        AppDelegate.shared.onPause()
    }

    override fun onStop() {
        super.onStop()
        AppDelegate.shared.onStop()
    }

    override fun onDestroy() {
        super.onDestroy()
        AppDelegate.shared.onDestroy()
    }

    override fun onLowMemory() {
        super.onLowMemory()
        AppDelegate.shared.onLowMemory()
    }

    override fun onRestart() {
        logger.info("onRestart")
        super.onRestart()
    }

    override fun onSaveInstanceState(outState: android.os.Bundle): Unit = super.onSaveInstanceState(outState)

    override fun onRestoreInstanceState(bundle: android.os.Bundle) {
        // Usually you restore your state in onCreate(). It is possible to restore it in onRestoreInstanceState() as well, but not very common. (onRestoreInstanceState() is called after onStart(), whereas onCreate() is called before onStart().
        logger.info("onRestoreInstanceState")
        super.onRestoreInstanceState(bundle)
    }

    override fun onRequestPermissionsResult(requestCode: Int, permissions: kotlin.Array<String>, grantResults: IntArray) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults)
        logger.info("onRequestPermissionsResult: ${requestCode}")
        if (requestCode == NOTIFICATION_PERMISSION_REQUEST_CODE && grantResults.isNotEmpty() && grantResults[0] == PackageManager.PERMISSION_GRANTED) {
            android.os.Handler(android.os.Looper.getMainLooper()).postDelayed({
                (application as? AndroidAppMain)?.saveFcmTokenPublic()
            }, 1000)
        }
    }

    companion object {
    }
}

@Composable
internal fun SyncSystemBarsWithTheme() {
    val dark = MaterialTheme.colorScheme.background.luminance() < 0.5f

    val transparent = AndroidColor.TRANSPARENT
    val style = if (dark) {
        SystemBarStyle.dark(transparent)
    } else {
        SystemBarStyle.light(transparent, transparent)
    }

    val activity = LocalContext.current as? ComponentActivity
    DisposableEffect(style) {
        activity?.enableEdgeToEdge(
            statusBarStyle = style,
            navigationBarStyle = style
        )
        onDispose { }
    }
}

/// Android: Skip の Compose が親の制約を正しく受け取らない場合があるため、
/// BoxWithConstraints で明示的なサイズを渡して画面全体を描画する。
@Composable
internal fun PresentationRootView(context: ComposeContext) {
    // A light scheme alone still uses Android wallpaper-derived dynamic colors.
    // Keep presentation/system-bar surfaces equal to the SwiftUI design tokens.
    val colorScheme = ColorScheme.light
    Material3ColorScheme(scheme = { _, _ ->
        lightColorScheme(
            primary = Color(0xFF173F35),
            onPrimary = Color.White,
            primaryContainer = Color(0xFFF0F2F5),
            onPrimaryContainer = Color.Black,
            secondary = Color(0xFF173F35),
            onSecondary = Color.White,
            secondaryContainer = Color(0xFFF0F2F5),
            onSecondaryContainer = Color.Black,
            background = Color.White,
            onBackground = Color.Black,
            surface = Color.White,
            onSurface = Color.Black,
            surfaceVariant = Color(0xFFF0F2F5),
            onSurfaceVariant = Color(0xFF57616B),
            surfaceContainerLowest = Color.White,
            surfaceContainerLow = Color(0xFFF0F2F5),
            surfaceContainer = Color(0xFFF2F5F7),
            surfaceContainerHigh = Color(0xFFF0F2F5),
            // Material switches use these for the inactive track and thumb.
            surfaceContainerHighest = Color(0xFFC7C7CC),
            outline = Color.White,
            outlineVariant = Color(0xFFD6DEE6),
            surfaceTint = Color.Transparent
        )
    }) {
        // The shared header/footer already delimit navigation content. Apply the
        // system insets once here so nested NavigationStacks cannot add them again.
        PresentationRoot(defaultColorScheme = colorScheme, absoluteSystemBarEdges = Edge.Set.of(), context = context) { ctx ->
            SyncSystemBarsWithTheme()
            val contentContext = ctx.content()
            Box(modifier = ctx.modifier.fillMaxSize().windowInsetsPadding(WindowInsets.safeDrawing).imePadding(), contentAlignment = Alignment.TopStart) {
                BoxWithConstraints(modifier = Modifier.fillMaxSize()) {
                    Box(
                        modifier = Modifier
                            .size(maxWidth, maxHeight.coerceAtLeast(400.dp))
                            .background(Color.White)
                    ) {
                        // Shared SwiftUI fields supply their own padding and height.
                        // Remove Material's internal padding from those plain fields.
                        AppRootView().textFieldContentPadding(bridgedTop = 0.0, bridgedLeading = 0.0, bridgedBottom = 0.0, bridgedTrailing = 0.0).Compose(context = contentContext)
                    }
                }
            }
        }
    }
}
