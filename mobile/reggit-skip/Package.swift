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
        // github.com/skiptools に統一（source.skip.tools と混在すると identity conflict になる）
        .package(url: "https://github.com/skiptools/skip.git", from: "1.9.8"),
        .package(url: "https://github.com/skiptools/skip-fuse.git", from: "1.0.3"),
        .package(url: "https://github.com/skiptools/skip-fuse-ui.git", from: "1.18.2"),
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
