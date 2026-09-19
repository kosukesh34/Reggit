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
        if (token == prefs.getString(PUSH_TOKEN_PREFS_KEY, null)) return
        prefs.edit().putString(PUSH_TOKEN_PREFS_KEY, token).apply()
        AppDelegate.shared.onPushTokenReceived(token)
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
        AppDelegate.shared.onPushTokenReceived(token)
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
        if (type == "coupon_issued") {
            AppDelegate.shared.onNotificationForeground(notificationType = type)
        }
    }
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

private fun resolveCancelledIdsBaseUrl(context: Context): String? {
    // Debug は iOS と同様に開発サーバ（ngrok）へ向ける。Skip.env の本番 URL だと取消しIDが取れない。
    if (BuildConfig.DEBUG) {
        return "https://unadministrable-idiocratically-annalee.ngrok-free.dev"
    }
    val meta = context.packageManager
        .getApplicationInfo(context.packageName, PackageManager.GET_META_DATA)
        .metaData
    return listOf("LiffBaseUrlAndroid", "LIFF_BASE_URL_ANDROID", "LiffBaseUrl", "LIFF_BASE_URL")
        .mapNotNull { key -> meta?.getString(key)?.trim()?.takeIf { it.isNotEmpty() && it != "undefined" && it != "null" } }
        .firstOrNull()
        ?.trimEnd('/')
}

private fun resolveCancelledIdsProjectKey(context: Context): String {
    val meta = context.packageManager
        .getApplicationInfo(context.packageName, PackageManager.GET_META_DATA)
        .metaData
    return listOf("LiffProjectKeyAndroid", "LIFF_PROJECT_KEY_ANDROID", "LiffProjectKey", "LIFF_PROJECT_KEY")
        .mapNotNull { key -> meta?.getString(key)?.trim()?.takeIf { it.isNotEmpty() && it != "undefined" && it != "null" } }
        .firstOrNull()
        ?: ""
}

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
    // SwiftUI 側と同じライトテーマを明示し、端末設定による色差をなくす。
    val colorScheme = ColorScheme.light
    PresentationRoot(defaultColorScheme = colorScheme, context = context) { ctx ->
        SyncSystemBarsWithTheme()
        val contentContext = ctx.content()
        Box(modifier = ctx.modifier.fillMaxSize(), contentAlignment = Alignment.TopStart) {
            BoxWithConstraints(modifier = Modifier.fillMaxSize()) {
                Box(
                    modifier = Modifier
                        .size(maxWidth, maxHeight.coerceAtLeast(400.dp))
                        .background(Color(0xFFF9F6ED))
                ) {
                    AppRootView().Compose(context = contentContext)
                }
            }
        }
    }
}
