// swift-tools-version: 6.0
// This is a Skip (https://skip.dev) package — Fuse (native) mode.
import PackageDescription

let package = Package(
    name: "reggit-skip",
    defaultLocalization: "en",
    platforms: [.iOS(.v17), .macOS(.v14)],
    products: [
        .library(name: "Reggit", type: .dynamic, targets: ["Reggit"]),
    ],
    dependencies: [
        // Keep all Skip packages on the same canonical host to avoid SwiftPM
        // package-identity conflicts in generated Android packages.
        .package(url: "https://github.com/skiptools/skip.git", exact: "1.9.11"),
        .package(url: "https://github.com/skiptools/skip-fuse.git", exact: "1.0.3"),
        .package(url: "https://github.com/skiptools/skip-fuse-ui.git", exact: "1.18.2"),
    ],
    targets: [
        .target(
            name: "Reggit",
            dependencies: [
                .product(name: "SkipFuse", package: "skip-fuse"),
                .product(name: "SkipFuseUI", package: "skip-fuse-ui"),
            ],
            resources: [.process("Resources")],
            plugins: [.plugin(name: "skipstone", package: "skip")]
        ),
        .testTarget(name: "ReggitTests", dependencies: ["Reggit"]),
    ]
)
