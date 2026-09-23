import SkipFuse
import SwiftUI

// MARK: - Skeleton primitives

struct SkeletonBlock: View {
    var height: CGFloat = 16
    var width: CGFloat? = nil
    var cornerRadius: CGFloat = 8

    var body: some View {
        RoundedRectangle(cornerRadius: self.cornerRadius)
            .fill(AppDesign.hairline.opacity(0.65))
            .frame(width: width, height: height)
            .frame(maxWidth: width == nil ? .infinity : nil, alignment: .leading)
            .shimmering()
    }
}

struct SkeletonCircle: View {
    var size: CGFloat = 48

    var body: some View {
        Circle()
            .fill(AppDesign.hairline.opacity(0.65))
            .frame(width: size, height: size)
            .shimmering()
    }
}

struct ShimmerModifier: ViewModifier {
    func body(content: Content) -> some View {
        content
            .overlay(
                LinearGradient(
                    colors: [
                        Color.white.opacity(0.0),
                        Color.white.opacity(0.28),
                        Color.white.opacity(0.0),
                    ],
                    startPoint: .leading,
                    endPoint: .trailing
                )
                .allowsHitTesting(false)
            )
            .clipped()
    }
}

extension View {
    func shimmering() -> some View {
        modifier(ShimmerModifier())
    }
}

// MARK: - Screen skeletons

struct ProductsSkeletonView: View {
    var layout: ProductDisplayLayout = .listWithImage

    private let columns = [
        GridItem(.flexible(), spacing: 12),
        GridItem(.flexible(), spacing: 12),
    ]

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 16) {
                SkeletonBlock(height: 44, cornerRadius: AppDesign.radiusM)
                    .padding(.horizontal, 20)
                HStack(spacing: 8) {
                    ForEach(0 ..< 4, id: \.self) { _ in
                        SkeletonBlock(height: 32, cornerRadius: 16)
                            .frame(width: 72)
                    }
                }
                .padding(.horizontal, 16)

                productPlaceholders
            }
            .padding(.top, 12)
            .padding(.bottom, 24)
        }
    }

    @ViewBuilder
    private var productPlaceholders: some View {
        switch layout {
        case .listWithImage:
            VStack(spacing: 0) {
                ForEach(0 ..< 5, id: \.self) { _ in
                    VStack(alignment: .leading, spacing: 12) {
                        HStack(alignment: .top, spacing: 16) {
                            VStack(alignment: .leading, spacing: 8) {
                                SkeletonBlock(height: 12, width: 72)
                                SkeletonBlock(height: 18)
                                SkeletonBlock(height: 12, width: 120)
                            }
                            SkeletonBlock(height: 96, width: 96, cornerRadius: AppDesign.radiusM)
                        }
                        HStack {
                            SkeletonBlock(height: 18, width: 72)
                            Spacer()
                            SkeletonBlock(height: 36, width: 104, cornerRadius: 18)
                        }
                    }
                    .padding(.vertical, 16)
                    Divider()
                }
            }
            .padding(.horizontal, 16)
        case .grid2Columns:
            LazyVGrid(columns: columns, spacing: 12) {
                ForEach(0 ..< 6, id: \.self) { _ in
                    VStack(alignment: .leading, spacing: 10) {
                        SkeletonBlock(height: 128, cornerRadius: AppDesign.radiusM)
                        SkeletonBlock(height: 16)
                        SkeletonBlock(height: 16, width: 64)
                        HStack {
                            Spacer()
                            SkeletonBlock(height: 36, width: 104, cornerRadius: 18)
                        }
                    }
                    .padding(10)
                    .background(AppDesign.surface)
                    .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusL, style: .continuous))
                }
            }
            .padding(.horizontal, 16)
        case .nameOnly:
            VStack(spacing: 10) {
                ForEach(0 ..< 6, id: \.self) { _ in
                    HStack(spacing: 12) {
                        VStack(alignment: .leading, spacing: 7) {
                            SkeletonBlock(height: 18)
                            SkeletonBlock(height: 14, width: 64)
                        }
                        SkeletonBlock(height: 36, width: 104, cornerRadius: 18)
                    }
                    .padding(14)
                    .background(AppDesign.surface)
                    .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous))
                }
            }
            .padding(.horizontal, 16)
        }
    }
}

