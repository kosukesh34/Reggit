import java.util.Properties

plugins {
    alias(libs.plugins.kotlin.compose)
    alias(libs.plugins.android.application)
    id("skip-build-plugin")
    id("com.google.gms.google-services") version "4.4.2" apply true
}

skip {
}

kotlin {
    compilerOptions {
        jvmTarget = org.jetbrains.kotlin.gradle.dsl.JvmTarget.fromTarget(libs.versions.jvm.get().toString())
    }
}

android {
    namespace = group as String
    compileSdk = libs.versions.android.sdk.compile.get().toInt()
    compileOptions {
        sourceCompatibility = JavaVersion.toVersion(libs.versions.jvm.get())
        targetCompatibility = JavaVersion.toVersion(libs.versions.jvm.get())
    }
    packaging {
        jniLibs {
            keepDebugSymbols.add("**/*.so")
            pickFirsts.add("**/*.so")
            // this option will compress JNI .so files
            useLegacyPackaging = true
        }
    }

    defaultConfig {
        minSdk = libs.versions.android.sdk.min.get().toInt()
        targetSdk = libs.versions.android.sdk.compile.get().toInt()
        // skip.tools.skip-build-plugin will automatically use Skip.env properties for:
        // applicationId = ANDROID_APPLICATION_ID ?? PRODUCT_BUNDLE_IDENTIFIER
        // versionCode = CURRENT_PROJECT_VERSION
        // versionName = MARKETING_VERSION
        // LIFF_BASE_URL を Skip.env から読み、Bundle.main.infoDictionary で参照できるように manifest に渡す
        val skipEnvFile = file("../../Skip.env")
        fun readSkipEnvValue(key: String, defaultValue: String): String {
            if (!skipEnvFile.isFile) return defaultValue
            return skipEnvFile.readLines()
                .asSequence()
                .map { it.trim() }
                .filter { it.isNotEmpty() && !it.startsWith("//") }
                .firstOrNull { it.startsWith("$key ") || it.startsWith("$key=") }
                ?.substringAfter("=", "")
                ?.trim()
                ?.trim('"', '\'')
                ?.takeIf { it.isNotEmpty() }
                ?: defaultValue
        }

        val liffBaseUrl = readSkipEnvValue("LIFF_BASE_URL", "https://unadministrable-idiocratically-annalee.ngrok-free.dev")
        val liffBaseUrlAndroid = readSkipEnvValue("LIFF_BASE_URL_ANDROID", liffBaseUrl)
        val liffProjectKey = readSkipEnvValue("LIFF_PROJECT_KEY", "default")
        val liffProjectKeyAndroid = readSkipEnvValue("LIFF_PROJECT_KEY_ANDROID", liffProjectKey)
        val liffFixedTab = readSkipEnvValue("LIFF_FIXED_TAB", "products")
        val liffFixedTabAndroid = readSkipEnvValue("LIFF_FIXED_TAB_ANDROID", liffFixedTab)
        val oneSignalAppId = readSkipEnvValue("ONE_SIGNAL_APP_ID", "")
        // Android では Bundle 経由で共通キーを参照するケースがあるため、
        // 共通キーにも Android 向け URL を設定して白画面（ngrok エラーページ遷移）を防ぐ。
        manifestPlaceholders["LIFF_BASE_URL"] = liffBaseUrlAndroid
        manifestPlaceholders["ONE_SIGNAL_APP_ID"] = oneSignalAppId
        manifestPlaceholders["LIFF_BASE_URL_ANDROID"] = liffBaseUrlAndroid
        manifestPlaceholders["LIFF_PROJECT_KEY"] = liffProjectKey
        manifestPlaceholders["LIFF_PROJECT_KEY_ANDROID"] = liffProjectKeyAndroid
        manifestPlaceholders["LIFF_FIXED_TAB"] = liffFixedTab
        manifestPlaceholders["LIFF_FIXED_TAB_ANDROID"] = liffFixedTabAndroid
    }

    buildFeatures {
        buildConfig = true
    }

    lint {
        disable.add("Instantiatable")
        disable.add("MissingPermission")
    }

    dependenciesInfo {
        // Disables dependency metadata when building APKs.
        includeInApk = false
        // Disables dependency metadata when building Android App Bundles.
        includeInBundle = false
    }

    // default signing configuration tries to load from keystore.properties
    // see: https://skip.dev/docs/deployment/#export-signing
    signingConfigs {
        val keystorePropertiesFile = file("keystore.properties")
        create("release") {
            if (keystorePropertiesFile.isFile) {
                val keystoreProperties = Properties()
                keystoreProperties.load(keystorePropertiesFile.inputStream())
                keyAlias = keystoreProperties.getProperty("keyAlias")
                keyPassword = keystoreProperties.getProperty("keyPassword")
                storeFile = file(keystoreProperties.getProperty("storeFile"))
                storePassword = keystoreProperties.getProperty("storePassword")
            } else {
                // when there is no keystore.properties file, fall back to signing with debug config
                keyAlias = signingConfigs.getByName("debug").keyAlias
                keyPassword = signingConfigs.getByName("debug").keyPassword
                storeFile = signingConfigs.getByName("debug").storeFile
                storePassword = signingConfigs.getByName("debug").storePassword
            }
        }
    }

    buildTypes {
        release {
            signingConfig = signingConfigs.findByName("release")
            isMinifyEnabled = true
            isShrinkResources = true
            isDebuggable = false // can be set to true for debugging release build, but needs to be false when uploading to store
            proguardFiles(getDefaultProguardFile("proguard-android-optimize.txt"), "proguard-rules.pro")
        }
    }
}

dependencies {
    // OneSignal push notifications (FCM is used under the hood; configure google-services.json)
    implementation("com.onesignal:OneSignal:[5.0.0, 5.99.99]")
}

// Skip plugin adds implementation("reggit.app:Reggit"), but skipstone publishes
// group "reggit.module" (from Package.swift name "reggit-skip"). Substitute to the
// included :Reggit project so assembleDebug resolves.
configurations.configureEach {
    resolutionStrategy.dependencySubstitution {
        substitute(module("reggit.app:Reggit")).using(project(":Reggit"))
    }
}
