package mobile.order.app

import skip.lib.*
import skip.model.*
import skip.foundation.*
import skip.ui.*

import android.Manifest
import android.app.Application
import android.content.Context
import android.content.pm.PackageManager
import android.graphics.Color as AndroidColor
import android.os.Build
import com.onesignal.OneSignal
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.activity.SystemBarStyle
import androidx.activity.ComponentActivity
import androidx.appcompat.app.AppCompatActivity
import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.Box
import androidx.compose.ui.graphics.Color
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.SideEffect
import androidx.compose.runtime.saveable.rememberSaveableStateHolder
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.luminance
import androidx.compose.ui.platform.LocalContext
import androidx.compose.material3.MaterialTheme
import androidx.core.app.ActivityCompat

internal val logger: SkipLogger = SkipLogger(subsystem = "mobile.order.app", category = "MobileOrderApp")

private typealias AppRootView = MobileOrderAppRootView
private typealias AppDelegate = MobileOrderAppAppDelegate

/// Key for OneSignal subscription ID. Must match Skip module's key (mobile_order_push_token).
private const val PUSH_TOKEN_PREFS_KEY = "mobile_order_push_token"
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
        initOneSignalIfConfigured()
        AppDelegate.shared.onInit()
    }

    private fun initOneSignalIfConfigured() {
        val appId = try {
            (packageManager.getApplicationInfo(packageName, android.content.pm.PackageManager.GET_META_DATA).metaData?.getString("ONE_SIGNAL_APP_ID"))?.trim()
        } catch (_: Exception) {
            null
        }
        if (appId.isNullOrEmpty()) return
        OneSignal.initWithContext(this, appId)
        // 通知権限は SDK が初回表示。subscription id は遅延で保存
        // トークン取得後に保存（2秒・5秒後にポーリング）
        android.os.Handler(android.os.Looper.getMainLooper()).postDelayed({ saveOneSignalSubscriptionIdIfNeeded() }, 2000)
        android.os.Handler(android.os.Looper.getMainLooper()).postDelayed({ saveOneSignalSubscriptionIdIfNeeded() }, 5000)
    }

    private fun saveOneSignalSubscriptionIdIfNeeded() {
        val id = OneSignal.User.pushSubscription.id?.takeIf { it.isNotBlank() } ?: return
        val prefs = applicationContext.getSharedPreferences(PUSH_TOKEN_PREFS_NAME, Context.MODE_PRIVATE)
        if (id == prefs.getString(PUSH_TOKEN_PREFS_KEY, null)) return
        prefs.edit().putString(PUSH_TOKEN_PREFS_KEY, id).apply()
    }

    fun saveOneSignalSubscriptionIdIfNeededPublic() {
        saveOneSignalSubscriptionIdIfNeeded()
    }

    companion object {
    }
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
                (application as? AndroidAppMain)?.saveOneSignalSubscriptionIdIfNeededPublic()
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

@Composable
internal fun PresentationRootView(context: ComposeContext) {
    val colorScheme = if (isSystemInDarkTheme()) ColorScheme.dark else ColorScheme.light
    PresentationRoot(defaultColorScheme = colorScheme, context = context) { ctx ->
        SyncSystemBarsWithTheme()
        val contentContext = ctx.content()
        Box(modifier = ctx.modifier.fillMaxSize(), contentAlignment = Alignment.TopStart) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .background(Color(0xFFF5F5F5))
            ) {
                AppRootView().Compose(context = contentContext)
            }
        }
    }
}