struct MembershipSkeletonView: View {
    var body: some View {
        ScrollView {
            VStack(spacing: 16) {
                VStack(alignment: .leading, spacing: 16) {
                    HStack {
                        SkeletonBlock(height: 12, width: 100)
                        Spacer()
                        SkeletonBlock(height: 24, width: 64, cornerRadius: 12)
                    }
                    HStack(spacing: 12) {
                        SkeletonCircle(size: 48)
                        VStack(alignment: .leading, spacing: 8) {
                            SkeletonBlock(height: 18, width: 120)
                            SkeletonBlock(height: 14, width: 64)
                        }
                        Spacer()
                    }
                    SkeletonBlock(height: 72, cornerRadius: 12)
                    SkeletonBlock(height: 12, width: 140)
                }
                .padding(20)
                .background(AppDesign.surface)
                .clipShape(RoundedRectangle(cornerRadius: 24, style: .continuous))

                SkeletonBlock(height: 72, cornerRadius: AppDesign.radiusM)

                ForEach(0 ..< 3, id: \.self) { _ in
                    SkeletonBlock(height: 64, cornerRadius: AppDesign.radiusM)
                }
            }
            .padding(16)
        }
    }
}

struct CouponsSkeletonView: View {
    var body: some View {
        ScrollView {
            LazyVStack(spacing: 14) {
                ForEach(0 ..< 3, id: \.self) { _ in
                    VStack(alignment: .leading, spacing: 0) {
                        SkeletonBlock(height: 148, cornerRadius: 0)
                        VStack(alignment: .leading, spacing: 10) {
                            SkeletonBlock(height: 18, width: 160)
                            SkeletonBlock(height: 12)
                            SkeletonBlock(height: 12, width: 100)
                            SkeletonBlock(height: 44, cornerRadius: 12)
                        }
                        .padding(16)
                    }
                    .background(AppDesign.surface)
                    .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusL, style: .continuous))
                }
            }
            .padding(16)
        }
    }
}

struct StampsSkeletonView: View {
    var body: some View {
        ScrollView {
            LazyVStack(spacing: 16) {
                ForEach(0 ..< 2, id: \.self) { _ in
                    VStack(alignment: .leading, spacing: 14) {
                        HStack(spacing: 14) {
                            SkeletonBlock(height: 72, width: 72, cornerRadius: 16)
                            VStack(alignment: .leading, spacing: 8) {
                                SkeletonBlock(height: 16, width: 140)
                                SkeletonBlock(height: 28, width: 80)
                            }
                            Spacer()
                        }
                        HStack(spacing: 8) {
                            ForEach(0 ..< 5, id: \.self) { _ in
                                SkeletonCircle(size: 40)
                            }
                        }
                    }
                    .padding(18)
                    .background(AppDesign.surface)
                    .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusL, style: .continuous))
                }
            }
            .padding(16)
        }
    }
}

struct QueueSkeletonView: View {
    var body: some View {
        VStack(spacing: 24) {
            Spacer(minLength: 40)
            SkeletonBlock(height: 28, width: 140)
            SkeletonBlock(height: 16, width: 200)
            VStack(spacing: 16) {
                SkeletonBlock(height: 12, width: 48)
                SkeletonBlock(height: 48, width: 80)
            }
            .padding(24)
            .frame(maxWidth: .infinity)
            .background(AppDesign.surface)
            .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusL, style: .continuous))
            .padding(.horizontal, 24)
            SkeletonBlock(height: 52, cornerRadius: 14)
                .padding(.horizontal, 24)
            Spacer()
        }
    }
}

struct HomeSkeletonView: View {
    var body: some View {
        VStack(spacing: 10) {
            ForEach(0 ..< 3, id: \.self) { _ in
                SkeletonBlock(height: 64, cornerRadius: AppDesign.radiusM)
            }
        }
    }
}

struct OrdersSkeletonView: View {
    var body: some View {
        ListCardSkeletonView(count: 5)
    }
}

struct ListCardSkeletonView: View {
    var count: Int = 4

    var body: some View {
        ScrollView {
            VStack(spacing: 12) {
                ForEach(0 ..< count, id: \.self) { _ in
                    SkeletonBlock(height: 88, cornerRadius: AppDesign.radiusL)
                }
            }
            .padding(16)
        }
    }
}

struct GallerySkeletonView: View {
    private let columns = [
        GridItem(.flexible(), spacing: 10),
        GridItem(.flexible(), spacing: 10),
    ]

    var body: some View {
        ScrollView {
            LazyVGrid(columns: columns, spacing: 10) {
                ForEach(0 ..< 6, id: \.self) { _ in
                    SkeletonBlock(height: 140, cornerRadius: 12)
                }
            }
            .padding(16)
        }
    }
}
